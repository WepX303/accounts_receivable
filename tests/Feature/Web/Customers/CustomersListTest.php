<?php

namespace Tests\Feature\Web\Customers;

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
 * Pins down the panel's customer list: its filters, its period column and its counters.
 */
class CustomersListTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-14 15:00:00'));
        $this->cashier = UserFactory::new()->create();

        // name => [source amount, source paid, local paid, extra]
        $rows = [
            'Paid Today' => ['1000.00', '0.00', '0.00', ['branch' => 'Mary', 'rv_bigint' => 6]],
            'Paid Yesterday' => ['1000.00', '0.00', '0.00', ['branch' => 'Lebap', 'rv_bigint' => 5]],
            'Paid Long Ago' => ['1000.00', '0.00', '0.00', ['branch' => 'Mary', 'rv_bigint' => 4]],
            'Never Paid' => ['1000.00', '0.00', '0.00', ['branch' => 'Mary', 'assurance' => 'Kepil Kepilow', 'rv_bigint' => 3]],
            'Settled' => ['1000.00', '1000.00', '1000.00', ['branch' => 'Mary', 'status' => ' Bermejek ', 'rv_bigint' => 2]],
            'Blocked' => ['1000.00', '0.00', '0.00', ['branch' => 'Mary', 'is_blocked' => 1, 'rv_bigint' => 1]],
            'Deleted' => ['1000.00', '0.00', '0.00', ['branch' => 'Mary', 'active' => false, 'rv_bigint' => 0]],
        ];

        foreach ($rows as $name => [$amount, $paid, $paidLocal, $extra]) {
            CreditFactory::new()->create(array_merge([
                'name' => $name,
                'amount' => $amount,
                'paid' => $paid,
                'amount_local' => $amount,
                'paid_local' => $paidLocal,
            ], $extra));
        }

        $this->take('Paid Today', 300, '2026-10-14 10:00');
        $this->take('Paid Today', 150, '2026-10-14 11:00');
        $this->take('Paid Yesterday', 200, '2026-10-13 10:00');
        $this->take('Paid Long Ago', 50, '2026-06-01 10:00');
        $voided = $this->take('Never Paid', 75, '2026-10-14 12:00');
        app(PaymentService::class)->void(UserFactory::new()->role(UserRoleEnum::ADMIN)->create(), $voided, 'mistake');

        auth()->forgetGuards();
    }

    private function take(string $name, float $amount, string $at)
    {
        return app(PaymentService::class)->create($this->cashier, new CreatePaymentData(
            creditSourceId: Credit::withoutGlobalScopes()->where('name', $name)->value('source_id'),
            method: 'cash',
            received: $amount,
            paymentAt: Carbon::parse($at),
        ));
    }

    private function open(string $query = '', ?User $user = null)
    {
        $user ??= UserFactory::new()->role(UserRoleEnum::ADMIN)->create();
        $user->forceFill(['token' => bin2hex(random_bytes(32)), 'token_expires_at' => now()->addDay()])->save();
        auth()->forgetGuards();

        return $this->withCookie('auth_token', $user->token)->get('/customers' . $query)->assertOk();
    }

    private function names(string $query = '', ?User $user = null): array
    {
        return collect($this->open($query, $user)->viewData('credit_users')->items())->pluck('name')->all();
    }

    public function test_list_shows_active_credits_newest_first_with_todays_payments(): void
    {
        $response = $this->open();

        $this->assertSame('all', $response->viewData('quick'));
        $this->assertSame(__('pages/customers_index.today_paid'), $response->viewData('periodLabel'));

        $rows = collect($response->viewData('credit_users')->items());
        $this->assertSame(['Paid Today', 'Paid Yesterday', 'Paid Long Ago', 'Never Paid', 'Settled', 'Blocked'], $rows->pluck('name')->all());

        // The period column defaults to today and leaves voided payments out.
        $this->assertSame('450.00', $rows[0]->period_pay_sum);
        $this->assertNull($rows[1]->period_pay_sum);
        $this->assertNull($rows[3]->period_pay_sum);
    }

    public function test_counters(): void
    {
        $stats = $this->open()->viewData('stats');

        $this->assertSame(450.0, $stats['paid_today_sum']);
        $this->assertSame(2, $stats['paid_today_cnt']);
        $this->assertSame(200.0, $stats['paid_y_sum']);
        $this->assertSame(1, $stats['paid_y_cnt']);
        // Counted on the source figures and over every credit, deleted ones included.
        $this->assertSame(6, $stats['has_debt_cnt']);
        // Local paid differs from the source: the three that took payments here.
        $this->assertSame(3, $stats['paid_mismatch_cnt']);
    }

    public function test_quick_filters(): void
    {
        $this->assertSame(['Paid Today'], $this->names('?quick_filter=paid_today'));
        $this->assertSame(['Paid Yesterday'], $this->names('?quick_filter=paid_yesterday'));
        $this->assertSame(['Paid Today', 'Paid Yesterday'], $this->names('?quick_filter=paid_7d'));
        $this->assertSame(['Paid Today', 'Paid Yesterday', 'Paid Long Ago'], $this->names('?quick_filter=paid_6m'));
        $this->assertSame(['Paid Today', 'Paid Yesterday', 'Paid Long Ago', 'Never Paid', 'Blocked'], $this->names('?quick_filter=has_debt'));
        $this->assertSame(['Settled'], $this->names('?quick_filter=no_debt'));
        $this->assertSame(['Never Paid', 'Settled', 'Blocked'], $this->names('?quick_filter=no_payment'));
        $this->assertSame(['Blocked'], $this->names('?quick_filter=blocked'));
        $this->assertSame(['Deleted'], $this->names('?quick_filter=deleted'));
        $this->assertSame(['Settled'], $this->names('?quick_filter=bermejek'));
        $this->assertSame(['Paid Today', 'Paid Yesterday', 'Paid Long Ago'], $this->names('?quick_filter=paid_mismatch'));
        $this->assertCount(6, $this->names('?quick_filter=active'));
    }

    public function test_period_column_and_label_follow_the_filter(): void
    {
        $response = $this->open('?quick_filter=paid_7d');
        $this->assertSame(__('pages/customers_index.paid_last_7_days'), $response->viewData('periodLabel'));
        $this->assertSame(['450.00', '200.00'], collect($response->viewData('credit_users')->items())->pluck('period_pay_sum')->all());

        // A date range wins over the quick filter.
        $response = $this->open('?quick_filter=paid_today&date_from=2026-06-01&date_to=2026-06-30');
        $this->assertSame('2026-06-01 → 2026-06-30', $response->viewData('periodLabel'));
        $rows = collect($response->viewData('credit_users')->items());
        $this->assertSame(['Paid Long Ago'], $rows->pluck('name')->all());
        $this->assertSame('50.00', $rows[0]->period_pay_sum);

        // Half a range is ignored.
        $this->assertSame(['Paid Today'], $this->names('?quick_filter=paid_today&date_from=2026-06-01'));
    }

    public function test_search_also_looks_at_the_guarantor(): void
    {
        $this->assertSame(['Never Paid'], $this->names('?q=kepil'));
        $this->assertSame(['Paid Long Ago'], $this->names('?q=+long+'));
        $this->assertCount(6, $this->names('?q=null'));
    }

    public function test_input_is_validated(): void
    {
        $admin = UserFactory::new()->role(UserRoleEnum::ADMIN)->create(['token' => str_repeat('a', 64), 'token_expires_at' => now()->addDay()]);
        $this->withCookie('auth_token', $admin->token);

        $this->get('/customers?quick_filter=everything')->assertSessionHasErrors('quick_filter');
        $this->get('/customers?date_from=2026-10-10&date_to=2026-10-01')->assertSessionHasErrors('date_to');
    }

    public function test_branch_restricted_user_sees_and_counts_only_their_branches(): void
    {
        $analyst = UserFactory::new()->role(UserRoleEnum::ANALYST)->create(['branches' => ['Lebap']]);

        $this->assertSame(['Paid Yesterday'], $this->names('', $analyst));

        $stats = $this->open('', $analyst)->viewData('stats');
        $this->assertSame(0.0, $stats['paid_today_sum']);
        $this->assertSame(200.0, $stats['paid_y_sum']);
        $this->assertSame(1, $stats['has_debt_cnt']);
    }

    public function test_only_roles_with_the_list_may_open_it(): void
    {
        $cashier = UserFactory::new()->role(UserRoleEnum::CASHIER)->create(['token' => str_repeat('c', 64), 'token_expires_at' => now()->addDay()]);

        $this->withCookie('auth_token', $cashier->token)->get('/customers')->assertForbidden();
    }
}
