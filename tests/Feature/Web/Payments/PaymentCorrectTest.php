<?php

namespace Tests\Feature\Web\Payments;

use App\Enums\UserRoleEnum;
use App\Models\CreditPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;

class PaymentCorrectTest extends PaymentTestCase
{
    private function correct(CreditPayment $payment, array $data = []): TestResponse
    {
        return $this->post("/payments/{$payment->id}/correct", array_merge([
            'payment_method' => 'cash',
            'pay_amount' => '100',
        ], $data));
    }

    public function test_correction_voids_the_old_payment_and_books_a_new_one(): void
    {
        $cashier = $this->user(UserRoleEnum::CASHIER, ['firstname' => 'Aman', 'lastname' => 'Amanow']);
        $admin = $this->user(UserRoleEnum::ADMIN);
        $credit = $this->credit('1000.00', '200.00');
        $old = $this->takePayment($credit, ['pay_amount' => '300'], $cashier);

        $this->signIn($admin)->correct($old, [
            'payment_method' => 'card',
            'pay_amount' => '450',
            'note' => ' real   amount ',
            'reason' => ' typed  wrong ',
        ])->assertSessionHas('success', __('validations/validations.payment_correct.corrected_success'));

        $this->assertCount(2, $this->paymentsOf($credit));

        $old = $this->paymentsOf($credit)->first();
        $this->assertSame('2026-10-12 14:30:45', $old->voided_at->format('Y-m-d H:i:s'));
        $this->assertSame($admin->id, $old->voided_by);
        $this->assertSame('typed wrong', $old->void_reason);
        $this->assertSame('300.00', $old->pay_amount);

        $new = $this->lastPayment($credit);
        $this->assertNull($new->voided_at);
        $this->assertSame('card', $new->method);
        $this->assertSame('450.00', $new->pay_amount);
        $this->assertSame('0.00', $new->change_amount);
        $this->assertSame('0.00', $new->cash_amount);
        $this->assertSame('450.00', $new->card_amount);
        $this->assertSame('800.00', $new->old_amount_local);
        $this->assertSame('350.00', $new->new_amount_local);
        $this->assertSame('200.00', $new->old_paid_local);
        $this->assertSame('650.00', $new->new_paid_local);
        $this->assertSame('real amount', $new->note);
        $this->assertSame($credit->source_id, $new->credit_source_id);
        $this->assertSame($credit->logicalref, $new->credit_logicalref);

        // The money was taken by the cashier; the admin only corrected the entry.
        $this->assertSame($cashier->id, $new->created_by);
        $this->assertSame('Aman Amanow', $new->created_by_name);
        $this->assertSame($cashier->email, $new->created_by_email);
        $this->assertSame($admin->id, $new->corrected_by);
        $this->assertSame('2026-10-12 14:30:45', $new->corrected_at->format('Y-m-d H:i:s'));
        $this->assertSame('typed wrong', $new->correct_reason);
        $this->assertSame($old->id, $new->corrected_from_payment_id);

        $row = $this->creditRow($credit);
        $this->assertSame('650.00', $row->paid_local);
        $this->assertSame(
            "corrected=yes | corrected_from_payment_id={$old->id}"
            . ' | payment_at=2026-10-12 14:30:45 | entered_at=2026-10-12 14:30:45'
            . ' | method=card | received=450.00 | applied=450.00 | change=0.00'
            . ' | cash=0.00 | card=450.00 | phone=0.00 | total_local=1000.00'
            . ' | old_paid_local=200.00 | new_paid_local=650.00'
            . ' | old_remaining=800.00 | new_remaining=350.00 | note=real amount',
            $row->paid_note
        );

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'payment_corrected',
            'severity' => 'critical',
            'is_suspicious' => true,
            'user_id' => $admin->id,
            'subject_id' => $new->id,
        ]);
        $this->assertDatabaseHas('security_alerts', ['alert_type' => 'payment_corrected', 'risk_level' => 'critical']);
    }

    public function test_reason_is_optional_and_falls_back_to_a_default_on_the_voided_payment(): void
    {
        $credit = $this->credit();
        $old = $this->takePayment($credit, ['pay_amount' => '300']);

        $this->signIn($this->user(UserRoleEnum::ADMIN))->correct($old)->assertSessionHas('success');

        $this->assertSame(
            __('validations/validations.payment_correct.corrected_default_reason'),
            $this->paymentsOf($credit)->first()->void_reason
        );
        $this->assertNull($this->lastPayment($credit)->correct_reason);
    }

    public function test_payment_that_closed_the_credit_can_still_be_corrected(): void
    {
        $credit = $this->credit('1000.00', '700.00');
        $old = $this->takePayment($credit, ['pay_amount' => '300']);
        $this->assertSame('1000.00', $this->creditRow($credit)->paid_local);

        $this->signIn($this->user(UserRoleEnum::ADMIN))->correct($old, ['pay_amount' => '100'])
            ->assertSessionHas('success');

        $this->assertSame('800.00', $this->creditRow($credit)->paid_local);
    }

    public function test_correction_may_be_backdated(): void
    {
        $credit = $this->credit();
        $old = $this->takePayment($credit, ['pay_amount' => '300']);

        $this->signIn($this->user(UserRoleEnum::ADMIN))
            ->correct($old, ['payment_at' => '2026-10-09 11:20:00'])
            ->assertSessionHas('success');

        $new = $this->lastPayment($credit);
        $this->assertSame('2026-10-09 11:20:00', $new->created_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-12 14:30:45', $new->corrected_at->format('Y-m-d H:i:s'));
    }

    public function test_future_date_is_rejected(): void
    {
        $credit = $this->credit();
        $old = $this->takePayment($credit, ['pay_amount' => '300']);

        $this->signIn($this->user(UserRoleEnum::ADMIN))
            ->correct($old, ['payment_at' => '2026-10-13 09:00:00'])
            ->assertSessionHas('warning', __('validations/validations.payment_correct.future_payment_date_not_allowed'));

        $this->assertCount(1, $this->paymentsOf($credit));
        $this->assertNull($this->lastPayment($credit)->voided_at);
    }

    public function test_mixed_parts_must_add_up(): void
    {
        $credit = $this->credit();
        $old = $this->takePayment($credit, ['pay_amount' => '300']);
        $this->signIn($this->user(UserRoleEnum::ADMIN));

        $this->correct($old, ['payment_method' => 'mixed', 'pay_amount' => '200', 'cash_total' => '50', 'card_total' => '100'])
            ->assertSessionHas('warning', __('validations/validations.payment_correct.mixed_sum_must_equal'));
        $this->assertCount(1, $this->paymentsOf($credit));

        $this->correct($old, ['payment_method' => 'mixed', 'pay_amount' => '200', 'cash_total' => '50', 'card_total' => '150'])
            ->assertSessionHas('success');

        $new = $this->lastPayment($credit);
        $this->assertSame('50.00', $new->cash_amount);
        $this->assertSame('150.00', $new->card_amount);
    }

    public function test_phone_correction_does_not_ask_for_a_receiver_number(): void
    {
        $credit = $this->credit();
        $old = $this->takePayment($credit, [
            'payment_method' => 'phone',
            'pay_amount' => '300',
            'receiver_phone_number' => '99361112233',
        ]);

        $this->signIn($this->user(UserRoleEnum::ADMIN))
            ->correct($old, ['payment_method' => 'phone', 'pay_amount' => '250', 'receiver_phone_number' => '99365556677'])
            ->assertSessionHas('success');

        // Today the receiver number is neither required nor carried over to the new payment.
        $new = $this->lastPayment($credit);
        $this->assertSame('phone', $new->method);
        $this->assertSame('250.00', $new->phone_amount);
        $this->assertNull($new->receiver_phone_number);
    }

    public function test_overpayment_rolls_the_whole_correction_back(): void
    {
        $credit = $this->credit('1000.00', '600.00');
        $old = $this->takePayment($credit, ['pay_amount' => '300']);

        $this->signIn($this->user(UserRoleEnum::ADMIN))->correct($old, ['pay_amount' => '500.01'])
            ->assertSessionHas('warning', __('validations/validations.payment_correct.overpayment_too_high', ['max' => '100.00']));

        $this->assertCount(1, $this->paymentsOf($credit));
        $this->assertNull($this->lastPayment($credit)->voided_at);
        $this->assertSame('900.00', $this->creditRow($credit)->paid_local);
        $this->assertDatabaseHas('activity_logs', ['action' => 'payment_correct_failed', 'severity' => 'critical']);

        $this->correct($old, ['pay_amount' => '500'])->assertSessionHas('success');
        $this->assertSame('100.00', $this->lastPayment($credit)->change_amount);
        $this->assertSame('1000.00', $this->creditRow($credit)->paid_local);
    }

    public function test_voided_payment_cannot_be_corrected(): void
    {
        $credit = $this->credit();
        $old = $this->takePayment($credit, ['pay_amount' => '300']);
        $this->signIn($this->user(UserRoleEnum::ADMIN));

        $this->post("/payments/{$old->id}/void", ['void_reason' => 'mistake'])->assertSessionHas('success');

        $this->correct($old)
            ->assertSessionHas('warning', __('validations/validations.payment_correct.already_voided'));

        $this->assertCount(1, $this->paymentsOf($credit));
        $this->assertSame('0.00', $this->creditRow($credit)->paid_local);
    }

    public function test_form_fields_are_validated(): void
    {
        $credit = $this->credit();
        $old = $this->takePayment($credit, ['pay_amount' => '300']);
        $this->signIn($this->user(UserRoleEnum::ADMIN));

        $this->post("/payments/{$old->id}/correct", ['pay_amount' => '0', 'payment_method' => 'crypto'])
            ->assertSessionHasErrors(['payment_method', 'pay_amount']);

        $this->assertCount(1, $this->paymentsOf($credit));
    }

    public function test_payment_of_an_inactive_credit_cannot_be_corrected(): void
    {
        $credit = $this->credit();
        $old = $this->takePayment($credit, ['pay_amount' => '300']);
        DB::table('credits')->where('source_id', $credit->source_id)->update(['active' => false]);

        $this->signIn($this->user(UserRoleEnum::ADMIN))->correct($old)
            ->assertSessionHas('warning', $this->creditNotFoundMessage());

        $this->assertCount(1, $this->paymentsOf($credit));
        $this->assertNull($this->lastPayment($credit)->voided_at);
    }

    public function test_branch_restricted_admin_cannot_correct_a_payment_of_another_branch(): void
    {
        $credit = $this->credit('1000.00', '0.00', ['branch' => 'Lebap']);
        $old = $this->takePayment($credit, ['pay_amount' => '300']);

        $this->signIn($this->user(UserRoleEnum::ADMIN, ['branches' => ['Mary']]))->correct($old)->assertNotFound();

        $this->assertCount(1, $this->paymentsOf($credit));
        $this->assertNull($this->lastPayment($credit)->voided_at);
        $this->assertSame('300.00', $this->creditRow($credit)->paid_local);
    }

    public function test_only_admins_may_correct(): void
    {
        $credit = $this->credit();
        $old = $this->takePayment($credit, ['pay_amount' => '300']);

        foreach ([UserRoleEnum::CASHIER, UserRoleEnum::OPERATOR, UserRoleEnum::ANALYST, UserRoleEnum::MANAGER] as $role) {
            $this->signIn($this->user($role))->correct($old)->assertForbidden();
        }

        $this->assertCount(1, $this->paymentsOf($credit));

        $this->signIn($this->user(UserRoleEnum::SUPER_ADMIN))->correct($old)->assertSessionHas('success');
    }
}
