<?php

namespace Tests\Feature\Api\Auth;

use App\Enums\UserRoleEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_email(): void
    {
        $user = User::create([
            'firstname' => 'Admin',
            'lastname' => 'User',
            'email' => 'admin@example.com',
            'phonenumber' => '99360000001',
            'position' => 'Admin',
            'role' => UserRoleEnum::ADMIN->value,
            'status' => true,
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'admin@example.com',
            'password' => 'secret123',
        ]);

        $response
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login successful.',
            ])
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'token_type',
                    'expires_at',
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

        $this->assertNotNull($user->fresh()->token);
        $this->assertNotNull($user->fresh()->token_expires_at);
    }

    public function test_user_can_login_with_phone(): void
    {
        User::create([
            'firstname' => 'Cashier',
            'lastname' => 'User',
            'email' => 'cashier@example.com',
            'phonenumber' => '99360000002',
            'position' => 'Cashier',
            'role' => UserRoleEnum::CASHIER->value,
            'status' => true,
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => '99360000002',
            'password' => 'secret123',
        ]);

        $response
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::create([
            'firstname' => 'Admin',
            'lastname' => 'User',
            'email' => 'admin@example.com',
            'phonenumber' => '99360000003',
            'position' => 'Admin',
            'role' => UserRoleEnum::ADMIN->value,
            'status' => true,
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'admin@example.com',
            'password' => 'wrongpass',
        ]);

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error_code' => 'INVALID_CREDENTIALS',
            ]);
    }

    public function test_login_fails_for_inactive_user(): void
    {
        User::create([
            'firstname' => 'Inactive',
            'lastname' => 'User',
            'email' => 'inactive@example.com',
            'phonenumber' => '99360000004',
            'position' => 'Manager',
            'role' => UserRoleEnum::MANAGER->value,
            'status' => false,
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'inactive@example.com',
            'password' => 'secret123',
        ]);

        $response
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error_code' => 'ACCOUNT_INACTIVE',
            ]);
    }

    public function test_login_validation_errors_are_returned_as_json(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error_code' => 'VALIDATION_ERROR',
            ])
            ->assertJsonStructure([
                'errors' => [
                    'login',
                    'password',
                ],
            ]);
    }
}