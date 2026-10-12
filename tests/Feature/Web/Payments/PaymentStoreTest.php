<?php

namespace Tests\Feature\Web\Payments;

use App\Enums\UserRoleEnum;

class PaymentStoreTest extends PaymentTestCase
{
    public function test_cash_payment_reduces_the_debt_and_records_the_payment(): void
    {
        $cashier = $this->user(UserRoleEnum::CASHIER);
        $credit = $this->credit('1000.00', '200.00');

        $response = $this->signIn($cashier)->pay($credit, ['pay_amount' => '300', 'note' => '  first   part ']);

        $response->assertRedirect(route('payments', ['id' => (string) $credit->source_id]));
        $response->assertSessionHas('success', __('validations/validations.payments.payment_saved'));

        $row = $this->creditRow($credit);
        $this->assertSame('500.00', $row->paid_local);
        $this->assertSame('1000.00', $row->amount_local);
        $this->assertSame($cashier->id, (int) $row->paid_updated_by);
        $this->assertSame('2026-10-12 14:30:45', $row->paid_updated_at);
        $this->assertSame(
            'backdated=no | payment_at=2026-10-12 14:30:45 | entered_at=2026-10-12 14:30:45'
            . ' | method=cash | received=300.00 | applied=300.00 | change=0.00'
            . ' | cash=300.00 | card=0.00 | phone=0.00 | total_local=1000.00'
            . ' | old_paid_local=200.00 | new_paid_local=500.00'
            . ' | old_remaining=800.00 | new_remaining=500.00 | note=first part',
            $row->paid_note
        );

        $this->assertCount(1, $this->paymentsOf($credit));
        $payment = $this->lastPayment($credit);

        $this->assertSame($credit->source_id, $payment->credit_source_id);
        $this->assertSame($credit->logicalref, $payment->credit_logicalref);
        $this->assertSame('cash', $payment->method);
        $this->assertSame('300.00', $payment->pay_amount);
        $this->assertSame('0.00', $payment->change_amount);
        $this->assertSame('300.00', $payment->cash_amount);
        $this->assertSame('0.00', $payment->card_amount);
        $this->assertSame('0.00', $payment->phone_amount);
        $this->assertNull($payment->receiver_phone_number);

        // Despite the names, these two hold the remaining debt before and after.
        $this->assertSame('800.00', $payment->old_amount_local);
        $this->assertSame('500.00', $payment->new_amount_local);
        $this->assertSame('200.00', $payment->old_paid_local);
        $this->assertSame('500.00', $payment->new_paid_local);

        $this->assertSame('first part', $payment->note);
        $this->assertSame('2026-10-12 14:30:45', $payment->created_at->format('Y-m-d H:i:s'));
        $this->assertNull($payment->voided_at);
    }

    public function test_payment_keeps_a_snapshot_of_the_customer_and_the_cashier(): void
    {
        $cashier = $this->user(UserRoleEnum::CASHIER, ['firstname' => 'Aman', 'lastname' => 'Amanow']);
        $credit = $this->credit('1000.00', '0.00', ['branch' => 'Mary']);

        $this->signIn($cashier)->pay($credit)->assertSessionHas('success');

        $payment = $this->lastPayment($credit);

        $this->assertSame($credit->name, $payment->customer_name);
        $this->assertSame($credit->phone, $payment->customer_phone);
        $this->assertSame($credit->passport, $payment->customer_passport);
        $this->assertSame($credit->contract, $payment->customer_contract);
        $this->assertSame('Mary', $payment->branch);

        $this->assertSame($cashier->id, $payment->created_by);
        $this->assertSame('Aman Amanow', $payment->created_by_name);
        $this->assertSame($cashier->email, $payment->created_by_email);
        $this->assertSame($cashier->phonenumber, $payment->created_by_phone);
    }

    public function test_card_payment_is_booked_entirely_as_card(): void
    {
        $credit = $this->credit();

        $this->signIn($this->user())->pay($credit, [
            'payment_method' => 'card',
            'pay_amount' => '250',
            'cash_total' => '999',
        ])->assertSessionHas('success');

        $payment = $this->lastPayment($credit);
        $this->assertSame('card', $payment->method);
        $this->assertSame('0.00', $payment->cash_amount);
        $this->assertSame('250.00', $payment->card_amount);
        $this->assertSame('0.00', $payment->phone_amount);
    }

    public function test_mixed_payment_splits_cash_and_card(): void
    {
        $credit = $this->credit();

        $this->signIn($this->user())->pay($credit, [
            'payment_method' => 'mixed',
            'pay_amount' => '300',
            'cash_total' => '120.50',
            'card_total' => '179.50',
        ])->assertSessionHas('success');

        $payment = $this->lastPayment($credit);
        $this->assertSame('mixed', $payment->method);
        $this->assertSame('120.50', $payment->cash_amount);
        $this->assertSame('179.50', $payment->card_amount);
        $this->assertSame('300.00', $this->creditRow($credit)->paid_local);
    }

    public function test_mixed_payment_is_rejected_when_the_parts_do_not_add_up(): void
    {
        $credit = $this->credit();

        $this->signIn($this->user())->pay($credit, [
            'payment_method' => 'mixed',
            'pay_amount' => '300',
            'cash_total' => '100',
            'card_total' => '150',
        ])->assertSessionHas('warning', __('validations/validations.payments.mixed_sum_must_equal'));

        $this->assertCount(0, $this->paymentsOf($credit));
        $this->assertSame('0.00', $this->creditRow($credit)->paid_local);
    }

    public function test_phone_payment_stores_the_receiver_number_without_spaces(): void
    {
        $credit = $this->credit();

        $this->signIn($this->user())->pay($credit, [
            'payment_method' => 'phone',
            'pay_amount' => '80',
            'receiver_phone_number' => ' 9936 111 2233 ',
        ])->assertSessionHas('success');

        $payment = $this->lastPayment($credit);
        $this->assertSame('phone', $payment->method);
        $this->assertSame('80.00', $payment->phone_amount);
        $this->assertSame('0.00', $payment->cash_amount);
        $this->assertSame('0.00', $payment->card_amount);
        $this->assertSame('99361112233', $payment->receiver_phone_number);
    }

    public function test_phone_payment_requires_a_numeric_receiver_number(): void
    {
        $credit = $this->credit();
        $this->signIn($this->user());

        $this->pay($credit, ['payment_method' => 'phone'])
            ->assertSessionHas('warning', __('pages/payments.form.receiver_phone_number_required'));

        $this->pay($credit, ['payment_method' => 'phone', 'receiver_phone_number' => '+99361112233'])
            ->assertSessionHas('warning', __('pages/payments.form.receiver_phone_number_digits'));

        $this->assertCount(0, $this->paymentsOf($credit));
    }

    public function test_money_above_the_debt_is_returned_as_change_and_closes_the_credit(): void
    {
        $credit = $this->credit('1000.00', '900.00');

        $this->signIn($this->user())->pay($credit, ['pay_amount' => '150'])->assertSessionHas('success');

        $this->assertSame('1000.00', $this->creditRow($credit)->paid_local);

        $payment = $this->lastPayment($credit);
        $this->assertSame('150.00', $payment->pay_amount);
        $this->assertSame('50.00', $payment->change_amount);
        $this->assertSame(100.0, $payment->applied_amount);
        $this->assertSame('100.00', $payment->old_amount_local);
        $this->assertSame('0.00', $payment->new_amount_local);
    }

    public function test_overpayment_is_allowed_up_to_one_hundred_above_the_debt(): void
    {
        $credit = $this->credit('1000.00', '900.00');
        $this->signIn($this->user());

        // As written today the :max placeholder is never substituted; the limit is appended instead.
        $this->pay($credit, ['pay_amount' => '200.01'])
            ->assertSessionHas('warning', __('validations/validations.payments.overpayment_too_high') . '100.00');

        $this->assertCount(0, $this->paymentsOf($credit));
        $this->assertSame('900.00', $this->creditRow($credit)->paid_local);

        $this->pay($credit, ['pay_amount' => '200'])->assertSessionHas('success');

        $this->assertSame('100.00', $this->lastPayment($credit)->change_amount);
    }

    public function test_closed_credit_cannot_take_a_payment(): void
    {
        $credit = $this->credit('1000.00', '999.99');

        $this->signIn($this->user())->pay($credit)
            ->assertSessionHas('warning', __('validations/validations.payments.debt_closed_paid_ge_total'));

        $this->assertCount(0, $this->paymentsOf($credit));
    }

    public function test_credit_without_local_fields_cannot_take_a_payment(): void
    {
        $credit = $this->credit('1000.00', '0.00', ['amount_local' => null, 'paid_local' => null]);

        $this->signIn($this->user())->pay($credit)
            ->assertSessionHas('warning', __('validations/validations.payments.local_debt_missing'));

        $this->assertCount(0, $this->paymentsOf($credit));
    }

    public function test_inactive_credit_cannot_take_a_payment(): void
    {
        $credit = $this->credit('1000.00', '0.00', ['active' => false]);

        $this->signIn($this->user())->pay($credit)
            ->assertSessionHas('warning', $this->creditNotFoundMessage());

        $this->assertCount(0, $this->paymentsOf($credit));
        $this->assertDatabaseHas('activity_logs', ['action' => 'payment_create_failed', 'is_success' => false]);
    }

    public function test_input_is_validated_before_anything_is_written(): void
    {
        $credit = $this->credit();
        $this->signIn($this->user());

        $this->post('/payments', ['payment_method' => 'cash', 'pay_amount' => '10'])
            ->assertSessionHas('warning', __('validations/validations.payments.customer_not_selected'));

        $this->pay($credit, ['payment_method' => 'crypto'])
            ->assertSessionHas('warning', __('validations/validations.payments.method_invalid'));

        $this->pay($credit, ['pay_amount' => '0'])
            ->assertSessionHas('warning', __('validations/validations.payments.pay_amount_zero'));

        $this->pay($credit, ['payment_at' => 'yesterday'])
            ->assertSessionHas('warning', __('validations/validations.payments.invalid_payment_date'));

        $this->assertCount(0, $this->paymentsOf($credit));
    }

    public function test_amounts_typed_with_a_decimal_comma_are_understood(): void
    {
        $credit = $this->credit('5000.00');

        $this->signIn($this->user())->pay($credit, ['pay_amount' => '1.234,50'])->assertSessionHas('success');

        $this->assertSame('1234.50', $this->lastPayment($credit)->pay_amount);
    }

    public function test_future_payment_date_is_rejected(): void
    {
        $credit = $this->credit();

        $this->signIn($this->user())->pay($credit, ['payment_at' => '2026-10-13T09:00'])
            ->assertSessionHas('warning', __('validations/validations.payments.future_payment_date_not_allowed'));

        $this->assertCount(0, $this->paymentsOf($credit));
    }

    public function test_backdated_payment_is_recorded_and_raises_a_high_alert(): void
    {
        $credit = $this->credit();

        $this->signIn($this->user())->pay($credit, ['payment_at' => '2026-10-10T09:15'])
            ->assertSessionHas('success');

        $payment = $this->lastPayment($credit);
        $this->assertSame('2026-10-10 09:15', $payment->created_at->format('Y-m-d H:i'));
        $this->assertStringStartsWith('backdated=yes | payment_at=2026-10-10 09:15', $this->creditRow($credit)->paid_note);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'payment_created',
            'severity' => 'warning',
            'is_suspicious' => true,
        ]);
        $this->assertDatabaseHas('security_alerts', ['alert_type' => 'backdated_payment', 'risk_level' => 'high']);
    }

    public function test_ordinary_payment_is_logged_without_any_alert(): void
    {
        $cashier = $this->user();
        $credit = $this->credit();

        $this->signIn($cashier)->pay($credit)->assertSessionHas('success');

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'payment_created',
            'category' => 'payment',
            'severity' => 'info',
            'is_suspicious' => false,
            'user_id' => $cashier->id,
            'subject_id' => $credit->source_id,
        ]);
        $this->assertDatabaseMissing('security_alerts', ['alert_type' => 'high_amount_payment']);
        $this->assertDatabaseMissing('security_alerts', ['alert_type' => 'backdated_payment']);
    }

    public function test_payment_of_five_thousand_or_more_raises_a_medium_alert(): void
    {
        $credit = $this->credit('20000.00');
        $this->signIn($this->user());

        $this->pay($credit, ['pay_amount' => '4999.99'])->assertSessionHas('success');
        $this->assertDatabaseMissing('security_alerts', ['alert_type' => 'high_amount_payment']);

        $this->pay($credit, ['pay_amount' => '5000'])->assertSessionHas('success');
        $this->assertDatabaseHas('security_alerts', ['alert_type' => 'high_amount_payment', 'risk_level' => 'medium']);
    }

    public function test_branch_restricted_cashier_cannot_pay_a_credit_of_another_branch(): void
    {
        $cashier = $this->user(UserRoleEnum::CASHIER, ['branches' => ['Mary']]);
        $own = $this->credit('1000.00', '0.00', ['branch' => 'Mary']);
        $other = $this->credit('1000.00', '0.00', ['branch' => 'Lebap']);

        $this->signIn($cashier);

        $this->pay($other)->assertSessionHas('warning', $this->creditNotFoundMessage());
        $this->assertCount(0, $this->paymentsOf($other));
        $this->assertSame('0.00', $this->creditRow($other)->paid_local);

        $this->pay($own)->assertSessionHas('success');
        $this->assertCount(1, $this->paymentsOf($own));
    }

    public function test_only_roles_that_take_payments_may_post_one(): void
    {
        $credit = $this->credit();

        $this->pay($credit)->assertRedirect(route('login'));

        foreach ([UserRoleEnum::ANALYST, UserRoleEnum::MANAGER, UserRoleEnum::USER] as $role) {
            $this->signIn($this->user($role))->pay($credit)->assertForbidden();
        }

        foreach ([UserRoleEnum::SUPER_ADMIN, UserRoleEnum::ADMIN, UserRoleEnum::CASHIER, UserRoleEnum::OPERATOR] as $role) {
            $this->signIn($this->user($role))->pay($credit, ['pay_amount' => '10'])->assertSessionHas('success');
        }

        $this->assertCount(4, $this->paymentsOf($credit));
        $this->assertSame('40.00', $this->creditRow($credit)->paid_local);
    }
}
