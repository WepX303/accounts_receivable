<?php

namespace Tests\Feature\Api\V1;

use App\Enums\UserRoleEnum;
use Illuminate\Support\Facades\Hash;

class ProfileTest extends ApiTestCase
{
    private function details(array $data = []): array
    {
        return array_merge([
            'firstname' => 'Aman',
            'lastname' => 'Amanow',
            'email' => 'aman@example.com',
            'phonenumber' => '99361112233',
        ], $data);
    }

    public function test_user_updates_their_own_details(): void
    {
        $user = $this->user(UserRoleEnum::CASHIER, $this->details());

        $this->signIn($user)->putJson('/api/v1/profile', $this->details(['firstname' => 'Amanmyrat']))
            ->assertOk()
            ->assertJsonPath('message', __('validations/validations.profile.profile_updated'))
            ->assertJsonPath('data.user.firstname', 'Amanmyrat')
            ->assertJsonPath('data.user.full_name', 'Amanmyrat Amanow');

        $this->assertDatabaseHas('activity_logs', ['action' => 'profile_updated', 'user_id' => $user->id]);

        $this->putJson('/api/v1/profile', $this->details(['firstname' => 'Amanmyrat']))
            ->assertOk()
            ->assertJsonPath('message', __('validations/validations.profile.no_changes'));
    }

    public function test_role_branches_and_status_cannot_be_changed_through_the_profile(): void
    {
        $user = $this->user(UserRoleEnum::CASHIER, $this->details(['branches' => ['Mary']]));

        $this->signIn($user)->putJson('/api/v1/profile', $this->details([
            'firstname' => 'Changed',
            'role' => 'SuperAdmin',
            'branches' => [],
            'status' => false,
            'daily_report' => true,
            'password' => 'injected123',
        ]))->assertOk()->assertJsonPath('data.user.role', 'Cashier');

        $user->refresh();
        $this->assertSame(UserRoleEnum::CASHIER, $user->role);
        $this->assertSame(['Mary'], $user->branches);
        $this->assertTrue((bool) $user->status);
        $this->assertFalse($user->daily_report);
        $this->assertTrue(Hash::check('password', $user->password));
    }

    public function test_changing_the_password_needs_the_old_one_and_signs_other_devices_out(): void
    {
        $user = $this->user(UserRoleEnum::CASHIER, $this->details());
        $user->createToken('tablet');
        $this->signIn($user);

        $new = ['new_password' => 'secret123', 'new_password_confirmation' => 'secret123'];

        $this->putJson('/api/v1/profile', $this->details($new))
            ->assertStatus(422)
            ->assertJsonPath('errors.old_password.0', __('validations/validations.profile.old_password_required_for_change'));
        $this->putJson('/api/v1/profile', $this->details($new + ['old_password' => 'nope']))
            ->assertStatus(422)
            ->assertJsonPath('errors.old_password.0', __('validations/validations.profile.old_password_incorrect'));
        $this->assertSame(2, $user->tokens()->count());

        $this->putJson('/api/v1/profile', $this->details($new + ['old_password' => 'password']))
            ->assertOk()
            ->assertJsonPath('message', __('validations/validations.profile.password_updated'));

        $this->assertTrue(Hash::check('secret123', $user->refresh()->password));
        $this->assertSame(['test'], $user->tokens()->pluck('name')->all());
        $this->assertDatabaseHas('security_alerts', ['alert_type' => 'password_changed', 'user_id' => $user->id]);

        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1/auth/me')->assertOk();
    }

    public function test_email_and_phone_must_stay_unique(): void
    {
        $this->user(UserRoleEnum::CASHIER, ['email' => 'taken@example.com', 'phonenumber' => '99360000000']);
        $user = $this->user(UserRoleEnum::CASHIER, $this->details());

        $this->signIn($user)->putJson('/api/v1/profile', $this->details(['email' => 'taken@example.com', 'phonenumber' => '99360000000']))
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', __('validations/validations.profile.email_unique'))
            ->assertJsonPath('errors.phonenumber.0', __('validations/validations.profile.phonenumber_unique'));
    }

    public function test_guest_cannot_reach_it(): void
    {
        $this->putJson('/api/v1/profile', $this->details())->assertStatus(401);
    }
}
