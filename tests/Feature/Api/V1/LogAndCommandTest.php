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
