<?php

namespace Tests\Feature\Web\Profile;

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Pins down how the panel's profile form behaves today.
 */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = UserFactory::new()->create([
            'firstname' => 'Aman',
            'lastname' => 'Amanow',
            'email' => 'aman@example.com',
            'phonenumber' => '99361112233',
            'token' => str_repeat('a', 64),
            'token_expires_at' => now()->addDay(),
        ]);

        $this->withCookie('auth_token', $this->user->token);
    }

    private function save(array $data = [])
    {
        return $this->post('/profile', array_merge([
            'firstname' => 'Aman',
            'lastname' => 'Amanow',
            'email' => 'aman@example.com',
            'phonenumber' => '99361112233',
        ], $data));
    }

    public function test_details_are_updated_and_logged(): void
    {
        $this->save(['firstname' => 'Amanmyrat', 'phonenumber' => '99365554433'])
            ->assertSessionHas('success', __('validations/validations.profile.profile_updated'));

        $this->user->refresh();
        $this->assertSame('Amanmyrat', $this->user->firstname);
        $this->assertSame('99365554433', $this->user->phonenumber);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'profile_updated',
            'category' => 'profile',
            'user_id' => $this->user->id,
            'severity' => 'info',
            'is_suspicious' => false,
        ]);
        $this->assertDatabaseCount('security_alerts', 0);
    }

    public function test_nothing_is_saved_or_logged_when_nothing_changed(): void
    {
        $this->save()->assertSessionHas('info', __('validations/validations.profile.no_changes'));

        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_password_changes_with_the_old_one_and_raises_an_alert(): void
    {
        $this->save(['old_password' => 'password', 'new_password' => 'secret123', 'new_password_confirmation' => 'secret123'])
            ->assertSessionHas('success', __('validations/validations.profile.password_updated'));

        $this->assertTrue(Hash::check('secret123', $this->user->refresh()->password));
        $this->assertDatabaseHas('activity_logs', ['action' => 'profile_updated', 'severity' => 'warning', 'is_suspicious' => true]);
        $this->assertDatabaseHas('security_alerts', ['alert_type' => 'password_changed', 'risk_level' => 'medium', 'user_id' => $this->user->id]);
    }

    public function test_password_is_not_changed_without_the_right_old_one(): void
    {
        $new = ['new_password' => 'secret123', 'new_password_confirmation' => 'secret123'];

        $this->save($new)
            ->assertSessionHasErrors(['old_password' => __('validations/validations.profile.old_password_required_for_change')]);
        $this->save($new + ['old_password' => 'nope'])
            ->assertSessionHasErrors(['old_password' => __('validations/validations.profile.old_password_incorrect')]);
        $this->save(['old_password' => 'password', 'new_password' => 'secret123', 'new_password_confirmation' => 'other'])
            ->assertSessionHasErrors(['new_password' => __('validations/validations.profile.new_password_confirmed')]);
        $this->save(['old_password' => 'password', 'new_password' => '12345', 'new_password_confirmation' => '12345'])
            ->assertSessionHasErrors(['new_password' => __('validations/validations.profile.new_password_min', ['min' => 6])]);

        $this->assertTrue(Hash::check('password', $this->user->refresh()->password));
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_email_and_phone_must_stay_unique(): void
    {
        UserFactory::new()->create(['email' => 'taken@example.com', 'phonenumber' => '99360000000']);

        $this->save(['email' => 'taken@example.com', 'phonenumber' => '99360000000'])->assertSessionHasErrors([
            'email' => __('validations/validations.profile.email_unique'),
            'phonenumber' => __('validations/validations.profile.phonenumber_unique'),
        ]);

        $this->save(['firstname' => '', 'email' => 'not-an-email'])->assertSessionHasErrors(['firstname', 'email']);

        $this->assertSame('aman@example.com', $this->user->refresh()->email);
    }
}
