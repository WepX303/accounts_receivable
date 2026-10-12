<?php

namespace Tests\Feature\Api\V1;

use App\Enums\UserRoleEnum;
use App\Models\Credit;
use App\Models\User;
use App\Services\Payments\CreatePaymentData;
use App\Services\Payments\PaymentService;
use Illuminate\Support\Carbon;

class DashboardTest extends ApiTestCase
{
    private User $aman;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-14 15:00:00'));

        $this->aman = $this->user(UserRoleEnum::CASHIER, ['firstname' => 'Aman', 'lastname' => 'Amanow']);
        $oraz = $this->user(UserRoleEnum::CASHIER, ['firstname' => 'Oraz', 'lastname' => 'Orazow']);

        $mary = $this->credit('50000.00', '0.00', ['branch' => 'Mary', 'name' => 'Mary Customer']);
        $lebap = $this->credit('50000.00', '0.00', ['branch' => 'Lebap']);

        $this->take($this->aman, $mary, 'cash', 300, '2026-10-14 09:10');
        $this->take($this->aman, $lebap, 'card', 200, '2026-10-14 11:20');
        $this->take($oraz, $mary, 'cash', 500, '2026-10-14 11:40');
        $this->take($oraz, $lebap, 'cash', 1000, '2026-10-13 10:00');

        auth()->forgetGuards();
    }

    private function take(User $cashier, Credit $credit, string $method, float $amount, string $at): void
    {
        app(PaymentService::class)->create($cashier, new CreatePaymentData(
            creditSourceId: $credit->source_id,
            method: $method,
            received: $amount,
            paymentAt: Carbon::parse($at),
        ));
    }

    public function test_cashier_gets_their_own_day(): void
    {
        $this->signIn($this->aman)->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.type', 'cashier')
            ->assertJsonPath('data.stats.today_total', '500.00')
            ->assertJsonPath('data.stats.today_count', 2)
            ->assertJsonPath('data.stats.avg_payment', '250.00')
            ->assertJsonPath('data.stats.last_payment_at', '2026-10-14T11:20:00+05:00')
            ->assertJsonCount(2, 'data.recent_payments')
            ->assertJsonPath('data.recent_payments.0.amount', '200.00')
            ->assertJsonPath('data.recent_payments.1.customer_name', 'Mary Customer');
    }

    public function test_everyone_else_gets_the_overview_for_the_chosen_period(): void
    {
        $this->signIn($this->user(UserRoleEnum::ADMIN))->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.type', 'overview')
            ->assertJsonPath('data.period', 'today')
            ->assertJsonPath('data.start', '2026-10-14')
            ->assertJsonPath('data.kpi.total_net', '1000.00')
            ->assertJsonPath('data.kpi.total_count', 3)
            ->assertJsonPath('data.kpi.total_cash', '800.00')
            ->assertJsonPath('data.kpi.total_card', '200.00')
            ->assertJsonPath('data.top_cashier.cashier_name', 'Aman Amanow')
            ->assertJsonCount(2, 'data.by_cashier')
            ->assertJsonPath('data.by_branch.0', [
                'branch' => 'Mary',
                'tx_count' => 2,
                'total_net' => '800.00',
                'total_cash' => '800.00',
                'total_card' => '0.00',
                'total_phone' => '0.00',
            ])
            ->assertJsonCount(3, 'data.recent_payments')
            ->assertJsonPath('data.recent_payments.0.net_amount', '500.00')
            ->assertJsonCount(24, 'data.chart.labels')
            ->assertJsonPath('data.chart.series.11', '700.00');

        $this->getJson('/api/v1/dashboard?period=yesterday')->assertJsonPath('data.kpi.total_net', '1000.00');
        $this->getJson('/api/v1/dashboard?period=custom&start=2026-10-13&end=2026-10-14')
            ->assertJsonPath('data.kpi.total_net', '2000.00')
            ->assertJsonPath('data.end', '2026-10-14');

        $this->getJson('/api/v1/dashboard?period=forever')->assertStatus(422)->assertJsonStructure(['errors' => ['period']]);
    }

    public function test_overview_is_limited_to_the_users_branches(): void
    {
        $this->signIn($this->user(UserRoleEnum::ADMIN))->getJson('/api/v1/dashboard')->assertJsonPath('data.kpi.total_net', '1000.00');

        $this->signIn($this->user(UserRoleEnum::ANALYST, ['branches' => ['Lebap']]))->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.kpi.total_net', '200.00')
            ->assertJsonCount(1, 'data.by_branch')
            ->assertJsonPath('data.by_branch.0.branch', 'Lebap');
    }

    public function test_guest_cannot_reach_it(): void
    {
        $this->getJson('/api/v1/dashboard')->assertStatus(401);
    }
}
