<?php

namespace Tests\Feature\Api\V1;

use App\Enums\UserRoleEnum;
use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
