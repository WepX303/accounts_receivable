<?php

namespace Tests\Feature\Web\Dashboard;

use App\Enums\UserRoleEnum;
use App\Models\Credit;
use App\Models\User;
use App\Services\Payments\CreatePaymentData;
use App\Services\Payments\PaymentService;
use Database\Factories\CreditFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Pins down the figures the panel's two dashboards show today.
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $aman;

    private User $oraz;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-14 15:00:00'));

        $this->aman = UserFactory::new()->create(['firstname' => 'Aman', 'lastname' => 'Amanow']);
        $this->oraz = UserFactory::new()->create(['firstname' => 'Oraz', 'lastname' => 'Orazow']);

        $mary = CreditFactory::new()->debt('50000.00')->create(['branch' => 'Mary']);
        $lebap = CreditFactory::new()->debt('50000.00')->create(['branch' => 'Lebap']);

        // Today: Aman 300 cash (Mary) and 200 card (Lebap); Oraz 500 mixed (Mary).
        $this->take($this->aman, $mary, 'cash', 300, at: '2026-10-14 09:10');
        $this->take($this->aman, $lebap, 'card', 200, at: '2026-10-14 11:20');
        $this->take($this->oraz, $mary, 'mixed', 500, cash: 100, card: 400, at: '2026-10-14 11:40');
        // Yesterday and last month.
        $this->take($this->oraz, $lebap, 'phone', 1000, at: '2026-10-13 10:00');
        $this->take($this->aman, $mary, 'cash', 70, at: '2026-09-20 10:00');
        // Voided today: counts nowhere.
        $voided = $this->take($this->aman, $mary, 'cash', 999, at: '2026-10-14 12:00');
        app(PaymentService::class)->void(UserFactory::new()->role(UserRoleEnum::ADMIN)->create(), $voided, 'mistake');

        auth()->forgetGuards();
    }

    private function take(User $cashier, Credit $credit, string $method, float $amount, float $cash = 0, float $card = 0, string $at = '')
    {
        return app(PaymentService::class)->create($cashier, new CreatePaymentData(
            creditSourceId: $credit->source_id,
            method: $method,
            received: $amount,
            cashTotal: $cash,
            cardTotal: $card,
            receiverPhoneNumber: '99361112233',
            paymentAt: Carbon::parse($at),
        ));
    }

    private function open(User $user, string $query = '')
    {
        $user->forceFill(['token' => bin2hex(random_bytes(32)), 'token_expires_at' => now()->addDay()])->save();

        return $this->withCookie('auth_token', $user->token)->get('/' . $query)->assertOk();
    }

    public function test_cashier_sees_only_their_own_day(): void
    {
        $response = $this->open($this->aman)->assertViewIs('pages.dashboard.cashier');

        $stats = $response->viewData('stats');
        $this->assertSame(500.0, $stats['today_total']);
        $this->assertSame(2, $stats['today_count']);
        $this->assertSame(250.0, $stats['avg_payment']);
        $this->assertStringStartsWith('2026-10-14 11:20', (string) $stats['last_payment_at']);

        // The list is not limited to today: the cashier's ten most recently
        // entered payments, whatever their date, voided ones left out.
        $recent = $response->viewData('recentPayments');
        $this->assertSame([70.0, 200.0, 300.0], $recent->pluck('amount')->all());
    }

    public function test_admin_sees_todays_totals_by_method_cashier_and_branch(): void
    {
        $admin = UserFactory::new()->role(UserRoleEnum::ADMIN)->create();

        $response = $this->open($admin)->assertViewIs('pages.dashboard.dashboard');

        $this->assertSame('today', $response->viewData('period'));

        $kpi = $response->viewData('kpi');
        $this->assertSame(1000.0, $kpi['total_net']);
        $this->assertSame(3, $kpi['total_count']);
        $this->assertSame(400.0, $kpi['total_cash']);
        $this->assertSame(600.0, $kpi['total_card']);
        $this->assertSame(0.0, $kpi['total_phone']);
        $this->assertSame(1000.0, $kpi['all_total']);
        $this->assertSame(1, $kpi['mixed_tx_count']);

        $byCashier = $response->viewData('byCashier');
        $this->assertSame(['Aman Amanow', 'Oraz Orazow'], $byCashier->pluck('cashier_name')->all());
        $this->assertSame(['500.00', '500.00'], $byCashier->pluck('total_net')->all());
        $this->assertSame('Aman Amanow', $response->viewData('topCashier')->cashier_name);

        $byBranch = $response->viewData('byBranch');
        $this->assertSame(['Mary' => '800.00', 'Lebap' => '200.00'], $byBranch->pluck('total_net', 'branch')->all());

        $this->assertCount(3, $response->viewData('recentAdminPayments'));

        $chart = $response->viewData('chartDaily');
        $this->assertCount(24, $chart['labels']);
        $this->assertSame(300.0, $chart['series'][9]);
        $this->assertSame(700.0, $chart['series'][11]);
    }

    public function test_admin_can_pick_other_periods(): void
    {
        $admin = UserFactory::new()->role(UserRoleEnum::ADMIN)->create();

        $this->assertSame(1000.0, $this->open($admin, '?period=yesterday')->viewData('kpi')['total_phone']);
        $this->assertSame(2000.0, $this->open($admin, '?period=week')->viewData('kpi')['total_net']);
        $this->assertSame(2000.0, $this->open($admin, '?period=last7')->viewData('kpi')['total_net']);
        $this->assertSame(2070.0, $this->open($admin, '?period=last30')->viewData('kpi')['total_net']);
        $this->assertSame(2070.0, $this->open($admin, '?period=all')->viewData('kpi')['total_net']);

        $month = $this->open($admin, '?period=month');
        $this->assertSame(2000.0, $month->viewData('kpi')['total_net']);
        $this->assertCount(14, $month->viewData('chartDaily')['labels']);

        $custom = $this->open($admin, '?period=custom&start=2026-09-01&end=2026-09-30');
        $this->assertSame(70.0, $custom->viewData('kpi')['total_net']);
        $this->assertSame('2026-09-01', $custom->viewData('start')->toDateString());
        $this->assertSame([], $custom->viewData('chartDaily')['labels']);

        $all = $this->open($admin, '?period=all')->viewData('chartDaily');
        $this->assertSame(['09.2026', '10.2026'], $all['labels']);
        $this->assertSame([70.0, 2000.0], $all['series']);
    }

    public function test_branch_restricted_user_sees_only_their_branches_and_does_not_share_a_cache(): void
    {
        $admin = UserFactory::new()->role(UserRoleEnum::ADMIN)->create();
        $restricted = UserFactory::new()->role(UserRoleEnum::ANALYST)->create(['branches' => ['Lebap']]);

        $this->assertSame(1000.0, $this->open($admin)->viewData('kpi')['total_net']);

        auth()->forgetGuards();
        $response = $this->open($restricted);
        $this->assertSame(200.0, $response->viewData('kpi')['total_net']);
        $this->assertSame(['Lebap'], $response->viewData('byBranch')->pluck('branch')->all());
    }

    public function test_new_payment_shows_up_at_once_despite_the_cache(): void
    {
        $admin = UserFactory::new()->role(UserRoleEnum::ADMIN)->create();
        $this->assertSame(1000.0, $this->open($admin)->viewData('kpi')['total_net']);

        $this->take($this->aman, Credit::withoutGlobalScopes()->first(), 'cash', 50, at: '2026-10-14 14:00');

        $this->assertSame(1050.0, $this->open($admin)->viewData('kpi')['total_net']);
    }

    public function test_bad_period_is_rejected(): void
    {
        $admin = UserFactory::new()->role(UserRoleEnum::ADMIN)->create();
        $admin->forceFill(['token' => str_repeat('b', 64), 'token_expires_at' => now()->addDay()])->save();

        $this->withCookie('auth_token', $admin->token)->get('/?period=forever')->assertSessionHasErrors('period');
        $this->withCookie('auth_token', $admin->token)->get('/?period=custom&start=2026-10-10&end=2026-10-01')->assertSessionHasErrors('end');
    }
}
