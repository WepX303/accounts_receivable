<?php

namespace Tests\Feature\Api\Users;

use App\Enums\UserRoleEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithToken(
        string $role = 'Admin',
        string $email = 'admin@example.com',
        string $phone = '99360000300'
    ): User {
        return User::create([
            'firstname' => 'System',
            'lastname' => 'User',
            'email' => $email,
            'phonenumber' => $phone,
            'position' => 'Tester',
            'role' => $role,
            'status' => true,
            'password' => Hash::make('secret123'),
            'token' => bin2hex(random_bytes(32)),
            'token_expires_at' => now()->addDay(),
        ]);
    }

    public function test_admin_can_get_users_list(): void
    {
        $admin = $this->createUserWithToken(
            UserRoleEnum::ADMIN->value,
            'admin1@example.com',
            '99360000301'
        );

        User::create([
            'firstname' => 'User',
            'lastname' => 'One',
            'email' => 'user1@example.com',
            'phonenumber' => '99360000302',
            'position' => 'Cashier',
            'role' => UserRoleEnum::CASHIER->value,
            'status' => true,
            'password' => Hash::make('secret123'),
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $admin->token)
            ->getJson('/api/v1/users');

        $response
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'users' => [
                        '*' => [
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
                    ],
                    'allowed_roles',
                ],
                'meta' => [
                    'pagination' => [
                        'current_page',
                        'last_page',
                        'per_page',
                        'total',
                        'from',
                        'to',
                        'has_more_pages',
                    ],
                ],
            ]);
    }

    public function test_non_admin_cannot_get_users_list(): void
    {
        $cashier = $this->createUserWithToken(
            UserRoleEnum::CASHIER->value,
            'cashier1@example.com',
            '99360000303'
        );

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $cashier->token)
            ->getJson('/api/v1/users');

        $response
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error_code' => 'ROLE_FORBIDDEN',
            ]);
    }

    public function test_admin_can_create_user(): void
    {
        $admin = $this->createUserWithToken(
            UserRoleEnum::ADMIN->value,
            'admin2@example.com',
            '99360000304'
        );

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $admin->token)
            ->postJson('/api/v1/users', [
                'firstname' => 'New',
                'lastname' => 'User',
                'email' => 'newuser@example.com',
                'phonenumber' => '99360000305',
                'password' => 'secret123',
                'role' => UserRoleEnum::CASHIER->value,
                'position' => 'Cashier',
                'status' => true,
            ]);

        $response
            ->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'User created successfully.',
            ])
            ->assertJsonPath('data.email', 'newuser@example.com')
            ->assertJsonPath('data.role', UserRoleEnum::CASHIER->value);

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'role' => UserRoleEnum::CASHIER->value,
        ]);
    }

    public function test_admin_cannot_create_super_admin_user(): void
    {
        $admin = $this->createUserWithToken(
            UserRoleEnum::ADMIN->value,
            'admin3@example.com',
            '99360000306'
        );

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $admin->token)
            ->postJson('/api/v1/users', [
                'firstname' => 'Super',
                'lastname' => 'Admin',
                'email' => 'superadmin@example.com',
                'phonenumber' => '99360000307',
                'password' => 'secret123',
                'role' => UserRoleEnum::SUPER_ADMIN->value,
                'position' => 'Super Admin',
                'status' => true,
            ]);

        $response
            ->assertStatus(422);
    }

    public function test_super_admin_can_create_super_admin_user(): void
    {
        $superAdmin = $this->createUserWithToken(
            UserRoleEnum::SUPER_ADMIN->value,
            'superadmin1@example.com',
            '99360000308'
        );

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $superAdmin->token)
            ->postJson('/api/v1/users', [
                'firstname' => 'Second',
                'lastname' => 'Super',
                'email' => 'superadmin2@example.com',
                'phonenumber' => '99360000309',
                'password' => 'secret123',
                'role' => UserRoleEnum::SUPER_ADMIN->value,
                'position' => 'Super Admin',
                'status' => true,
            ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('data.role', UserRoleEnum::SUPER_ADMIN->value);
    }

    public function test_admin_can_update_user(): void
    {
        $admin = $this->createUserWithToken(
            UserRoleEnum::ADMIN->value,
            'admin4@example.com',
            '99360000310'
        );

        $user = User::create([
            'firstname' => 'Old',
            'lastname' => 'Name',
            'email' => 'oldname@example.com',
            'phonenumber' => '99360000311',
            'position' => 'Cashier',
            'role' => UserRoleEnum::CASHIER->value,
            'status' => true,
            'password' => Hash::make('secret123'),
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $admin->token)
            ->putJson('/api/v1/users/' . $user->id, [
                'firstname' => 'Updated',
                'lastname' => 'User',
                'email' => 'updated@example.com',
                'phonenumber' => '99360000312',
                'role' => UserRoleEnum::MANAGER->value,
                'position' => 'Manager',
                'status' => false,
            ]);

        $response
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'User updated successfully.',
            ])
            ->assertJsonPath('data.email', 'updated@example.com')
            ->assertJsonPath('data.role', UserRoleEnum::MANAGER->value)
            ->assertJsonPath('data.status', false);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'updated@example.com',
            'role' => UserRoleEnum::MANAGER->value,
            'status' => false,
        ]);
    }

    public function test_admin_cannot_update_super_admin_user(): void
    {
        $admin = $this->createUserWithToken(
            UserRoleEnum::ADMIN->value,
            'admin5@example.com',
            '99360000313'
        );

        $superAdminUser = User::create([
            'firstname' => 'Main',
            'lastname' => 'Super',
            'email' => 'mainsuper@example.com',
            'phonenumber' => '99360000314',
            'position' => 'Super Admin',
            'role' => UserRoleEnum::SUPER_ADMIN->value,
            'status' => true,
            'password' => Hash::make('secret123'),
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $admin->token)
            ->putJson('/api/v1/users/' . $superAdminUser->id, [
                'firstname' => 'Blocked',
                'lastname' => 'Update',
                'email' => 'blocked@example.com',
                'phonenumber' => '99360000315',
                'role' => UserRoleEnum::ADMIN->value,
                'position' => 'Admin',
                'status' => true,
            ]);

        $response
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error_code' => 'SUPER_ADMIN_MODIFY_FORBIDDEN',
            ]);
    }

    public function test_admin_can_delete_user(): void
    {
        $admin = $this->createUserWithToken(
            UserRoleEnum::ADMIN->value,
            'admin6@example.com',
            '99360000316'
        );

        $user = User::create([
            'firstname' => 'Delete',
            'lastname' => 'Me',
            'email' => 'deleteme@example.com',
            'phonenumber' => '99360000317',
            'position' => 'Cashier',
            'role' => UserRoleEnum::CASHIER->value,
            'status' => true,
            'password' => Hash::make('secret123'),
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $admin->token)
            ->deleteJson('/api/v1/users/' . $user->id);

        $response
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'User deleted successfully.',
            ]);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    public function test_user_cannot_delete_own_account(): void
    {
        $admin = $this->createUserWithToken(
            UserRoleEnum::ADMIN->value,
            'admin7@example.com',
            '99360000318'
        );

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $admin->token)
            ->deleteJson('/api/v1/users/' . $admin->id);

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error_code' => 'SELF_DELETE_FORBIDDEN',
            ]);
    }

    public function test_user_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/users')
            ->assertStatus(401);

        $this->postJson('/api/v1/users', [])
            ->assertStatus(401);
    }
}