<?php

namespace Tests\Feature\Payments;

use App\Models\CreditPayment;
use App\Services\Payments\CreatePaymentData;
use App\Services\Payments\PaymentException;
use App\Services\Payments\PaymentService;
use Database\Factories\CreditFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private function data(int $creditId, ?string $key, float $amount = 300.0): CreatePaymentData
    {
        return new CreatePaymentData(creditSourceId: $creditId, method: 'cash', received: $amount, clientRequestId: $key);
    }

    public function test_the_same_key_books_the_payment_only_once(): void
    {
        $cashier = UserFactory::new()->create();
        $credit = CreditFactory::new()->debt('1000.00')->create();
        $service = app(PaymentService::class);

        $first = $service->create($cashier, $this->data($credit->source_id, 'key-1'));
        $again = $service->create($cashier, $this->data($credit->source_id, 'key-1'));

        $this->assertTrue($first->wasRecentlyCreated);
        $this->assertFalse($again->wasRecentlyCreated);
        $this->assertSame($first->id, $again->id);

        $this->assertSame(1, CreditPayment::count());
        $this->assertSame('300.00', DB::table('credits')->where('source_id', $credit->source_id)->value('paid_local'));
        $this->assertSame(1, DB::table('activity_logs')->where('action', 'payment_created')->count());
    }

    public function test_the_key_still_answers_after_the_payment_closed_the_credit(): void
    {
        $cashier = UserFactory::new()->create();
        $credit = CreditFactory::new()->debt('1000.00', '700.00')->create();
        $service = app(PaymentService::class);

        $first = $service->create($cashier, $this->data($credit->source_id, 'key-1'));
        $again = $service->create($cashier, $this->data($credit->source_id, 'key-1'));

        $this->assertSame($first->id, $again->id);
        $this->assertSame(1, CreditPayment::count());
    }

    public function test_a_key_cannot_be_reused_for_another_credit_or_by_another_user(): void
    {
        $cashier = UserFactory::new()->create();
        $credit = CreditFactory::new()->debt('1000.00')->create();
        $other = CreditFactory::new()->debt('1000.00')->create();
        $service = app(PaymentService::class);

        $service->create($cashier, $this->data($credit->source_id, 'key-1'));

        foreach ([[$cashier, $other], [UserFactory::new()->create(), $credit]] as [$user, $target]) {
            try {
                $service->create($user, $this->data($target->source_id, 'key-1'));
                $this->fail('The reused key was accepted.');
            } catch (PaymentException $e) {
                $this->assertSame('idempotency_key_reused', $e->errorCode);
            }
        }

        $this->assertSame(1, CreditPayment::count());
        $this->assertSame('0.00', DB::table('credits')->where('source_id', $other->source_id)->value('paid_local'));
    }

    public function test_payments_without_a_key_are_never_merged(): void
    {
        $cashier = UserFactory::new()->create();
        $credit = CreditFactory::new()->debt('1000.00')->create();
        $service = app(PaymentService::class);

        $service->create($cashier, $this->data($credit->source_id, null, 100.0));
        $service->create($cashier, $this->data($credit->source_id, null, 100.0));

        $this->assertSame(2, CreditPayment::count());
        $this->assertSame('200.00', DB::table('credits')->where('source_id', $credit->source_id)->value('paid_local'));
    }
}
