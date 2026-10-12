<?php

namespace Tests\Feature\Web\Payments;

use App\Enums\UserRoleEnum;
use App\Models\Credit;
use App\Models\CreditPayment;
use App\Models\User;
use Database\Factories\CreditFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Shared setup for the tests that pin down how the web panel takes, voids and
 * corrects payments today.
 */
abstract class PaymentTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-12 14:30:45'));
    }

    protected function user(UserRoleEnum $role = UserRoleEnum::CASHIER, array $attributes = []): User
    {
        return UserFactory::new()->role($role)->create($attributes);
    }

    /**
     * Signs the user in the way the panel does: a token on the user row and
     * the same token in the auth_token cookie.
     */
    protected function signIn(User $user): static
    {
        $token = bin2hex(random_bytes(32));

        $user->forceFill([
            'token' => $token,
            'token_expires_at' => now()->addDay(),
        ])->save();

        return $this->withCookie('auth_token', $token);
    }

    /**
     * What the cashier sees today when the credit row cannot be found: the
     * framework's own exception text leaks through, because
     * ModelNotFoundException is a RuntimeException and those are shown as is.
     */
    protected function creditNotFoundMessage(): string
    {
        return 'No query results for model [App\\Models\\Credit].';
    }

    protected function credit(string $amount = '1000.00', string $paid = '0.00', array $attributes = []): Credit
    {
        return CreditFactory::new()->debt($amount, $paid)->create($attributes);
    }

    protected function pay(Credit $credit, array $data = []): TestResponse
    {
        return $this->post('/payments', array_merge([
            'customer_id' => $credit->source_id,
            'payment_method' => 'cash',
            'pay_amount' => '100',
        ], $data));
    }

    /**
     * A payment taken by a cashier, for the void and correction tests to act on.
     */
    protected function takePayment(Credit $credit, array $data = [], ?User $cashier = null): CreditPayment
    {
        $this->signIn($cashier ?? $this->user(UserRoleEnum::CASHIER))
            ->pay($credit, $data)
            ->assertSessionHas('success');

        return $this->lastPayment($credit);
    }

    /**
     * Read straight from the table: once a request has run, the signed-in user
     * stays bound and BranchScope would filter model queries in assertions.
     */
    protected function creditRow(Credit $credit): object
    {
        return DB::table('credits')->where('source_id', $credit->source_id)->first();
    }

    protected function paymentsOf(Credit $credit)
    {
        return CreditPayment::withoutGlobalScopes()
            ->where('credit_source_id', $credit->source_id)
            ->orderBy('id')
            ->get();
    }

    protected function lastPayment(Credit $credit): CreditPayment
    {
        return $this->paymentsOf($credit)->last();
    }
}
