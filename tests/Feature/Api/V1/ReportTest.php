<?php

namespace Tests\Feature\Api\V1;

use App\Enums\UserRoleEnum;
use App\Http\Controllers\Api\V1\Reports\ReportController;
use App\Models\AvshocrecatReport;
use App\Models\Credit;
use App\Models\User;
use App\Services\Payments\CreatePaymentData;
use App\Services\Payments\PaymentService;
use Illuminate\Support\Carbon;

class ReportTest extends ApiTestCase
{
    private const DETAILS = [
        'payment-calendar/details' => 'date=2026-10-14&type=received',
        'collection-performance/details' => 'type=branch&value=Mary',
    ];

    private Credit $mary;

    private Credit $lebap;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-14 15:00:00'));

        $this->mary = $this->credit('1200.00', '0.00', [
            'name' => 'Mary Customer', 'branch' => 'Mary', 'contract' => 'C-000111', 'phone' => '99361000001',
            'date_' => '2026-06-01', 'willpaiddate' => '2026-10-10', 'willpaidamount' => 200, 'paid' => '0.00',
        ]);
        $this->lebap = $this->credit('1200.00', '0.00', [
            'name' => 'Lebap Customer', 'branch' => 'Lebap', 'contract' => 'C-000222', 'date_' => '2026-06-01', 'paid' => '0.00',
        ]);

        AvshocrecatReport::create(['magazyn' => 'Mary', 'karz_alyjy' => 'Mary Customer', 'sertnama_nomeri' => 'C-000111', 'tolejek_senesi' => '2026-10-14', 'aylyk_tolegi' => '200.00']);

        $cashier = $this->user(UserRoleEnum::CASHIER, ['firstname' => 'Aman', 'lastname' => 'Amanow']);
        $this->take($cashier, $this->mary, 300, '2026-10-14 10:00');
        $this->take($cashier, $this->lebap, 200, '2026-10-13 10:00');
        $voided = $this->take($cashier, $this->mary, 50, '2026-10-14 11:00');
        app(PaymentService::class)->void($this->user(UserRoleEnum::ADMIN), $voided, 'mistake');

        auth()->forgetGuards();
    }

    private function take(User $cashier, Credit $credit, float $amount, string $at)
    {
        return app(PaymentService::class)->create($cashier, new CreatePaymentData(
            creditSourceId: $credit->source_id,
            method: 'cash',
            received: $amount,
            paymentAt: Carbon::parse($at),
        ));
    }

    private function address(string $report): string
    {
        $query = self::DETAILS[preg_replace('#/export$#', '', $report)] ?? '';

        return "/api/v1/reports/{$report}" . ($query !== '' ? "?{$query}" : '');
    }

    public function test_every_report_answers_with_the_data_of_its_panel_page(): void
    {
        $this->signIn($this->user(UserRoleEnum::ADMIN));

        foreach (array_keys(ReportController::REPORTS) as $report) {
            $response = $this->getJson($this->address($report));

            $this->assertSame(200, $response->status(), "{$report}: " . $response->getContent());
            $this->assertTrue($response->json('success'), $report);
            $this->assertNotEmpty($response->json('data'), $report);
        }
    }

    public function test_every_export_downloads_a_spreadsheet(): void
    {
        $this->signIn($this->user(UserRoleEnum::ADMIN));

        foreach (array_keys(ReportController::EXPORTS) as $report) {
            $address = $this->address($report) . ($report === 'customer-statement/export' ? '?contract=C-000111' : '');
            $response = $this->get($address);

            $this->assertSame(200, $response->getStatusCode(), $report);
            $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'), $report);
        }
    }

    public function test_daily_cash_closing_carries_the_panels_figures(): void
    {
        $response = $this->signIn($this->user(UserRoleEnum::ANALYST))
            ->getJson('/api/v1/reports/daily-cash-closing?date_from=2026-10-13&date_to=2026-10-14')
            ->assertOk()
            ->assertJsonStructure(['data' => ['summary', 'daily_rows', 'branches', 'cashiers', 'date_from', 'date_to', 'branch', 'cashier']]);

        $this->assertEqualsCanonicalizing(['Lebap', 'Mary'], $response->json('data.branches'));
        $this->assertCount(2, $response->json('data.daily_rows'));
    }

    public function test_paged_reports_return_items_and_paging(): void
    {
        $this->signIn($this->user(UserRoleEnum::ADMIN))->getJson('/api/v1/reports/avshocrecat')
            ->assertOk()
            ->assertJsonPath('data.rows.meta.total', 1)
            ->assertJsonPath('data.rows.items.0.karz_alyjy', 'Mary Customer')
            ->assertJsonPath('data.rows.items.0.aylyk_tolegi', '200.00')
            ->assertJsonPath('data.branches', ['Mary']);

        $this->getJson('/api/v1/reports/void-correction')
            ->assertOk()
            ->assertJsonPath('data.rows.meta.total', 1);
    }

    public function test_customer_statement_finds_the_credit_and_lists_its_payments(): void
    {
        $response = $this->signIn($this->user(UserRoleEnum::ANALYST))
            ->getJson('/api/v1/reports/customer-statement?contract=C-000111')
            ->assertOk()
            ->assertJsonPath('data.credit.name', 'Mary Customer')
            ->assertJsonPath('data.credit.local_remaining', 900)
            ->assertJsonMissingPath('data.credit.paid_updated_by_user.email');

        $this->assertNotEmpty($response->json('data.statement_rows'));
    }

    public function test_bad_filters_and_missing_parameters_are_validation_errors(): void
    {
        $this->signIn($this->user(UserRoleEnum::ADMIN));

        $this->getJson('/api/v1/reports/daily-cash-closing?date_from=2026-10-14&date_to=2026-10-01')
            ->assertStatus(422)->assertJsonPath('error_code', 'validation_failed');
        $this->getJson('/api/v1/reports/payment-calendar/details')
            ->assertStatus(422)->assertJsonStructure(['errors' => ['date', 'type']]);
        $this->getJson('/api/v1/reports/overdue-payments?sort=drop+table')->assertStatus(422);

        // The panel goes back with a message here; the API returns the message.
        $this->getJson('/api/v1/reports/customer-statement/export')
            ->assertStatus(422)->assertJsonPath('error_code', 'request_failed');

        $this->getJson('/api/v1/reports/no-such-report')->assertNotFound();
    }

    public function test_reports_stay_inside_the_users_branches(): void
    {
        $this->signIn($this->user(UserRoleEnum::ANALYST, ['branches' => ['Lebap']]));

        $response = $this->getJson('/api/v1/reports/daily-cash-closing?date_from=2026-10-13&date_to=2026-10-14')->assertOk();
        $this->assertSame(['Lebap'], $response->json('data.branches'));
        $this->assertCount(1, $response->json('data.daily_rows'));

        $this->getJson('/api/v1/reports/customer-statement?contract=C-000111')
            ->assertOk()
            ->assertJsonPath('data.credit', null);

        $this->getJson('/api/v1/reports/collection-performance/details?type=branch&value=Mary')
            ->assertOk()
            ->assertJsonPath('data.rows.meta.total', 0);
    }

    public function test_roles_match_the_panel(): void
    {
        foreach ([UserRoleEnum::CASHIER, UserRoleEnum::OPERATOR, UserRoleEnum::MANAGER, UserRoleEnum::USER] as $role) {
            $this->signIn($this->user($role));
            $this->getJson('/api/v1/reports/daily-cash-closing')->assertForbidden();
            $this->getJson('/api/v1/reports/balance-integrity/export')->assertForbidden();
        }

        // The instalment report is also open to cashiers and operators.
        foreach ([UserRoleEnum::CASHIER, UserRoleEnum::OPERATOR, UserRoleEnum::ANALYST] as $role) {
            $this->signIn($this->user($role))->getJson('/api/v1/reports/avshocrecat')->assertOk();
        }
        $this->signIn($this->user(UserRoleEnum::MANAGER))->getJson('/api/v1/reports/avshocrecat')->assertForbidden();

        $this->app['auth']->forgetGuards();
        $this->flushHeaders()->getJson('/api/v1/reports/daily-cash-closing')->assertStatus(401);
    }
}
