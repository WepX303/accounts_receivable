<?php

namespace Tests\Feature\Web\Payments;

use App\Enums\UserRoleEnum;
use App\Models\CreditPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;

class PaymentVoidTest extends PaymentTestCase
{
    private function void(CreditPayment $payment, ?string $reason = 'wrong customer'): TestResponse
    {
        return $this->post("/payments/{$payment->id}/void", ['void_reason' => $reason]);
    }

    public function test_admin_voids_a_payment_and_the_debt_comes_back(): void
    {
        $admin = $this->user(UserRoleEnum::ADMIN, ['firstname' => 'Oraz', 'lastname' => 'Orazow']);
        $credit = $this->credit('1000.00', '200.00');
        $payment = $this->takePayment($credit, ['pay_amount' => '300']);

        $this->signIn($admin)->void($payment, '  wrong    customer ')
            ->assertSessionHas('success', __('validations/validations.payment_void.void_success'));

        $row = $this->creditRow($credit);
        $this->assertSame('200.00', $row->paid_local);
        $this->assertSame($admin->id, (int) $row->paid_updated_by);
        $this->assertSame(
            "voided=yes | voided_payment_id={$payment->id} | voided_applied=300.00"
            . ' | void_reason=wrong customer | voided_by=Oraz Orazow | voided_at=2026-10-12 14:30:45',
            $row->paid_note
        );

        // The payment stays, flagged; its amounts are never rewritten.
        $this->assertCount(1, $this->paymentsOf($credit));
        $voided = $this->lastPayment($credit);
        $this->assertSame('2026-10-12 14:30:45', $voided->voided_at->format('Y-m-d H:i:s'));
        $this->assertSame($admin->id, $voided->voided_by);
        $this->assertSame('wrong customer', $voided->void_reason);
        $this->assertSame('300.00', $voided->pay_amount);
        $this->assertSame('500.00', $voided->new_paid_local);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'payment_voided',
            'severity' => 'critical',
            'is_suspicious' => true,
            'user_id' => $admin->id,
            'subject_id' => $payment->id,
        ]);
        $this->assertDatabaseHas('security_alerts', ['alert_type' => 'payment_voided', 'risk_level' => 'critical']);
    }

    public function test_only_the_applied_part_is_given_back_when_change_was_returned(): void
    {
        $credit = $this->credit('1000.00', '900.00');
        $payment = $this->takePayment($credit, ['pay_amount' => '150']);
        $this->assertSame('1000.00', $this->creditRow($credit)->paid_local);

        $this->signIn($this->user(UserRoleEnum::ADMIN))->void($payment)->assertSessionHas('success');

        $this->assertSame('900.00', $this->creditRow($credit)->paid_local);
    }

    public function test_paid_amount_never_drops_below_zero(): void
    {
        $credit = $this->credit();
        $payment = $this->takePayment($credit, ['pay_amount' => '300']);
        DB::table('credits')->where('source_id', $credit->source_id)->update(['paid_local' => '50.00']);

        $this->signIn($this->user(UserRoleEnum::ADMIN))->void($payment)->assertSessionHas('success');

        $this->assertSame('0.00', $this->creditRow($credit)->paid_local);
    }

    public function test_reason_is_required(): void
    {
        $credit = $this->credit();
        $payment = $this->takePayment($credit, ['pay_amount' => '300']);

        $this->signIn($this->user(UserRoleEnum::ADMIN))->void($payment, '   ')
            ->assertSessionHas('warning', __('validations/validations.payment_void.void_reason_required'));

        $this->assertNull($this->lastPayment($credit)->voided_at);
        $this->assertSame('300.00', $this->creditRow($credit)->paid_local);
    }

    public function test_payment_cannot_be_voided_twice(): void
    {
        $credit = $this->credit('1000.00', '200.00');
        $payment = $this->takePayment($credit, ['pay_amount' => '300']);
        $this->signIn($this->user(UserRoleEnum::ADMIN));

        $this->void($payment)->assertSessionHas('success');
        $this->void($payment)
            ->assertSessionHas('warning', __('validations/validations.payment_void.already_voided'));

        $this->assertSame('200.00', $this->creditRow($credit)->paid_local);
        $this->assertDatabaseHas('activity_logs', ['action' => 'payment_void_failed', 'severity' => 'critical']);
    }

    public function test_payment_of_an_inactive_credit_cannot_be_voided(): void
    {
        $credit = $this->credit();
        $payment = $this->takePayment($credit, ['pay_amount' => '300']);
        DB::table('credits')->where('source_id', $credit->source_id)->update(['active' => false]);

        $this->signIn($this->user(UserRoleEnum::ADMIN))->void($payment)
            ->assertSessionHas('warning', $this->creditNotFoundMessage());

        $this->assertNull($this->lastPayment($credit)->voided_at);
        $this->assertSame('300.00', $this->creditRow($credit)->paid_local);
    }

    public function test_only_admins_may_void(): void
    {
        $credit = $this->credit();
        $payment = $this->takePayment($credit, ['pay_amount' => '300']);

        foreach ([UserRoleEnum::CASHIER, UserRoleEnum::OPERATOR, UserRoleEnum::ANALYST, UserRoleEnum::MANAGER] as $role) {
            $this->signIn($this->user($role))->void($payment)->assertForbidden();
        }

        $this->assertNull($this->lastPayment($credit)->voided_at);

        $this->signIn($this->user(UserRoleEnum::SUPER_ADMIN))->void($payment)->assertSessionHas('success');
    }

    public function test_branch_restricted_admin_cannot_void_a_payment_of_another_branch(): void
    {
        $credit = $this->credit('1000.00', '0.00', ['branch' => 'Lebap']);
        $payment = $this->takePayment($credit, ['pay_amount' => '300']);

        $this->signIn($this->user(UserRoleEnum::ADMIN, ['branches' => ['Mary']]))->void($payment)->assertNotFound();

        $this->assertNull($this->lastPayment($credit)->voided_at);
        $this->assertSame('300.00', $this->creditRow($credit)->paid_local);
    }
}
