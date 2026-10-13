<?php

namespace Tests\Feature\Web\Logs;

use App\Enums\UserRoleEnum;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LogsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_page_shows_where_each_entry_came_from_in_the_users_language(): void
    {
        DB::table('login_histories')->insert([
            ['login_value' => 'app@example.com', 'status' => 'success', 'client' => 'mobile', 'ip_address' => '95.85.100.20', 'created_at' => now()],
            ['login_value' => 'panel@example.com', 'status' => 'success', 'client' => 'web', 'ip_address' => '95.85.100.21', 'created_at' => now()->subMinute()],
            // Written before the source was recorded.
            ['login_value' => 'old@example.com', 'status' => 'success', 'client' => null, 'ip_address' => null, 'created_at' => now()->subDay()],
        ]);

        $super = UserFactory::new()->create(['role' => UserRoleEnum::SUPER_ADMIN]);

        $super->forceFill(['token' => bin2hex(random_bytes(32)), 'token_expires_at' => now()->addDay()])->save();

        $this->withCookie('auth_token', $super->token)->withSession(['locale' => 'tr'])->get('/logs')
            ->assertOk()
            ->assertSee(__('pages/logs.login', [], 'tr'))
            ->assertSee(__('pages/logs.client', [], 'tr'))
            ->assertSeeInOrder(['app@example.com', 'Mobil', '95.85.100.20', 'panel@example.com', 'Web', '95.85.100.21', 'old@example.com'])
            ->assertDontSee('Total Logs');
    }

    public function test_entries_from_before_the_source_was_recorded_are_marked_as_the_panels(): void
    {
        DB::table('login_histories')->insert([
            ['login_value' => 'old@example.com', 'status' => 'success', 'client' => null, 'created_at' => now()->subMonth()],
            ['login_value' => 'app@example.com', 'status' => 'success', 'client' => 'mobile', 'created_at' => now()],
        ]);
        DB::table('activity_logs')->insert(['action' => 'payment_created', 'client' => null, 'created_at' => now()->subMonth()]);
        DB::table('security_alerts')->insert(['alert_type' => 'wrong_password_attempt', 'risk_level' => 'medium', 'client' => null, 'created_at' => now()->subMonth()]);

        (require database_path('migrations/2026_10_13_140000_mark_earlier_log_entries_as_web.php'))->up();

        $this->assertSame(['mobile', 'web'], DB::table('login_histories')->orderBy('client')->pluck('client')->all());
        $this->assertSame('web', DB::table('activity_logs')->value('client'));
        $this->assertSame('web', DB::table('security_alerts')->value('client'));
    }
}
