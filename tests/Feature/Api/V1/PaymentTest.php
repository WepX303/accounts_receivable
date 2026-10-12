<?php

namespace Tests\Feature\Api\V1;

use App\Enums\UserRoleEnum;
use App\Models\Credit;
use App\Models\CreditPayment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;

class PaymentTest extends ApiTestCase
{
    private int $keys = 0;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-12 14:30:45'));
    }

    private function pay(Credit $credit, array $data = [], ?string $key = null): TestResponse
    {
        return $this->postJson('/api/v1/payments', array_merge([
            'credit_id' => $credit->source_id,
            'method' => 'cash',
            'amount' => 100,
        ], $data), ['Idempotency-Key' => $key ?? 'test-key-' . str_pad((string) ++$this->keys, 8, '0', STR_PAD_LEFT)]);
    }

    private function payments(Credit $credit)
    {
        return CreditPayment::withoutGlobalScopes()->where('credit_source_id', $credit->source_id)->orderBy('id')->get();
    }

    public function test_cashier_takes_a_payment_exactly_as_in_the_panel(): void
    {
        $cashier = $this->user(UserRoleEnum::CASHIER, ['firstname' => 'Aman', 'lastname' => 'Amanow']);
        $credit = $this->credit('1000.00', '200.00', ['branch' => 'Mary']);

        $this->signIn($cashier)->pay($credit, ['amount' => 300, 'note' => '  first   part '])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.payment.method', 'cash')
            ->assertJsonPath('data.payment.amount', '300.00')
            ->assertJsonPath('data.payment.change', '0.00')
            ->assertJsonPath('data.payment.applied', '300.00')
            ->assertJsonPath('data.payment.remaining_before', '800.00')
            ->assertJsonPath('data.payment.remaining_after', '500.00')
            ->assertJsonPath('data.payment.cashier', 'Aman Amanow')
            ->assertJsonPath('data.payment.note', 'first part')
            ->assertJsonPath('data.payment.is_voided', false)
            ->assertJsonPath('data.credit.paid', '500.00')
            ->assertJsonPath('data.credit.remaining', '500.00');

        $row = $this->creditRow($credit);
        $this->assertSame('500.00', $row->paid_local);
        $this->assertSame(
            'backdated=no | payment_at=2026-10-12 14:30:45 | entered_at=2026-10-12 14:30:45'
            . ' | method=cash | received=300.00 | applied=300.00 | change=0.00'
            . ' | cash=300.00 | card=0.00 | phone=0.00 | total_local=1000.00'
            . ' | old_paid_local=200.00 | new_paid_local=500.00'
            . ' | old_remaining=800.00 | new_remaining=500.00 | note=first part',
            $row->paid_note
        );

        $payment = $this->payments($credit)->sole();
        $this->assertSame($credit->source_id, $payment->credit_source_id);
        $this->assertSame($credit->logicalref, $payment->credit_logicalref);
        $this->assertSame($cashier->id, $payment->created_by);
        $this->assertSame('Mary', $payment->branch);
        $this->assertSame($credit->name, $payment->customer_name);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'payment_created',
            'user_id' => $cashier->id,
            'subject_id' => $credit->source_id,
            'severity' => 'info',
        ]);
    }

    public function test_card_mixed_and_phone_payments(): void
    {
        $credit = $this->credit('5000.00');
        $this->signIn($this->user());

        $this->pay($credit, ['method' => 'card', 'amount' => 250])
            ->assertCreated()->assertJsonPath('data.payment.card_amount', '250.00')->assertJsonPath('data.payment.cash_amount', '0.00');

        $this->pay($credit, ['method' => 'mixed', 'amount' => 300, 'cash_amount' => 120.5, 'card_amount' => 179.5])
            ->assertCreated()->assertJsonPath('data.payment.cash_amount', '120.50')->assertJsonPath('data.payment.card_amount', '179.50');

        $this->pay($credit, ['method' => 'phone', 'amount' => 80, 'receiver_phone_number' => '9936 111 2233'])
            ->assertCreated()->assertJsonPath('data.payment.phone_amount', '80.00')->assertJsonPath('data.payment.receiver_phone_number', '99361112233');

        $this->assertSame('630.00', $this->creditRow($credit)->paid_local);
    }

    public function test_refusals_carry_the_panels_message_and_a_code(): void
    {
        $credit = $this->credit('1000.00', '900.00');
        $closed = $this->credit('1000.00', '1000.00');
        $this->signIn($this->user());

        $this->pay($credit, ['method' => 'mixed', 'amount' => 100, 'cash_amount' => 10, 'card_amount' => 20])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'mixed_sum_must_equal')
            ->assertJsonPath('message', __('validations/validations.payments.mixed_sum_must_equal'));

        $this->pay($credit, ['method' => 'phone'])->assertStatus(422)->assertJsonPath('error_code', 'receiver_phone_number_required');
        $this->pay($credit, ['method' => 'phone', 'receiver_phone_number' => '+993'])->assertStatus(422)->assertJsonPath('error_code', 'receiver_phone_number_digits');
        $this->pay($credit, ['amount' => 0])->assertStatus(422)->assertJsonPath('error_code', 'pay_amount_zero');
        $this->pay($credit, ['amount' => 200.01])->assertStatus(422)->assertJsonPath('error_code', 'overpayment_too_high');
        $this->pay($credit, ['paid_at' => '2026-10-13T09:00:00+05:00'])->assertStatus(422)->assertJsonPath('error_code', 'future_payment_date');
        $this->pay($closed)->assertStatus(422)->assertJsonPath('error_code', 'debt_closed');

        $this->assertSame(0, CreditPayment::withoutGlobalScopes()->count());
        $this->assertSame('900.00', $this->creditRow($credit)->paid_local);
    }

    public function test_malformed_input_is_rejected_before_it_reaches_the_books(): void
    {
        $credit = $this->credit();
        $this->signIn($this->user());

        $this->pay($credit, ['method' => 'crypto'])->assertStatus(422)->assertJsonPath('error_code', 'validation_failed')->assertJsonStructure(['errors' => ['method']]);
        $this->pay($credit, ['amount' => '1e9999'])->assertStatus(422);
        $this->pay($credit, ['amount' => -5])->assertStatus(422);
        $this->pay($credit, ['amount' => '10.123'])->assertStatus(422);
        $this->pay($credit, ['amount' => '1,5'])->assertStatus(422);
        $this->pay($credit, ['method' => 'mixed', 'amount' => 10, 'cash_amount' => '1e3'])->assertStatus(422);
        $this->pay($credit, ['amount' => 'abc'])->assertStatus(422);
        $this->pay($credit, ['amount' => ['x' => 1]])->assertStatus(422);
        $this->pay($credit, ['credit_id' => 'abc'])->assertStatus(422);
        $this->pay($credit, ['note' => str_repeat('a', 501)])->assertStatus(422);
        $this->pay($credit, ['paid_at' => 'yesterday-ish'])->assertStatus(422);

        $this->assertSame(0, CreditPayment::withoutGlobalScopes()->count());
    }

    public function test_change_is_returned_and_the_credit_closes(): void
    {
        $credit = $this->credit('1000.00', '900.00');

        $this->signIn($this->user())->pay($credit, ['amount' => 150])
            ->assertCreated()
            ->assertJsonPath('data.payment.change', '50.00')
            ->assertJsonPath('data.payment.applied', '100.00')
            ->assertJsonPath('data.credit.remaining', '0.00')
            ->assertJsonPath('data.credit.is_closed', true);
    }

    public function test_backdated_payment_is_booked_at_its_local_time_and_raises_the_alert(): void
    {
        $credit = $this->credit();

        // 04:15 UTC is 09:15 in Ashgabat.
        $this->signIn($this->user())->pay($credit, ['paid_at' => '2026-10-10T04:15:00Z'])->assertCreated();

        $this->assertSame('2026-10-10 09:15:00', $this->payments($credit)->sole()->created_at->format('Y-m-d H:i:s'));
        $this->assertDatabaseHas('security_alerts', ['alert_type' => 'backdated_payment', 'risk_level' => 'high']);
    }

    public function test_an_idempotency_key_is_required_and_a_repeat_books_nothing_new(): void
    {
        $credit = $this->credit();
        $this->signIn($this->user());

        $this->postJson('/api/v1/payments', ['credit_id' => $credit->source_id, 'method' => 'cash', 'amount' => 100])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['idempotency_key']]);
        $this->pay($credit, [], 'short')->assertStatus(422);
        $this->pay($credit, [], "bad key'; drop table--")->assertStatus(422);

        $first = $this->pay($credit, [], 'retry-key-00000001')->assertCreated();
        $again = $this->pay($credit, [], 'retry-key-00000001')->assertOk();

        $this->assertSame($first->json('data.payment.id'), $again->json('data.payment.id'));
        $this->assertCount(1, $this->payments($credit));
        $this->assertSame('100.00', $this->creditRow($credit)->paid_local);

        $other = $this->credit();
        $this->pay($other, [], 'retry-key-00000001')->assertStatus(409)->assertJsonPath('error_code', 'idempotency_key_reused');
        $this->assertCount(0, $this->payments($other));
    }

    public function test_branch_restricted_cashier_cannot_pay_into_another_branch(): void
    {
        $other = $this->credit('1000.00', '0.00', ['branch' => 'Lebap']);

        $this->signIn($this->user(UserRoleEnum::CASHIER, ['branches' => ['Mary']]))->pay($other)
            ->assertNotFound()
            ->assertJsonPath('error_code', 'not_found')
            ->assertJsonPath('message', __('api.not_found'));

        $this->assertCount(0, $this->payments($other));
        $this->assertSame('0.00', $this->creditRow($other)->paid_local);
    }

    public function test_inactive_credit_is_not_found_without_leaking_internals(): void
    {
        $credit = $this->credit('1000.00', '0.00', ['active' => false]);

        $response = $this->signIn($this->user())->pay($credit)->assertNotFound();

        $this->assertStringNotContainsString('App\\Models', $response->getContent());
    }

    public function test_unexpected_failure_answers_500_without_the_database_error(): void
    {
        $credit = $this->credit();
        DB::statement('ALTER TABLE credit_payments ADD CONSTRAINT test_block CHECK (pay_amount < 50)');

        $response = $this->signIn($this->user())->pay($credit)->assertStatus(500)->assertJsonPath('error_code', 'server_error');

        $this->assertStringNotContainsString('SQLSTATE', $response->getContent());
        $this->assertStringNotContainsString('test_block', $response->getContent());
        $this->assertSame('0.00', $this->creditRow($credit)->paid_local);
    }

    public function test_only_roles_that_take_payments_may_post_one(): void
    {
        $credit = $this->credit();

        $this->pay($credit)->assertStatus(401);

        foreach ([UserRoleEnum::ANALYST, UserRoleEnum::MANAGER, UserRoleEnum::USER] as $role) {
            $this->signIn($this->user($role))->pay($credit)->assertForbidden();
        }

        foreach ([UserRoleEnum::SUPER_ADMIN, UserRoleEnum::ADMIN, UserRoleEnum::CASHIER, UserRoleEnum::OPERATOR] as $role) {
            $this->signIn($this->user($role))->pay($credit, ['amount' => 10])->assertCreated();
        }

        $this->assertSame('40.00', $this->creditRow($credit)->paid_local);
    }

    public function test_admin_voids_a_payment(): void
    {
        $admin = $this->user(UserRoleEnum::ADMIN);
        $credit = $this->credit('1000.00', '200.00');
        $id = $this->signIn($this->user())->pay($credit, ['amount' => 300])->json('data.payment.id');

        $this->signIn($admin);

        $this->postJson("/api/v1/payments/{$id}/void", [])->assertStatus(422)->assertJsonStructure(['errors' => ['reason']]);

        $this->postJson("/api/v1/payments/{$id}/void", ['reason' => 'wrong customer'])
            ->assertOk()
            ->assertJsonPath('data.payment.is_voided', true)
            ->assertJsonPath('data.payment.void_reason', 'wrong customer')
            ->assertJsonPath('data.payment.voided_by', $admin->full_name)
            ->assertJsonPath('data.payment.amount', '300.00')
            ->assertJsonPath('data.credit.paid', '200.00');

        $this->postJson("/api/v1/payments/{$id}/void", ['reason' => 'again'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'already_voided');

        $this->assertSame('200.00', $this->creditRow($credit)->paid_local);
        $this->assertDatabaseHas('security_alerts', ['alert_type' => 'payment_voided', 'risk_level' => 'critical']);
    }

    public function test_admin_corrects_a_payment(): void
    {
        $cashier = $this->user(UserRoleEnum::CASHIER, ['firstname' => 'Aman', 'lastname' => 'Amanow']);
        $admin = $this->user(UserRoleEnum::ADMIN);
        $credit = $this->credit('1000.00', '200.00');
        $id = $this->signIn($cashier)->pay($credit, ['amount' => 300])->json('data.payment.id');

        $response = $this->signIn($admin)->postJson("/api/v1/payments/{$id}/correct", [
            'method' => 'card',
            'amount' => 450,
            'reason' => 'typed wrong',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.payment.amount', '450.00')
            ->assertJsonPath('data.payment.method', 'card')
            ->assertJsonPath('data.payment.cashier', 'Aman Amanow')
            ->assertJsonPath('data.payment.corrected_by', $admin->full_name)
            ->assertJsonPath('data.payment.corrected_from_payment_id', $id)
            ->assertJsonPath('data.payment.correct_reason', 'typed wrong')
            ->assertJsonPath('data.credit.paid', '650.00');

        $all = $this->payments($credit);
        $this->assertCount(2, $all);
        $this->assertNotNull($all[0]->voided_at);
        $this->assertSame($cashier->id, $all[1]->created_by);
        $this->assertSame($admin->id, $all[1]->corrected_by);

        // A repeat of the same correction finds the old payment voided and books nothing.
        $this->postJson("/api/v1/payments/{$id}/correct", ['method' => 'card', 'amount' => 450])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'already_voided');
        $this->assertCount(2, $this->payments($credit));

        $this->postJson("/api/v1/payments/{$all[1]->id}/correct", ['method' => 'cash', 'amount' => 5000])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'overpayment_too_high');
        $this->assertSame('650.00', $this->creditRow($credit)->paid_local);
    }

    public function test_void_and_correct_are_for_admins_and_stay_inside_their_branches(): void
    {
        $credit = $this->credit('1000.00', '0.00', ['branch' => 'Lebap']);
        $id = $this->signIn($this->user())->pay($credit, ['amount' => 300])->json('data.payment.id');

        foreach ([UserRoleEnum::CASHIER, UserRoleEnum::OPERATOR, UserRoleEnum::ANALYST, UserRoleEnum::MANAGER] as $role) {
            $this->signIn($this->user($role));
            $this->postJson("/api/v1/payments/{$id}/void", ['reason' => 'x'])->assertForbidden();
            $this->postJson("/api/v1/payments/{$id}/correct", ['method' => 'cash', 'amount' => 1])->assertForbidden();
        }

        $this->signIn($this->user(UserRoleEnum::ADMIN, ['branches' => ['Mary']]));
        $this->postJson("/api/v1/payments/{$id}/void", ['reason' => 'x'])->assertNotFound();
        $this->postJson("/api/v1/payments/{$id}/correct", ['method' => 'cash', 'amount' => 1])->assertNotFound();
        $this->postJson('/api/v1/payments/999999/void', ['reason' => 'x'])->assertNotFound();

        $this->assertNull($this->payments($credit)->sole()->voided_at);
        $this->assertSame('300.00', $this->creditRow($credit)->paid_local);
    }
}
