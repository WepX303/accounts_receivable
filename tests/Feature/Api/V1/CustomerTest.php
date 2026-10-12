<?php

namespace Tests\Feature\Api\V1;

use App\Enums\UserRoleEnum;
use App\Models\AvshocrecatReport;

class CustomerTest extends ApiTestCase
{
    public function test_search_finds_active_credits_by_name_phone_passport_contract_or_client_ref(): void
    {
        $this->credit('1000.00', '250.00', ['name' => 'Aman Amanow', 'phone' => '99361000001', 'passport' => 'I-AS111111', 'contract' => 'C-000111', 'clientref' => '70001', 'rv_bigint' => 1]);
        $this->credit('1000.00', '0.00', ['name' => 'Oraz Orazow', 'rv_bigint' => 2]);
        $this->credit('1000.00', '0.00', ['name' => 'Aman Pasif', 'active' => false, 'rv_bigint' => 3]);

        $this->signIn($this->user());

        foreach (['aman', '99361000001', 'as1111', 'c-000111', '70001'] as $q) {
            $this->getJson('/api/v1/customers/search?q=' . urlencode($q))
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.name', 'Aman Amanow');
        }

        $this->getJson('/api/v1/customers/search?q=aman')
            ->assertJsonPath('data.0.amount', '1000.00')
            ->assertJsonPath('data.0.paid', '250.00')
            ->assertJsonPath('data.0.remaining', '750.00')
            ->assertJsonPath('data.0.is_closed', false)
            ->assertJsonPath('meta.total', 1);

        // Without a query: every active credit, newest first.
        $this->getJson('/api/v1/customers/search')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.name', 'Oraz Orazow');
    }

    public function test_search_is_paged_and_its_input_is_bounded(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->credit();
        }
        $this->signIn($this->user());

        $this->getJson('/api/v1/customers/search?per_page=2&page=2')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.last_page', 2);

        $this->getJson('/api/v1/customers/search?per_page=500')->assertStatus(422);
        $this->getJson('/api/v1/customers/search?q=' . str_repeat('a', 101))->assertStatus(422);
        $this->getJson("/api/v1/customers/search?q=' OR 1=1 --")->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_customer_card_holds_the_debt_the_plan_and_the_latest_payments(): void
    {
        $credit = $this->credit('1000.00', '0.00', ['contract' => 'C-000111']);
        AvshocrecatReport::create(['sertnama_nomeri' => 'C-000111', 'tolejek_senesi' => '2026-11-01', 'aylyk_tolegi' => '166.67']);
        AvshocrecatReport::create(['sertnama_nomeri' => 'C-000111', 'tolejek_senesi' => '2026-10-01', 'aylyk_tolegi' => '166.67']);
        AvshocrecatReport::create(['sertnama_nomeri' => 'C-OTHER', 'tolejek_senesi' => '2026-10-01']);

        $this->signIn($this->user());
        for ($i = 0; $i < 12; $i++) {
            $this->postJson('/api/v1/payments', ['credit_id' => $credit->source_id, 'method' => 'cash', 'amount' => 10], ['Idempotency-Key' => "card-test-key-{$i}-xxxx"])
                ->assertCreated();
        }

        $this->getJson("/api/v1/customers/{$credit->source_id}")
            ->assertOk()
            ->assertJsonPath('data.credit.id', $credit->source_id)
            ->assertJsonPath('data.credit.remaining', '880.00')
            ->assertJsonCount(2, 'data.schedule')
            ->assertJsonPath('data.schedule.0.will_pay_date', '2026-10-01')
            ->assertJsonPath('data.schedule.0.monthly_payment', '166.67')
            ->assertJsonCount(10, 'data.recent_payments')
            ->assertJsonPath('data.recent_payments.0.remaining_after', '880.00');

        $this->getJson("/api/v1/customers/{$credit->source_id}/payments?per_page=5")
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 12);
    }

    public function test_inactive_or_unknown_credit_is_not_found(): void
    {
        $inactive = $this->credit('1000.00', '0.00', ['active' => false]);
        $this->signIn($this->user());

        $this->getJson("/api/v1/customers/{$inactive->source_id}")->assertNotFound()->assertJsonPath('error_code', 'not_found');
        $this->getJson("/api/v1/customers/{$inactive->source_id}/payments")->assertNotFound();
        $this->getJson('/api/v1/customers/999999999')->assertNotFound();
        $this->getJson('/api/v1/customers/abc')->assertNotFound();
    }

    public function test_branch_restricted_user_sees_only_their_branches(): void
    {
        $own = $this->credit('1000.00', '0.00', ['branch' => 'Mary', 'name' => 'Own Customer']);
        $other = $this->credit('1000.00', '0.00', ['branch' => 'Lebap', 'name' => 'Other Customer']);

        $this->signIn($this->user(UserRoleEnum::CASHIER, ['branches' => ['Mary']]));

        $this->getJson('/api/v1/customers/search')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Own Customer');

        $this->getJson("/api/v1/customers/{$own->source_id}")->assertOk();
        $this->getJson("/api/v1/customers/{$other->source_id}")->assertNotFound();
        $this->getJson("/api/v1/customers/{$other->source_id}/payments")->assertNotFound();
    }

    public function test_only_roles_that_serve_customers_may_look_them_up(): void
    {
        $credit = $this->credit();

        $this->getJson('/api/v1/customers/search')->assertStatus(401);

        foreach ([UserRoleEnum::ANALYST, UserRoleEnum::MANAGER, UserRoleEnum::USER] as $role) {
            $this->signIn($this->user($role));
            $this->getJson('/api/v1/customers/search')->assertForbidden()->assertJsonPath('error_code', 'forbidden');
            $this->getJson("/api/v1/customers/{$credit->source_id}")->assertForbidden();
        }

        foreach ([UserRoleEnum::SUPER_ADMIN, UserRoleEnum::ADMIN, UserRoleEnum::CASHIER, UserRoleEnum::OPERATOR] as $role) {
            $this->signIn($this->user($role))->getJson("/api/v1/customers/{$credit->source_id}")->assertOk();
        }
    }
}
