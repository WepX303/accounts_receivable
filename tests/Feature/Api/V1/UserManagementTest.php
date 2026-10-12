<?php

namespace Tests\Feature\Api\V1;

use App\Enums\UserRoleEnum;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserManagementTest extends ApiTestCase
{
    private function form(array $data = []): array
    {
        return array_merge([
            'firstname' => 'Täze',
            'lastname' => 'Ulanyjy',
            'email' => 'new@example.com',
            'phonenumber' => '99365550000',
            'password' => 'secret123',
            'position' => 'Kassir',
            'role' => 'Cashier',
            'branches' => [],
            'status' => true,
        ], $data);
    }

    public function test_list_shows_users_and_what_may_be_assigned(): void
    {
        $this->credit('1000.00', '0.00', ['branch' => 'Mary']);
        $super = $this->user(UserRoleEnum::SUPER_ADMIN);
        $admin = $this->user(UserRoleEnum::ADMIN);
        $cashier = $this->user(UserRoleEnum::CASHIER, ['branches' => ['Mary'], 'position' => 'Kassir']);

        $response = $this->signIn($super)->getJson('/api/v1/users')->assertOk();

        $this->assertSame([$super->id, $admin->id, $cashier->id], array_column($response->json('data'), 'id'));
        $response->assertJsonPath('data.2.role', 'Cashier')
            ->assertJsonPath('data.2.role_label', 'Kassir')
            ->assertJsonPath('data.2.branches', ['Mary'])
            ->assertJsonPath('data.2.position', 'Kassir')
            ->assertJsonPath('data.2.status', true)
            ->assertJsonPath('data.2.daily_report', false)
            ->assertJsonMissingPath('data.2.password')
            ->assertJsonMissingPath('data.2.token')
            ->assertJsonPath('branches', ['Mary'])
            ->assertJsonPath('meta.total', 3);
        $this->assertContains('SuperAdmin', array_column($response->json('roles'), 'value'));

        // An admin neither sees super admins nor is offered that role.
        $response = $this->signIn($admin)->getJson('/api/v1/users')->assertOk();
        $this->assertSame([$admin->id, $cashier->id], array_column($response->json('data'), 'id'));
        $this->assertNotContains('SuperAdmin', array_column($response->json('roles'), 'value'));
    }

    public function test_admin_creates_a_user(): void
    {
        $this->credit('1000.00', '0.00', ['branch' => 'Mary']);
        $admin = $this->user(UserRoleEnum::ADMIN);

        $this->signIn($admin)->postJson('/api/v1/users', $this->form(['branches' => ['Mary'], 'daily_report' => true]))
            ->assertCreated()
            ->assertJsonPath('message', __('validations/validations.users.created'))
            ->assertJsonPath('data.user.email', 'new@example.com')
            ->assertJsonPath('data.user.role', 'Cashier')
            ->assertJsonPath('data.user.branches', ['Mary'])
            // Only a super admin may switch the nightly report on.
            ->assertJsonPath('data.user.daily_report', false);

        $created = User::where('email', 'new@example.com')->first();
        $this->assertTrue(Hash::check('secret123', $created->password));
        $this->assertDatabaseHas('activity_logs', ['user_id' => $admin->id, 'subject_id' => $created->id, 'category' => 'user']);
    }

    public function test_input_is_validated_like_in_the_panel(): void
    {
        $existing = $this->user(UserRoleEnum::CASHIER, ['email' => 'taken@example.com']);

        $this->signIn($this->user(UserRoleEnum::ADMIN))
            ->postJson('/api/v1/users', $this->form(['email' => 'taken@example.com', 'password' => '123', 'role' => 'Emperor']))
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'validation_failed')
            ->assertJsonStructure(['errors' => ['email', 'password', 'role']]);

        $this->putJson("/api/v1/users/{$existing->id}", $this->form(['firstname' => '']))
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['firstname']]);

        $this->assertSame(2, User::count());
    }

    public function test_an_admin_cannot_reach_above_their_own_level(): void
    {
        $super = $this->user(UserRoleEnum::SUPER_ADMIN);
        $admin = $this->user(UserRoleEnum::ADMIN);
        $this->signIn($admin);

        $this->postJson('/api/v1/users', $this->form(['role' => 'SuperAdmin']))->assertStatus(422);
        $this->assertDatabaseMissing('users', ['email' => 'new@example.com']);

        $this->putJson("/api/v1/users/{$super->id}", $this->form(['email' => $super->email, 'phonenumber' => $super->phonenumber, 'role' => 'Cashier']))
            ->assertStatus(422)
            ->assertJsonPath('message', 'You cannot modify Super Admin user.');
        $this->deleteJson("/api/v1/users/{$super->id}")
            ->assertStatus(422)
            ->assertJsonPath('message', 'You cannot delete Super Admin user.');
        $this->assertSame(UserRoleEnum::SUPER_ADMIN, $super->fresh()->role);

        $cashier = $this->user();
        $this->putJson("/api/v1/users/{$cashier->id}", $this->form(['email' => $cashier->email, 'phonenumber' => $cashier->phonenumber, 'role' => 'SuperAdmin']))
            ->assertStatus(422);
        $this->assertSame(UserRoleEnum::CASHIER, $cashier->fresh()->role);

        $this->deleteJson("/api/v1/users/{$admin->id}")
            ->assertStatus(422)
            ->assertJsonPath('message', 'You cannot delete your own account.');
    }

    public function test_updating_a_user_changes_their_access_at_once(): void
    {
        $this->credit('1000.00', '0.00', ['branch' => 'Lebap']);
        $cashier = $this->user(UserRoleEnum::CASHIER, ['email' => 'c@example.com', 'phonenumber' => '99361112233']);
        $cashierToken = $cashier->createToken('phone')->plainTextToken;
        // No new password: setting one would end the user's sessions, which is tested on its own.
        $keep = ['email' => 'c@example.com', 'phonenumber' => '99361112233', 'password' => null];

        $this->signIn($this->user(UserRoleEnum::SUPER_ADMIN));

        $this->putJson("/api/v1/users/{$cashier->id}", $this->form($keep + ['role' => 'Analyst', 'branches' => ['Lebap'], 'daily_report' => true]))
            ->assertOk()
            ->assertJsonPath('message', __('validations/validations.users.updated'))
            ->assertJsonPath('data.user.role', 'Analyst')
            ->assertJsonPath('data.user.branches', ['Lebap'])
            ->assertJsonPath('data.user.daily_report', true);
        $this->assertDatabaseHas('security_alerts', ['alert_type' => 'user_branches_changed']);

        // A role change needs no new sign-in: the same token now has the analyst's abilities.
        $this->app['auth']->forgetGuards();
        $this->withToken($cashierToken)->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.abilities', fn (array $a) => $a['reports'] === true && $a['payments.take'] === false);

        // Deactivating ends the account's sessions.
        $this->signIn(User::where('role', 'SuperAdmin')->first())
            ->putJson("/api/v1/users/{$cashier->id}", $this->form($keep + ['role' => 'Analyst', 'status' => false]))
            ->assertOk()
            ->assertJsonPath('data.user.status', false);
        $this->assertSame(0, $cashier->tokens()->count());
    }

    public function test_a_password_set_by_an_admin_signs_the_user_out_everywhere(): void
    {
        $cashier = $this->user(UserRoleEnum::CASHIER, ['email' => 'c@example.com', 'phonenumber' => '99361112233']);
        $cashier->createToken('phone');
        $cashier->createToken('tablet');

        $this->signIn($this->user(UserRoleEnum::ADMIN))
            ->putJson("/api/v1/users/{$cashier->id}", $this->form(['email' => 'c@example.com', 'phonenumber' => '99361112233', 'password' => 'brand-new-1']))
            ->assertOk();

        $this->assertTrue(Hash::check('brand-new-1', $cashier->fresh()->password));
        $this->assertSame(0, $cashier->tokens()->count());
    }

    public function test_deleting_a_user_removes_their_tokens(): void
    {
        $admin = $this->user(UserRoleEnum::ADMIN);
        $cashier = $this->user();
        $cashier->createToken('phone');

        $this->signIn($admin)->deleteJson("/api/v1/users/{$cashier->id}")
            ->assertOk()
            ->assertJsonPath('message', __('validations/validations.users.deleted'));

        $this->assertNull(User::find($cashier->id));
        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->deleteJson('/api/v1/users/999999')->assertNotFound();
    }

    public function test_only_admins_manage_users(): void
    {
        $target = $this->user();

        foreach ([UserRoleEnum::CASHIER, UserRoleEnum::OPERATOR, UserRoleEnum::ANALYST, UserRoleEnum::MANAGER, UserRoleEnum::USER] as $role) {
            $this->signIn($this->user($role));
            $this->getJson('/api/v1/users')->assertForbidden();
            $this->postJson('/api/v1/users', $this->form())->assertForbidden();
            $this->putJson("/api/v1/users/{$target->id}", $this->form())->assertForbidden();
            $this->deleteJson("/api/v1/users/{$target->id}")->assertForbidden();
        }

        $this->assertNotNull($target->fresh());
        $this->assertDatabaseMissing('users', ['email' => 'new@example.com']);
    }
}
