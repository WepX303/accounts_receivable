<?php

namespace Tests\Feature\Api\V1;

use App\Enums\UserRoleEnum;
use App\Models\Credit;
use App\Models\CreditPayment;
use App\Services\Payments\CreatePaymentData;
use App\Services\Payments\PaymentService;
use Illuminate\Support\Carbon;

class CustomerListTest extends ApiTestCase
{
    private Credit $mary;

    private Credit $lebap;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-14 15:00:00'));

        $this->mary = $this->credit('1000.00', '0.00', ['name' => 'Mary Customer', 'branch' => 'Mary', 'paid' => '0.00', 'rv_bigint' => 2, 'custstatus' => 'VIP']);
        $this->lebap = $this->credit('1000.00', '0.00', ['name' => 'Lebap Customer', 'branch' => 'Lebap', 'paid' => '0.00', 'rv_bigint' => 1]);
        $this->credit('1000.00', '0.00', ['name' => 'Deleted Customer', 'active' => false, 'rv_bigint' => 0]);

        $cashier = $this->user();
        foreach ([[$this->mary, 300, '2026-10-14 10:00'], [$this->lebap, 200, '2026-10-13 10:00']] as [$credit, $given, $at]) {
            app(PaymentService::class)->create($cashier, new CreatePaymentData(
                creditSourceId: $credit->source_id,
                method: 'cash',
                received: $given,
                paymentAt: Carbon::parse($at),
            ));
        }

        auth()->forgetGuards();
    }

    public function test_list_carries_the_panels_columns_counters_and_period(): void
    {
        $this->signIn($this->user(UserRoleEnum::ADMIN))->getJson('/api/v1/customers')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.name', 'Mary Customer')
            ->assertJsonPath('data.0.amount', '1000.00')
            ->assertJsonPath('data.0.paid', '300.00')
            ->assertJsonPath('data.0.remaining', '700.00')
            ->assertJsonPath('data.0.source_amount', '1000.00')
            ->assertJsonPath('data.0.source_paid', '0.00')
            ->assertJsonPath('data.0.source_remaining', '1000.00')
            ->assertJsonPath('data.0.customer_status', 'VIP')
            ->assertJsonPath('data.0.is_active', true)
            ->assertJsonPath('data.0.period_paid', '300.00')
            ->assertJsonPath('data.1.period_paid', '0.00')
            ->assertJsonPath('summary.quick_filter', 'all')
            ->assertJsonPath('summary.period_label', __('pages/customers_index.today_paid'))
            ->assertJsonPath('summary.paid_today_sum', '300.00')
            ->assertJsonPath('summary.paid_today_count', 1)
            ->assertJsonPath('summary.paid_yesterday_sum', '200.00')
            ->assertJsonPath('summary.has_debt_count', 3)
            ->assertJsonPath('summary.paid_mismatch_count', 2);
    }

    public function test_filters_work_as_in_the_panel(): void
    {
        $this->signIn($this->user(UserRoleEnum::ANALYST));

        $this->getJson('/api/v1/customers?quick_filter=paid_yesterday')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Lebap Customer')
            ->assertJsonPath('data.0.period_paid', '200.00')
            ->assertJsonPath('summary.period_label', __('pages/customers_index.paid_yesterday'));

        $this->getJson('/api/v1/customers?quick_filter=deleted')->assertJsonPath('data.0.name', 'Deleted Customer')->assertJsonPath('data.0.is_active', false);
        $this->getJson('/api/v1/customers?q=lebap')->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/customers?date_from=2026-10-13&date_to=2026-10-13')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('summary.period_label', '2026-10-13 → 2026-10-13');
        $this->getJson('/api/v1/customers?per_page=1&page=2')->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Lebap Customer');

        $this->getJson('/api/v1/customers?quick_filter=everything')->assertStatus(422)->assertJsonStructure(['errors' => ['quick_filter']]);
        $this->getJson('/api/v1/customers?per_page=1000')->assertStatus(422);
    }

    public function test_statement_lists_every_payment_of_the_credit(): void
    {
        $admin = $this->user(UserRoleEnum::ADMIN);
        $payment = CreditPayment::withoutGlobalScopes()->where('credit_source_id', $this->mary->source_id)->first();
        app(PaymentService::class)->void($admin, $payment, 'mistake');
        auth()->forgetGuards();

        $this->signIn($this->user(UserRoleEnum::MANAGER))->getJson("/api/v1/customers/{$this->mary->source_id}/statement")
            ->assertOk()
            ->assertJsonPath('data.credit.name', 'Mary Customer')
            ->assertJsonPath('data.credit.remaining', '1000.00')
            ->assertJsonCount(1, 'data.payments')
            ->assertJsonPath('data.payments.0.is_voided', true)
            ->assertJsonPath('data.payments.0.amount', '300.00');
    }

    public function test_export_downloads_the_panels_spreadsheet_and_is_logged(): void
    {
        $analyst = $this->user(UserRoleEnum::ANALYST);

        $response = $this->signIn($analyst)->get('/api/v1/customers/export?quick_filter=has_debt');

        $response->assertOk()->assertDownload('customers.xlsx');
        $this->assertDatabaseHas('activity_logs', ['action' => 'customers_exported', 'user_id' => $analyst->id]);

        $this->getJson('/api/v1/customers/export?quick_filter=everything')->assertStatus(422)->assertJsonPath('error_code', 'validation_failed');
    }

    public function test_branch_restricted_user_stays_inside_their_branches(): void
    {
        $this->signIn($this->user(UserRoleEnum::ANALYST, ['branches' => ['Lebap']]));

        $this->getJson('/api/v1/customers')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Lebap Customer')
            ->assertJsonPath('summary.paid_today_sum', '0.00')
            ->assertJsonPath('summary.has_debt_count', 1);

        $this->getJson("/api/v1/customers/{$this->mary->source_id}/statement")->assertNotFound();
        $this->getJson("/api/v1/customers/{$this->lebap->source_id}/statement")->assertOk();
    }

    public function test_only_roles_with_the_list_may_use_it(): void
    {
        foreach ([UserRoleEnum::CASHIER, UserRoleEnum::USER] as $role) {
            $this->signIn($this->user($role));
            $this->getJson('/api/v1/customers')->assertForbidden();
            $this->getJson('/api/v1/customers/export')->assertForbidden();
            $this->getJson("/api/v1/customers/{$this->mary->source_id}/statement")->assertForbidden();
        }

        foreach ([UserRoleEnum::SUPER_ADMIN, UserRoleEnum::ADMIN, UserRoleEnum::MANAGER, UserRoleEnum::ANALYST, UserRoleEnum::OPERATOR] as $role) {
            $this->signIn($this->user($role))->getJson('/api/v1/customers')->assertOk();
        }
    }
}
