<?php

namespace Tests\Feature\Web\Auth;

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pins down how the panel signs people in and out today.
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $attributes = []): User
    {
        return UserFactory::new()->create(array_merge([
            'email' => 'aman@example.com',
            'phonenumber' => '99361112233',
        ], $attributes));
    }

    public function test_login_with_email_sets_the_cookie_and_records_it(): void
    {
        $user = $this->user();

        $response = $this->post('/login', ['login' => 'aman@example.com', 'password' => 'password']);

        $user->refresh();
        $this->assertSame(64, strlen($user->token));
        $this->assertTrue($user->token_expires_at->between(now()->addHours(23), now()->addHours(25)));

        $response->assertRedirect(route('dashboard'));
        $response->assertCookie('auth_token', $user->token);

        $this->assertDatabaseHas('login_histories', [
            'user_id' => $user->id,
            'login_value' => 'aman@example.com',
            'status' => 'success',
            'is_suspicious' => false,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'login_success',
            'category' => 'auth',
            'user_id' => $user->id,
            'severity' => 'info',
        ]);
        $this->assertDatabaseCount('security_alerts', 0);
    }

    public function test_login_with_phone_number_works_too(): void
    {
        $this->user();

        $this->post('/login', ['login' => '99361112233', 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
    }

    public function test_unknown_login_is_refused_with_a_message_that_fits_what_was_typed(): void
    {
        $this->post('/login', ['login' => 'nobody@example.com', 'password' => 'x'])
            ->assertSessionHasErrors(['login' => __('validations/validations.auth.email_not_found')]);

        $this->post('/login', ['login' => '99360000000', 'password' => 'x'])
            ->assertSessionHasErrors(['login' => __('validations/validations.auth.phone_not_found')]);

        $this->post('/login', ['login' => 'nobody', 'password' => 'x'])
            ->assertSessionHasErrors(['login' => __('validations/validations.auth.invalid_login')]);

        $this->assertDatabaseHas('login_histories', [
            'user_id' => null,
            'login_value' => 'nobody',
            'status' => 'failed',
            'fail_reason' => 'user_not_found',
            'is_suspicious' => true,
        ]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'login_failed', 'is_success' => false, 'severity' => 'warning']);
        $this->assertDatabaseHas('security_alerts', ['alert_type' => 'login_failed_user_not_found', 'risk_level' => 'medium']);
    }

    public function test_wrong_password_is_refused_and_raises_an_alert(): void
    {
        $user = $this->user();

        $this->post('/login', ['login' => 'aman@example.com', 'password' => 'nope'])
            ->assertSessionHasErrors(['password' => __('validations/validations.auth.wrong_password')])
            ->assertCookieMissing('auth_token');

        $this->assertNull($user->refresh()->token);
        $this->assertDatabaseHas('login_histories', ['user_id' => $user->id, 'status' => 'failed', 'fail_reason' => 'wrong_password']);
        $this->assertDatabaseHas('security_alerts', [
            'alert_type' => 'wrong_password_attempt',
            'risk_level' => 'medium',
            'user_id' => $user->id,
        ]);
    }

    public function test_inactive_account_is_refused_even_with_the_right_password(): void
    {
        $user = $this->user(['status' => false]);

        $this->post('/login', ['login' => 'aman@example.com', 'password' => 'password'])
            ->assertSessionHasErrors(['login' => __('validations/validations.auth.account_inactive')]);

        $this->assertNull($user->refresh()->token);
        $this->assertDatabaseHas('login_histories', ['user_id' => $user->id, 'status' => 'failed', 'fail_reason' => 'inactive_account']);
        $this->assertDatabaseHas('security_alerts', [
            'alert_type' => 'inactive_account_login_attempt',
            'risk_level' => 'high',
            'user_id' => $user->id,
        ]);
    }

    public function test_both_fields_are_required(): void
    {
        $this->post('/login', [])->assertSessionHasErrors([
            'login' => __('validations/validations.auth.login_required'),
            'password' => __('validations/validations.auth.password_required'),
        ]);
    }

    public function test_logout_drops_the_token_and_records_it(): void
    {
        $user = $this->user();
        $this->post('/login', ['login' => 'aman@example.com', 'password' => 'password']);
        $token = $user->refresh()->token;

        $this->withCookie('auth_token', $token)->post('/logout')->assertRedirect(route('login'));

        $this->assertNull($user->refresh()->token);
        $this->assertNull($user->token_expires_at);
        $this->assertDatabaseHas('login_histories', [
            'user_id' => $user->id,
            'login_value' => 'aman@example.com',
            'status' => 'logout',
        ]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'logout', 'category' => 'auth', 'user_id' => $user->id]);

        $this->withCookie('auth_token', $token)->get('/')->assertRedirect(route('login'));
    }
}
