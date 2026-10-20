<?php

namespace Tests\Feature\Api\V1;

use App\Enums\UserRoleEnum;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class AuthTest extends ApiTestCase
{
    private function login(array $data = [])
    {
        return $this->postJson('/api/v1/auth/login', array_merge([
            'login' => 'aman@example.com',
            'password' => 'password',
        ], $data));
    }

    public function test_login_returns_a_token_the_user_and_their_abilities(): void
    {
        $user = $this->user(UserRoleEnum::CASHIER, [
            'email' => 'aman@example.com',
            'firstname' => 'Aman',
            'lastname' => 'Amanow',
            'branches' => ['Mary'],
        ]);

        $response = $this->login(['device_name' => 'Pixel 8'])->assertOk();

        $response->assertJsonPath('success', true)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.full_name', 'Aman Amanow')
            ->assertJsonPath('data.user.role', 'Cashier')
            ->assertJsonPath('data.user.role_label', 'Kassir')
            ->assertJsonPath('data.user.branches', ['Mary'])
            ->assertJsonPath('data.abilities', [
                'customers.info' => true,
                'payments.take' => true,
                'payments.void' => false,
                'payments.correct' => false,
                'customers.list' => false,
                'reports.avshocrecat' => true,
                'reports' => false,
                'sms' => false,
                'users' => false,
                'logs' => false,
                'commands' => false,
                'contracts.create' => true,
                'contracts.approve' => false,
                'contracts.cancel' => false,
            ])
            ->assertJsonMissingPath('data.user.password')
            ->assertJsonMissingPath('data.user.token');

        $this->assertNotNull($response->json('data.expires_at'));
        $this->assertSame('Pixel 8', PersonalAccessToken::first()->name);

        $this->withToken($response->json('data.token'))->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->id);

        $this->assertDatabaseHas('login_histories', ['user_id' => $user->id, 'status' => 'success']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'login_success', 'user_id' => $user->id]);
    }

    public function test_mobile_login_leaves_the_panel_session_alone(): void
    {
        $user = $this->user(UserRoleEnum::CASHIER, ['email' => 'aman@example.com']);
        $user->forceFill(['token' => str_repeat('a', 64), 'token_expires_at' => now()->addDay()])->save();

        $this->login()->assertOk();

        $this->assertSame(str_repeat('a', 64), $user->refresh()->token);
        $this->withCookie('auth_token', str_repeat('a', 64))->get('/profile')->assertOk();
    }

    public function test_refused_login_does_not_reveal_whether_the_account_exists(): void
    {
        $user = $this->user(UserRoleEnum::CASHIER, ['email' => 'aman@example.com']);

        $wrongPassword = $this->login(['password' => 'nope'])->assertStatus(422);
        $unknownLogin = $this->login(['login' => 'nobody@example.com'])->assertStatus(422);

        $wrongPassword->assertJsonPath('error_code', 'invalid_credentials')
            ->assertJsonPath('message', __('api.invalid_credentials'));
        $this->assertSame($wrongPassword->json(), $unknownLogin->json());

        $this->assertDatabaseCount('personal_access_tokens', 0);

        // The panel's log still tells the two apart.
        $this->assertDatabaseHas('login_histories', ['user_id' => $user->id, 'fail_reason' => 'wrong_password']);
        $this->assertDatabaseHas('login_histories', ['login_value' => 'nobody@example.com', 'fail_reason' => 'user_not_found']);
        $this->assertDatabaseHas('security_alerts', ['alert_type' => 'wrong_password_attempt', 'user_id' => $user->id]);
    }

    public function test_inactive_account_cannot_sign_in(): void
    {
        $this->user(UserRoleEnum::CASHIER, ['email' => 'aman@example.com', 'status' => false]);

        $this->login()->assertStatus(403)->assertJsonPath('error_code', 'account_inactive');

        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertDatabaseHas('security_alerts', ['alert_type' => 'inactive_account_login_attempt', 'risk_level' => 'high']);
    }

    public function test_signing_in_clears_the_users_expired_tokens(): void
    {
        $user = $this->user(UserRoleEnum::CASHIER, ['email' => 'aman@example.com']);
        $user->createToken('old phone');
        Carbon::setTestNow(now()->addDays(8));

        $this->login(['device_name' => 'new phone'])->assertOk();

        $this->assertSame(['new phone'], $user->tokens()->pluck('name')->all());
    }

    public function test_missing_fields_come_back_as_validation_errors(): void
    {
        $this->postJson('/api/v1/auth/login', [])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'validation_failed')
            ->assertJsonPath('errors.login.0', __('validations/validations.auth.login_required'))
            ->assertJsonPath('errors.password.0', __('validations/validations.auth.password_required'));
    }

    public function test_messages_follow_the_accept_language_header(): void
    {
        $this->user(UserRoleEnum::CASHIER, ['email' => 'aman@example.com']);

        $this->withHeader('Accept-Language', 'ru')->login(['password' => 'nope'])
            ->assertJsonPath('message', 'Неверный логин или пароль.');

        $this->withHeader('Accept-Language', 'ru')->getJson('/api/v1/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('message', 'Пожалуйста, войдите снова.');

        $this->withHeader('Accept-Language', 'tr')->getJson('/api/v1/auth/me')
            ->assertJsonPath('message', 'Lütfen tekrar giriş yapın.');

        $this->withHeader('Accept-Language', 'tr')->login(['password' => 'nope'])
            ->assertJsonPath('message', 'Kullanıcı adı veya şifre hatalı.');

        $this->withHeader('Accept-Language', 'de')->getJson('/api/v1/auth/me')
            ->assertJsonPath('message', 'Täzeden giriň.');
    }

    public function test_guessing_one_accounts_password_is_throttled_and_raises_one_alert(): void
    {
        $this->user(UserRoleEnum::CASHIER, ['email' => 'aman@example.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->login(['password' => 'nope'])->assertStatus(422);
        }

        // Even the right password has to wait now.
        $this->login()->assertStatus(429)->assertJsonPath('error_code', 'too_many_requests');
        $this->login(['login' => ' AMAN@example.com '])->assertStatus(429);

        $this->assertSame(1, DB::table('security_alerts')->where('alert_type', 'login_throttled')->count());

        // Another account from the same address is not locked out with it.
        $this->login(['login' => 'other@example.com'])->assertStatus(422);
    }

    public function test_one_address_trying_many_accounts_is_throttled(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->login(['login' => "user{$i}@example.com"])->assertStatus(422);
        }

        $this->login(['login' => 'user20@example.com'])->assertStatus(429);
    }

    public function test_answers_carry_the_security_headers(): void
    {
        $this->signIn($this->user())->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Cache-Control', 'no-store, private');

        $this->withHeader('Origin', 'https://evil.example')->getJson('/api/v1/auth/me')
            ->assertHeaderMissing('Access-Control-Allow-Origin');
    }

    public function test_requests_without_a_valid_token_are_rejected_as_json(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'unauthenticated');

        $this->withToken('1|nonsense')->get('/api/v1/auth/me')->assertStatus(401)->assertJsonPath('error_code', 'unauthenticated');

        $this->getJson('/api/v1/nothing-here')->assertStatus(404)->assertJsonPath('error_code', 'not_found');
    }

    public function test_token_expires(): void
    {
        $user = $this->user();
        $this->signIn($user)->getJson('/api/v1/auth/me')->assertOk();

        Carbon::setTestNow(now()->addDays(7)->addMinute());
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/v1/auth/me')->assertStatus(401);
    }

    public function test_deactivated_user_is_locked_out_and_loses_their_tokens(): void
    {
        $user = $this->user();
        $this->signIn($user)->getJson('/api/v1/auth/me')->assertOk();

        // Deactivating ends every session of the account on the spot.
        $user->update(['status' => false]);
        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1/auth/me')->assertStatus(401);
    }

    public function test_a_token_that_survives_deactivation_is_still_refused(): void
    {
        $user = $this->user();
        $this->signIn($user);

        // Changed behind the model's back, e.g. straight in the database.
        DB::table('users')->where('id', $user->id)->update(['status' => false]);

        $this->getJson('/api/v1/auth/me')
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'account_inactive');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logout_revokes_only_the_current_device(): void
    {
        $user = $this->user();
        $other = $user->createToken('tablet')->plainTextToken;

        $this->signIn($user)->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', __('api.logged_out'));

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseHas('login_histories', ['user_id' => $user->id, 'status' => 'logout']);

        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1/auth/me')->assertStatus(401);

        $this->app['auth']->forgetGuards();
        $this->withToken($other)->getJson('/api/v1/auth/me')->assertOk();
    }

    public function test_abilities_follow_the_role(): void
    {
        $abilities = fn (UserRoleEnum $role) => array_keys(array_filter(
            $this->signIn($this->user($role))->getJson('/api/v1/auth/me')->json('data.abilities')
        ));

        $this->assertSame(
            ['customers.info', 'payments.take', 'payments.void', 'payments.correct', 'customers.list', 'reports.avshocrecat', 'reports', 'sms', 'users', 'logs', 'commands', 'contracts.create', 'contracts.approve', 'contracts.cancel'],
            $abilities(UserRoleEnum::SUPER_ADMIN)
        );
        $this->assertSame(
            ['customers.info', 'payments.take', 'payments.void', 'payments.correct', 'customers.list', 'reports.avshocrecat', 'reports', 'sms', 'users', 'contracts.create', 'contracts.approve', 'contracts.cancel'],
            $abilities(UserRoleEnum::ADMIN)
        );
        $this->assertSame(['customers.list', 'reports.avshocrecat', 'reports'], $abilities(UserRoleEnum::ANALYST));
        $this->assertSame(['customers.info', 'payments.take', 'customers.list', 'reports.avshocrecat', 'sms', 'contracts.create', 'contracts.approve'], $abilities(UserRoleEnum::OPERATOR));
        $this->assertSame(['customers.list'], $abilities(UserRoleEnum::MANAGER));
        $this->assertSame([], $abilities(UserRoleEnum::USER));
    }
}
