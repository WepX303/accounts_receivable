<?php

namespace Tests\Feature\Api\V1;

use App\Enums\UserRoleEnum;
use App\Models\Credit;
use App\Models\User;
use Database\Factories\CreditFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The test client would otherwise announce English on its own.
        $this->withHeader('Accept-Language', 'tk');
    }

    protected function user(UserRoleEnum $role = UserRoleEnum::CASHIER, array $attributes = []): User
    {
        return UserFactory::new()->role($role)->create($attributes);
    }

    protected function credit(string $amount = '1000.00', string $paid = '0.00', array $attributes = []): Credit
    {
        return CreditFactory::new()->debt($amount, $paid)->create($attributes);
    }

    protected function creditRow(Credit $credit): object
    {
        return DB::table('credits')->where('source_id', $credit->source_id)->first();
    }

    /**
     * Signs in with a real token, the way the app does, rather than faking the guard.
     */
    protected function signIn(User $user): static
    {
        // The guard remembers the user it resolved; without this a second
        // sign-in within one test would keep acting as the first user.
        $this->app['auth']->forgetGuards();

        return $this->withToken($user->createToken('test')->plainTextToken);
    }
}
