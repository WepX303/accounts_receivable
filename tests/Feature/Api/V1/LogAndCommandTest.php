<?php

namespace Tests\Feature\Api\V1;

use App\Enums\UserRoleEnum;
use App\Services\Credits\CreditsResyncAmountLocalService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class LogAndCommandTest extends ApiTestCase
{
    public function test_logs_bring_activity_sign_ins_and_alerts_together(): void
    {
        $this->user(UserRoleEnum::CASHIER, ['email' => 'aman@example.com']);
        $this->postJson('/api/v1/auth/login', ['login' => 'aman@example.com', 'password' => 'nope'])->assertStatus(422);
        $this->postJson('/api/v1/auth/login', ['login' => 'aman@example.com', 'password' => 'password'])->assertOk();

        $response = $this->signIn($this->user(UserRoleEnum::SUPER_ADMIN))->getJson('/api/v1/logs')
            ->assertOk()
            ->assertJsonStructure(['data' => ['logs' => ['items', 'meta'], 'stats' => ['total', 'activity', 'login', 'alert'], 'q', 'type']]);

        $stats = $response->json('data.stats');
        $this->assertSame(2, $stats['activity']);
        $this->assertSame(2, $stats['login']);
        $this->assertSame(1, $stats['alert']);
        $this->assertSame(5, $response->json('data.logs.meta.total'));

        $this->getJson('/api/v1/logs?type=alert')
            ->assertOk()
            ->assertJsonPath('data.logs.meta.total', 1)
            ->assertJsonPath('data.logs.items.0.type', 'alert');

        $this->getJson('/api/v1/logs?type=nonsense')->assertStatus(422);
    }

    public function test_entries_say_whether_they_came_from_the_app_or_the_panel_and_from_which_address(): void
    {
        $this->user(UserRoleEnum::CASHIER, ['email' => 'aman@example.com']);

        // The app, through a proxy: the connection is the proxy's, the visitor's address is forwarded.
        $this->withHeaders([
            'User-Agent' => 'AccountsReceivableMobile/1.0.0 (android; mobile)',
            'X-Forwarded-For' => '95.85.100.20',
        ])->postJson('/api/v1/auth/login', ['login' => 'aman@example.com', 'password' => 'password'])->assertOk();

        $this->assertDatabaseHas('login_histories', [
            'status' => 'success',
            'client' => 'mobile',
            'ip_address' => '95.85.100.20',
            'device_type' => 'mobile',
            'browser' => 'Mobile App 1.0.0',
            'platform' => 'Android',
        ]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'login_success', 'client' => 'mobile', 'ip_address' => '95.85.100.20']);

        // The panel, in a browser.
        $this->flushHeaders()->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0 Safari/537.36',
            'X-Forwarded-For' => '95.85.100.21',
        ])->post('/login', ['login' => 'aman@example.com', 'password' => 'wrong']);

        $this->assertDatabaseHas('login_histories', [
            'status' => 'failed',
            'client' => 'web',
            'ip_address' => '95.85.100.21',
            'device_type' => 'desktop',
            'browser' => 'Chrome',
            'platform' => 'Windows',
        ]);
        $this->assertDatabaseHas('security_alerts', ['alert_type' => 'wrong_password_attempt', 'client' => 'web', 'ip_address' => '95.85.100.21']);

        $this->flushHeaders()->signIn($this->user(UserRoleEnum::SUPER_ADMIN));

        // The source is shown with each entry and can be searched for.
        $mobile = $this->getJson('/api/v1/logs?q=mobile&type=login')->assertOk();
        $this->assertSame(1, $mobile->json('data.logs.meta.total'));
        $this->assertSame('mobile', $mobile->json('data.logs.items.0.client'));
        $this->assertSame('mobile', $mobile->json('data.logs.items.0.raw.client'));
    }

    public function test_logs_are_read_a_page_at_a_time_in_one_order(): void
    {
        foreach (range(1, 30) as $minute) {
            DB::table('activity_logs')->insert(['action' => "a{$minute}", 'created_at' => now()->subMinutes($minute * 2)]);
            DB::table('login_histories')->insert(['login_value' => "l{$minute}", 'status' => 'success', 'created_at' => now()->subMinutes($minute * 2 + 1)]);
        }

        $this->signIn($this->user(UserRoleEnum::SUPER_ADMIN));

        $titles = [];
        foreach ([1, 2, 3] as $page) {
            $response = $this->getJson("/api/v1/logs?page={$page}")->assertOk()->assertJsonPath('data.logs.meta.total', 60);
            $titles = array_merge($titles, array_map(
                fn (array $row) => $row['type'] === 'activity' ? $row['main_title'] : $row['user_text'],
                $response->json('data.logs.items')
            ));
        }

        // Newest first across both tables, with nothing skipped or repeated between pages.
        $expected = [];
        foreach (range(1, 30) as $minute) {
            $expected[] = "a{$minute}";
            $expected[] = "l{$minute}";
        }
        $this->assertSame($expected, $titles);
    }

    public function test_command_centre_lists_the_panels_commands(): void
    {
        $response = $this->signIn($this->user(UserRoleEnum::SUPER_ADMIN))->getJson('/api/v1/commands')->assertOk();

        $this->assertSame(
            ['clear_all_caches', 'credits_resync_amount_local', 'sync_credits', 'sync_avshocrecat'],
            array_column($response->json('data.commands'), 'key')
        );
        $response->assertJsonPath('data.commands.2.risk', 'medium')
            ->assertJsonPath('data.commands.2.title', __('commands.commands.sync_credits.title'))
            ->assertJsonPath('data.commands.2.confirm_message', __('commands.commands.sync_credits.confirm'))
            ->assertJsonMissingPath('data.commands.2.route');
    }

    public function test_commands_run_and_are_logged(): void
    {
        $super = $this->user(UserRoleEnum::SUPER_ADMIN);
        $this->signIn($super);

        Artisan::shouldReceive('call')->once()->with('sync:credits')->andReturn(0);
        $this->postJson('/api/v1/commands/sync_credits')
            ->assertOk()
            ->assertJsonPath('message', __('commands.commands.sync_credits.success'));

        Artisan::shouldReceive('call')->once()->with('sync:avshocrecat')->andThrow(new \RuntimeException('source is down'));
        $this->postJson('/api/v1/commands/sync_avshocrecat')
            ->assertStatus(422)
            ->assertJsonPath('message', __('commands.commands.sync_avshocrecat.error'));

        $this->mock(CreditsResyncAmountLocalService::class)
            ->shouldReceive('run')->once()->andReturn(['affected_count' => 3, 'updated_count' => 2]);
        $this->postJson('/api/v1/commands/credits_resync_amount_local')
            ->assertOk()
            ->assertJsonPath('message', __('commands.commands.credits_resync_amount_local.success', ['affected' => 3, 'updated' => 2]));

        $this->assertDatabaseHas('activity_logs', ['action' => 'command_run', 'user_id' => $super->id, 'is_success' => true, 'severity' => 'warning']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'command_run', 'is_success' => false]);
        $this->assertSame(3, DB::table('activity_logs')->where('action', 'command_run')->count());

        $this->postJson('/api/v1/commands/migrate:fresh')->assertNotFound();
        $this->postJson('/api/v1/commands/drop_everything')->assertNotFound();
    }

    public function test_logs_and_commands_are_for_the_super_admin_only(): void
    {
        Artisan::shouldReceive('call')->never();

        foreach ([UserRoleEnum::ADMIN, UserRoleEnum::ANALYST, UserRoleEnum::CASHIER, UserRoleEnum::OPERATOR, UserRoleEnum::MANAGER, UserRoleEnum::USER] as $role) {
            $this->signIn($this->user($role));
            $this->getJson('/api/v1/logs')->assertForbidden();
            $this->getJson('/api/v1/commands')->assertForbidden();
            $this->postJson('/api/v1/commands/sync_credits')->assertForbidden();
        }

        $this->app['auth']->forgetGuards();
        $this->flushHeaders()->postJson('/api/v1/commands/sync_credits')->assertStatus(401);
    }
}
