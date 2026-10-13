<?php

namespace Tests\Feature\Api\V1;

use App\Enums\UserRoleEnum;
use App\Services\Customers\CustomerListService;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * What keeps the API standing and closed under misuse.
 */
class HardeningTest extends ApiTestCase
{
    public function test_exports_are_few_a_minute_and_one_at_a_time(): void
    {
        $analyst = $this->user(UserRoleEnum::ANALYST);
        $this->signIn($analyst);

        foreach (range(1, 6) as $attempt) {
            $this->get('/api/v1/customers/export')->assertOk();
        }
        $this->getJson('/api/v1/customers/export')->assertStatus(429)->assertJsonPath('error_code', 'too_many_requests');

        // While one spreadsheet is being written, a second request is turned away at once.
        Cache::flush();
        $lock = Cache::lock('api-export:' . $analyst->id, 300);
        $this->assertTrue($lock->get());

        $this->getJson('/api/v1/customers/export')
            ->assertStatus(429)
            ->assertJsonPath('error_code', 'export_in_progress')
            ->assertHeader('Retry-After', 15);

        $lock->release();
        $this->get('/api/v1/customers/export')->assertOk();

        // The place is given back when the spreadsheet is done.
        $this->assertTrue(Cache::lock('api-export:' . $analyst->id, 300)->get());
    }

    public function test_reports_and_writes_have_limits_of_their_own(): void
    {
        $this->signIn($this->user(UserRoleEnum::ADMIN));

        foreach (range(1, 40) as $attempt) {
            $this->getJson('/api/v1/reports/collection-trend')->assertOk();
        }
        $this->getJson('/api/v1/reports/collection-trend')->assertStatus(429);

        // Reading the rest of the app goes on meanwhile.
        $this->getJson('/api/v1/auth/me')->assertOk();

        foreach (range(1, 40) as $attempt) {
            $this->postJson('/api/v1/payments', [])->assertStatus(422);
        }
        $this->postJson('/api/v1/payments', [])->assertStatus(429);
    }

    public function test_a_flood_from_one_address_is_stopped_before_it_reaches_the_sign_in_check(): void
    {
        // No token at all: each of these is refused, and counted.
        foreach (range(1, 600) as $attempt) {
            $this->getJson('/api/v1/auth/me')->assertUnauthorized();
        }

        $this->getJson('/api/v1/auth/me')
            ->assertStatus(429)
            ->assertJsonPath('error_code', 'too_many_requests')
            ->assertHeader('Retry-After');

        // Another address is not held up by it.
        $this->withServerVariables(['REMOTE_ADDR' => '10.20.30.40'])->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_an_oversized_request_is_turned_away_before_it_is_read(): void
    {
        $this->signIn($this->user(UserRoleEnum::OPERATOR));

        $this->postJson('/api/v1/sms/preview', ['message' => str_repeat('a', 600 * 1024)])
            ->assertStatus(413)
            ->assertJsonPath('error_code', 'payload_too_large');
    }

    public function test_a_user_keeps_a_handful_of_devices(): void
    {
        $user = $this->user(UserRoleEnum::CASHIER, ['email' => 'aman@example.com']);

        foreach (range(1, 7) as $device) {
            Cache::flush();
            $this->postJson('/api/v1/auth/login', ['login' => 'aman@example.com', 'password' => 'password', 'device_name' => "phone-{$device}"])->assertOk();
        }

        $this->assertSame(
            ['phone-3', 'phone-4', 'phone-5', 'phone-6', 'phone-7'],
            PersonalAccessToken::where('tokenable_id', $user->id)->orderBy('id')->pluck('name')->all()
        );
    }

    public function test_a_panel_session_does_not_open_the_api(): void
    {
        $admin = $this->user(UserRoleEnum::ADMIN);

        // Signed in as a browser would be, without a token.
        $this->actingAs($admin, 'web')->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_answers_are_data_only_and_https_only(): void
    {
        $response = $this->getJson('https://localhost/api/v1/auth/me');

        $response->assertHeader('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none'")
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

        // Over plain http there is no such promise to make.
        $this->assertFalse($this->getJson('http://localhost/api/v1/auth/me')->headers->has('Strict-Transport-Security'));
    }

    public function test_a_server_fault_never_shows_its_cause(): void
    {
        $this->signIn($this->user(UserRoleEnum::ADMIN));
        config(['app.debug' => true]);

        $this->app->bind(CustomerListService::class, fn () => new class extends CustomerListService
        {
            public function list(array $filters, int $perPage = 25): array
            {
                throw new \RuntimeException('SQLSTATE[42P01]: relation "credits" does not exist in /var/www/app/secret.php');
            }
        });

        $response = $this->getJson('/api/v1/customers')->assertStatus(500)->assertJsonPath('error_code', 'server_error');

        foreach (['SQLSTATE', 'credits', '/var/www', 'RuntimeException', 'trace'] as $leak) {
            $this->assertStringNotContainsStringIgnoringCase($leak, $response->getContent());
        }
    }

    public function test_the_log_page_cannot_be_asked_to_read_the_whole_table(): void
    {
        $this->signIn($this->user(UserRoleEnum::SUPER_ADMIN));

        $this->getJson('/api/v1/logs?page=500')->assertOk();
        $this->getJson('/api/v1/logs?page=100000')->assertStatus(422);
    }
}
