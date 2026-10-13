<?php

namespace Tests\Feature\Web\Customers;

use App\Enums\UserRoleEnum;
use App\Exports\CustomersExport;
use App\Models\Credit;
use App\Models\User;
use App\Services\Payments\CreatePaymentData;
use App\Services\Payments\PaymentService;
use Database\Factories\CreditFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The "due" filters of the customer list, and the export holding what the list shows.
 */
class CustomersDueAndExportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-14 15:00:00'));
        $this->admin = UserFactory::new()->role(UserRoleEnum::ADMIN)->create();
        $this->admin->forceFill(['token' => bin2hex(random_bytes(32)), 'token_expires_at' => now()->addDay()])->save();

        // 600 over six months: 100 a month, the first one a month after the credit's date.
        // name => [credit date, paid so far]
        $rows = [
            'Due Today' => ['2026-09-14', '0.00'],           // 1st part on 10-14
            'Second Part Today' => ['2026-08-14', '100.00'], // 2nd part on 10-14
            'Due In 2 Days' => ['2026-09-16', '0.00'],       // 1st part on 10-16
            'Due In 6 Days' => ['2026-09-20', '0.00'],       // 1st part on 10-20
            'Due In 20 Days' => ['2026-10-03', '0.00'],      // 1st part on 11-03
            'Overdue' => ['2026-08-14', '0.00'],             // 1st part was due on 09-14
            'Paid Ahead' => ['2026-09-14', '100.00'],        // 1st part paid; the 2nd is due on 11-14
            'Settled' => ['2026-09-14', '600.00'],
        ];

        $order = count($rows);
        foreach ($rows as $name => [$date, $paid]) {
            CreditFactory::new()->create([
                'name' => $name,
                'date_' => $date,
                'amount' => '600.00',
                'paid' => $paid,
                'amount_local' => '600.00',
                'paid_local' => $paid,
                'rv_bigint' => $order--,
            ]);
        }
    }

    private function names(string $query): array
    {
        auth()->forgetGuards();

        return collect(
            $this->withCookie('auth_token', $this->admin->token)->get('/customers' . $query)->assertOk()->viewData('credit_users')->items()
        )->pluck('name')->all();
    }

    private function exported(array $filters): array
    {
        auth()->forgetGuards();
        $this->actingAs($this->admin);

        return (new CustomersExport(Request::create('/customers/export', 'GET', $filters)))->query()->pluck('name')->all();
    }

    public function test_due_filters_go_by_the_first_part_not_yet_paid(): void
    {
        $this->assertSame(['Due Today', 'Second Part Today'], $this->names('?quick_filter=due_today'));
        $this->assertSame(['Due Today', 'Second Part Today', 'Due In 2 Days'], $this->names('?quick_filter=due_3d'));
        $this->assertSame(
            ['Due Today', 'Second Part Today', 'Due In 2 Days', 'Due In 6 Days'],
            $this->names('?quick_filter=due_7d')
        );
    }

    public function test_the_export_holds_what_the_list_shows(): void
    {
        $pay = fn (string $name, string $at) => app(PaymentService::class)->create($this->admin, new CreatePaymentData(
            creditSourceId: Credit::withoutGlobalScopes()->where('name', $name)->value('source_id'),
            method: 'cash',
            received: 50,
            paymentAt: Carbon::parse($at),
        ));

        $pay('Overdue', '2026-10-10 10:00');
        $pay('Due In 20 Days', '2026-10-12 10:00');
        // A payment taken back does not count as one, on the screen or in the spreadsheet.
        app(PaymentService::class)->void($this->admin, $pay('Paid Ahead', '2026-10-10 11:00'), 'mistake');

        // A date range selects who paid in it — in the spreadsheet as on the screen.
        foreach ([
            ['date_from' => '2026-10-10', 'date_to' => '2026-10-10'],
            ['date_from' => '2026-10-10', 'date_to' => '2026-10-12'],
            ['quick_filter' => 'due_3d'],
            ['quick_filter' => 'has_debt', 'q' => 'Due'],
            [],
        ] as $filters) {
            $this->assertSame(
                $this->names('?' . http_build_query($filters)),
                $this->exported($filters),
                json_encode($filters)
            );
        }

        $this->assertSame(['Overdue'], $this->exported(['date_from' => '2026-10-10', 'date_to' => '2026-10-10']));
        $this->assertSame(['Due In 20 Days', 'Overdue'], $this->exported(['date_from' => '2026-10-10', 'date_to' => '2026-10-12']));
    }

    public function test_the_export_accepts_the_lists_filters_and_logs_them(): void
    {
        auth()->forgetGuards();

        $this->withCookie('auth_token', $this->admin->token)
            ->get('/customers/export?quick_filter=due_today&date_from=2026-10-10&date_to=2026-10-10')
            ->assertOk()
            ->assertDownload('customers.xlsx');

        $this->assertDatabaseHas('activity_logs', ['action' => 'customers_exported']);
        $logged = json_decode(DB::table('activity_logs')->where('action', 'customers_exported')->value('extra'), true);
        $this->assertSame(['2026-10-10', '2026-10-10', 'due_today'], [$logged['date_from'], $logged['date_to'], $logged['quick_filter']]);

        $this->withCookie('auth_token', $this->admin->token)
            ->get('/customers/export?date_from=2026-10-12&date_to=2026-10-10')
            ->assertSessionHasErrors('date_to');
    }
}
