<?php

namespace Tests\Feature\Api\Auth;

use App\Enums\UserRoleEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MeLogoutApiTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithToken(): User
    {
        return User::create([
            'firstname' => 'Admin',
            'lastname' => 'User',
            'email' => 'admin@example.com',
            'phonenumber' => '99360000010',
            'position' => 'Admin',
            'role' => UserRoleEnum::ADMIN->value,
            'status' => true,
            'password' => Hash::make('secret123'),
            'token' => bin2hex(random_bytes(32)),
            'token_expires_at' => now()->addDay(),
        ]);
    }

    public function test_authenticated_user_can_get_me(): void
    {
        $user = $this->createUserWithToken();

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $user->token)
            ->getJson('/api/v1/auth/me');

        $response
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'user' => [
                        'id',
                        'firstname',
                        'lastname',
                        'full_name',
                        'email',
                        'phonenumber',
                        'position',
                        'role',
                        'status',
                    ],
                    'permissions' => [
                        'can_manage_users',
                        'can_void_payments',
                        'can_correct_payments',
                    ],
                ],
            ]);
    }

    public function test_me_returns_unauthorized_without_token(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error_code' => 'AUTH_TOKEN_MISSING',
            ]);
    }

    public function test_me_returns_unauthorized_with_invalid_token(): void
    {
        $response = $this
            ->withHeader('Authorization', 'Bearer invalid-token')
            ->getJson('/api/v1/auth/me');

        $response
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error_code' => 'AUTH_TOKEN_INVALID',
            ]);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = $this->createUserWithToken();

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $user->token)
            ->postJson('/api/v1/auth/logout');

        $response
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logout successful.',
            ]);

        $user->refresh();

        $this->assertNull($user->token);
        $this->assertNull($user->token_expires_at);
    }

    public function test_logged_out_token_cannot_access_me(): void
    {
        $user = $this->createUserWithToken();
        $token = $user->token;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout')
            ->assertStatus(200);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error_code' => 'AUTH_TOKEN_INVALID',
            ]);
    }
}