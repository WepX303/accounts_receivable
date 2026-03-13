<?php

namespace Tests\Feature\Api\Profile;

use App\Enums\UserRoleEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileApiTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithToken(
        string $email = 'profile@example.com',
        string $phone = '99360000400'
    ): User {
        return User::create([
            'firstname' => 'Profile',
            'lastname' => 'User',
            'email' => $email,
            'phonenumber' => $phone,
            'position' => 'Tester',
            'role' => UserRoleEnum::ADMIN->value,
            'status' => true,
            'password' => Hash::make('secret123'),
            'token' => bin2hex(random_bytes(32)),
            'token_expires_at' => now()->addDay(),
        ]);
    }

    public function test_authenticated_user_can_get_profile(): void
    {
        $user = $this->createUserWithToken();

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $user->token)
            ->getJson('/api/v1/profile');

        $response
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Profile fetched successfully.',
            ])
            ->assertJsonStructure([
                'data' => [
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
            ]);
    }

    public function test_authenticated_user_can_update_profile(): void
    {
        $user = $this->createUserWithToken();

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $user->token)
            ->putJson('/api/v1/profile', [
                'firstname' => 'Updated',
                'lastname' => 'Person',
                'email' => 'updated-profile@example.com',
                'phonenumber' => '99360000401',
            ]);

        $response
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Profile updated successfully.',
            ])
            ->assertJsonPath('data.firstname', 'Updated')
            ->assertJsonPath('data.lastname', 'Person')
            ->assertJsonPath('data.email', 'updated-profile@example.com')
            ->assertJsonPath('data.phonenumber', '99360000401');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'firstname' => 'Updated',
            'lastname' => 'Person',
            'email' => 'updated-profile@example.com',
            'phonenumber' => '99360000401',
        ]);
    }

    public function test_profile_update_returns_no_changes_message(): void
    {
        $user = $this->createUserWithToken();

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $user->token)
            ->putJson('/api/v1/profile', [
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'email' => $user->email,
                'phonenumber' => $user->phonenumber,
            ]);

        $response
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'No changes detected.',
            ]);
    }

    public function test_authenticated_user_can_change_password(): void
    {
        $user = $this->createUserWithToken();

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $user->token)
            ->postJson('/api/v1/profile/change-password', [
                'old_password' => 'secret123',
                'new_password' => 'newsecret123',
                'new_password_confirmation' => 'newsecret123',
            ]);

        $response
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Password updated successfully.',
            ]);

        $user->refresh();

        $this->assertTrue(Hash::check('newsecret123', $user->password));
    }

    public function test_change_password_fails_with_wrong_old_password(): void
    {
        $user = $this->createUserWithToken();

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $user->token)
            ->postJson('/api/v1/profile/change-password', [
                'old_password' => 'wrong-old-password',
                'new_password' => 'newsecret123',
                'new_password_confirmation' => 'newsecret123',
            ]);

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error_code' => 'OLD_PASSWORD_INCORRECT',
            ]);
    }

    public function test_profile_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/profile')
            ->assertStatus(401);

        $this->putJson('/api/v1/profile', [
            'firstname' => 'Test',
            'lastname' => 'User',
            'email' => 'test@example.com',
            'phonenumber' => '99360000402',
        ])->assertStatus(401);

        $this->postJson('/api/v1/profile/change-password', [
            'old_password' => 'secret123',
            'new_password' => 'newsecret123',
            'new_password_confirmation' => 'newsecret123',
        ])->assertStatus(401);
    }
}