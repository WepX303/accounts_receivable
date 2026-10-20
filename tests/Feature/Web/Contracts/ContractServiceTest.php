<?php

namespace Tests\Feature\Web\Contracts;

use App\Enums\UserRoleEnum;
use App\Models\ActivityLog;
use App\Models\Credit;
use App\Models\CreditPayment;
use App\Models\CustomerContract;
use App\Models\SecurityAlert;
use App\Models\User;
use App\Services\Contracts\ContractData;
use App\Services\Contracts\ContractException;
use App\Services\Contracts\ContractService;
use Carbon\Carbon;
use Database\Factories\CreditFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ContractServiceTest extends TestCase
{
    use RefreshDatabase;

    private ContractService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-21 10:00:00');
        $this->service = app(ContractService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function user(UserRoleEnum $role, ?array $branches = null): User
    {
        $user = UserFactory::new()->role($role)->create(['branches' => $branches]);
        $this->actingAs($user);

        return $user;
    }

    private function data(array $overrides = []): ContractData
    {
        $d = $overrides + [
            'branch' => 'Mary',
            'contractDate' => Carbon::parse('2026-10-21'),
            'customerName' => 'Aman Amanow',
            'passportSeries' => 'I-AŞ',
            'passportNumber' => '350924',
            'address' => 'Aşgabat',
            'phone' => '+99365000000',
            'months' => 6,
            'monthlyAmount' => 250.0,
            'installmentPart' => '5/6',
            'goods' => [['name' => 'IMEI 1', 'quantity' => '1', 'price' => '1500', 'total' => '1500']],
        ];

        return new ContractData(...$d);
    }

    private function payment(Credit $credit, string $amount, string $at, bool $voided = false): void
    {
        CreditPayment::withoutGlobalScopes()->create([
            'credit_source_id' => $credit->source_id,
            'credit_logicalref' => $credit->logicalref,
            'branch' => $credit->branch,
            'pay_amount' => $amount,
            'change_amount' => '0.00',
            'method' => 'cash',
            'cash_amount' => $amount,
            'card_amount' => '0.00',
            'phone_amount' => '0.00',
            'old_amount_local' => '0.00',
            'new_amount_local' => '0.00',
            'old_paid_local' => '0.00',
            'new_paid_local' => '0.00',
            'created_at' => $at,
        ]);

        if ($voided) {
            DB::table('credit_payments')->latest('id')->limit(1)->update(['voided_at' => $at]);
        }
    }

    public function test_passports_are_split_into_series_and_number(): void
    {
        $cases = [
            'I-AŞ 350924' => ['I-AŞ', '350924'],
            'I-AS350924' => ['I-AS', '350924'],
            'AA-AŞ  123456' => ['AA-AŞ', '123456'],
            'I-AŞ № 123456' => ['I-AŞ', '123456'],
            '123456' => [null, '123456'],
            'I-AŞ' => ['I-AŞ', null],
            '' => [null, null],
        ];

        foreach ($cases as $passport => $expected) {
            $this->assertSame($expected, ContractService::splitPassport($passport), $passport);
        }
    }

    public function test_the_form_is_filled_from_a_credit(): void
    {
        $this->user(UserRoleEnum::CASHIER, ['Mary']);
        $credit = CreditFactory::new()->create([
            'branch' => 'Mary',
            'name' => ' Aman Amanow ',
            'passport' => 'I-AŞ 350924',
            'phone' => '+99365000000',
            'assurance' => 'Aşgabat şäheri, jaý 5',
        ]);

        $form = $this->service->prefill($credit);

        $this->assertSame((int) $credit->source_id, $form['credit_source_id']);
        $this->assertSame('Mary', $form['branch']);
        $this->assertSame('Aman Amanow', $form['customer_name']);
        $this->assertSame('I-AŞ', $form['passport_series']);
        $this->assertSame('350924', $form['passport_number']);
        $this->assertSame('Aşgabat şäheri, jaý 5', $form['address']);
        $this->assertSame('+99365000000', $form['phone']);
        $this->assertCount(1, $form['history']);
    }

    public function test_history_lists_the_buyers_credits_of_every_branch(): void
    {
        // A cashier of Mary still sees the buyer's record in Lebap, but only on page 2.
        $this->user(UserRoleEnum::CASHIER, ['Mary']);

        $closed = CreditFactory::new()->debt('1500.00', '1500.00')->create([
            'branch' => 'Lebap', 'passport' => 'I-AŞ 350924', 'contract' => 'OLD-1', 'date_' => '2025-01-10',
        ]);
        $this->payment($closed, '750.00', '2025-03-01 10:00:00');
        $this->payment($closed, '750.00', '2025-06-15 10:00:00');
        $this->payment($closed, '100.00', '2025-08-01 10:00:00', voided: true);

        CreditFactory::new()->debt('1200.00', '200.00')->create([
            'branch' => 'Mary', 'passport' => 'i-aş350924', 'contract' => 'OPEN-1', 'date_' => '2026-04-01',
        ]);

        // Same digits, another person; and a credit the source has removed.
        CreditFactory::new()->create(['passport' => 'I-AH 350924', 'contract' => 'OTHER']);
        CreditFactory::new()->create(['passport' => 'I-AŞ 350924', 'contract' => 'DELETED', 'active' => false]);

        $rows = $this->service->history('I-AŞ', '350924');

        $this->assertSame(['OLD-1', 'OPEN-1'], array_column($rows, 'contract'));

        $this->assertSame([
            'contract' => 'OLD-1',
            'date' => '10.01.2025',
            'amount' => '1 500',
            'paid' => '1 500',
            'remaining' => '0',
            'status' => 'ýapyk',
            'closed_at' => '15.06.2025',
            'duration' => '5 aý',
            'overdue' => '0',
        ], $rows[0]);

        $this->assertSame('açyk', $rows[1]['status']);
        $this->assertSame('1 000', $rows[1]['remaining']);
        $this->assertSame('', $rows[1]['closed_at']);
        // All six instalments of 200 have fallen due since April; 200 is paid.
        $this->assertSame('1 000', $rows[1]['overdue']);

        $this->assertSame(0, Credit::where('branch', 'Lebap')->count());
    }

    public function test_numbers_follow_each_other_within_a_year(): void
    {
        $user = $this->user(UserRoleEnum::CASHIER);

        $first = $this->service->create($user, $this->data());
        $second = $this->service->create($user, $this->data());

        $this->assertSame('UT-2026-0000001', $first->number);
        $this->assertSame('UT-2026-0000002', $second->number);

        Carbon::setTestNow('2027-01-02 09:00:00');
        $this->assertSame('UT-2027-0000001', $this->service->create($user, $this->data())->number);
    }

    public function test_a_cashier_creates_a_draft_and_it_is_logged(): void
    {
        $user = $this->user(UserRoleEnum::CASHIER, ['Mary']);

        $contract = $this->service->create($user, $this->data());

        $this->assertTrue($contract->isDraft());
        $this->assertSame('v1', $contract->template_version);
        $this->assertSame($user->id, $contract->created_by);
        $this->assertSame($user->full_name, $contract->created_by_name);

        $log = ActivityLog::where('action', 'contract_created')->sole();
        $this->assertSame((string) $contract->id, (string) $log->subject_id);
        $this->assertSame('Aman Amanow', $log->new_values['customer_name']);
    }

    public function test_who_may_create(): void
    {
        foreach ([UserRoleEnum::SUPER_ADMIN, UserRoleEnum::ADMIN, UserRoleEnum::CASHIER, UserRoleEnum::OPERATOR] as $role) {
            $this->service->create($this->user($role), $this->data());
        }

        foreach ([UserRoleEnum::ANALYST, UserRoleEnum::MANAGER, UserRoleEnum::USER] as $role) {
            $this->assertRefused('forbidden', fn () => $this->service->create($this->user($role), $this->data()));
        }

        $this->assertSame(4, CustomerContract::count());
    }

    public function test_a_contract_cannot_be_made_for_a_branch_the_user_does_not_have(): void
    {
        $user = $this->user(UserRoleEnum::CASHIER, ['Mary']);

        $this->assertRefused('branch_not_allowed', fn () => $this->service->create($user, $this->data(['branch' => 'Lebap'])));
        $this->assertRefused('customer_name_required', fn () => $this->service->create($user, $this->data(['customerName' => ' '])));
    }

    public function test_a_credit_of_another_branch_cannot_be_named(): void
    {
        $credit = CreditFactory::new()->create(['branch' => 'Lebap']);
        $user = $this->user(UserRoleEnum::CASHIER, ['Mary']);

        $this->assertRefused('credit_not_found', fn () => $this->service->create($user, $this->data(['creditSourceId' => (int) $credit->source_id])));
    }

    public function test_draft_changes_are_logged_with_what_they_replaced(): void
    {
        $user = $this->user(UserRoleEnum::CASHIER);
        $contract = $this->service->create($user, $this->data());

        $this->service->update($user, $contract, $this->data(['address' => 'Mary şäheri', 'months' => 8]));

        $log = ActivityLog::where('action', 'contract_updated')->sole();
        $this->assertSame(['address' => 'Aşgabat', 'months' => 6], $log->old_values);
        $this->assertSame(['address' => 'Mary şäheri', 'months' => 8], $log->new_values);

        // Saving the same again changes and logs nothing.
        $this->service->update($user, $contract->fresh(), $this->data(['address' => 'Mary şäheri', 'months' => 8]));
        $this->assertSame(1, ActivityLog::where('action', 'contract_updated')->count());
    }

    public function test_an_approved_contract_cannot_be_changed_or_approved_again(): void
    {
        $contract = $this->service->create($this->user(UserRoleEnum::CASHIER), $this->data());
        $operator = $this->user(UserRoleEnum::OPERATOR);

        $this->service->approve($operator, $contract);

        $fresh = $contract->fresh();
        $this->assertTrue($fresh->isApproved());
        $this->assertSame($operator->id, $fresh->approved_by);
        $this->assertSame(1, ActivityLog::where('action', 'contract_approved')->count());

        $this->assertRefused('not_draft', fn () => $this->service->update($operator, $fresh, $this->data(['address' => 'x'])));
        $this->assertRefused('not_draft', fn () => $this->service->approve($operator, $fresh));
        $this->assertSame('Aşgabat', $contract->fresh()->address);
    }

    public function test_a_cashier_cannot_approve_or_cancel_and_an_operator_cannot_cancel(): void
    {
        $cashier = $this->user(UserRoleEnum::CASHIER);
        $contract = $this->service->create($cashier, $this->data());

        $this->assertRefused('forbidden', fn () => $this->service->approve($cashier, $contract));
        $this->assertRefused('forbidden', fn () => $this->service->cancel($cashier, $contract, 'x'));
        $this->assertRefused('forbidden', fn () => $this->service->cancel($this->user(UserRoleEnum::OPERATOR), $contract, 'x'));
    }

    public function test_cancelling_needs_a_reason_and_an_approved_one_raises_an_alert(): void
    {
        $contract = $this->service->create($this->user(UserRoleEnum::CASHIER), $this->data());
        $admin = $this->user(UserRoleEnum::ADMIN);
        $this->service->approve($admin, $contract);

        $this->assertRefused('cancel_reason_required', fn () => $this->service->cancel($admin, $contract, '  '));

        $this->service->cancel($admin, $contract, ' Wrong   passport ');

        $fresh = $contract->fresh();
        $this->assertTrue($fresh->isCancelled());
        $this->assertSame('Wrong passport', $fresh->cancel_reason);
        $this->assertSame($admin->id, $fresh->cancelled_by);
        $this->assertSame(1, SecurityAlert::where('alert_type', 'contract_cancelled')->count());

        $this->assertRefused('already_cancelled', fn () => $this->service->cancel($admin, $fresh, 'again'));
    }

    public function test_a_draft_can_be_cancelled_without_an_alert(): void
    {
        $contract = $this->service->create($this->user(UserRoleEnum::CASHIER), $this->data());

        $this->service->cancel($this->user(UserRoleEnum::ADMIN), $contract, 'Typed twice');

        $this->assertTrue($contract->fresh()->isCancelled());
        $this->assertSame(0, SecurityAlert::count());
    }

    public function test_every_print_is_counted_and_logged(): void
    {
        $user = $this->user(UserRoleEnum::CASHIER);
        $contract = $this->service->create($user, $this->data());

        [$name, $pdf] = $this->service->print($user, $contract);
        $this->service->print($user, $contract);

        $this->assertSame('UT-2026-0000001.pdf', $name);
        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertSame(2, $contract->fresh()->print_count);
        $this->assertSame(2, ActivityLog::where('action', 'contract_printed')->count());
    }

    public function test_an_approved_and_a_cancelled_contract_can_still_be_printed(): void
    {
        $user = $this->user(UserRoleEnum::ADMIN);
        $contract = $this->service->create($user, $this->data());
        $this->service->approve($user, $contract);
        $this->service->print($user, $contract->fresh());
        $this->service->cancel($user, $contract->fresh(), 'x');
        $this->service->print($user, $contract->fresh());

        $this->assertSame(2, $contract->fresh()->print_count);
    }

    public function test_a_blank_takes_a_number_and_prints_only_that(): void
    {
        $user = $this->user(UserRoleEnum::CASHIER, ['Mary']);

        $blank = $this->service->issueBlank($user, 'Mary');
        $next = $this->service->create($user, $this->data());

        $this->assertTrue($blank->isIssued());
        $this->assertSame('', $blank->customer_name);
        $this->assertSame('UT-2026-0000001', $blank->number);
        $this->assertSame('UT-2026-0000002', $next->number);
        $this->assertSame(1, ActivityLog::where('action', 'contract_issued')->count());

        $this->assertRefused('branch_not_allowed', fn () => $this->service->issueBlank($user, 'Lebap'));
        $this->assertRefused('forbidden', fn () => $this->service->issueBlank($this->user(UserRoleEnum::ANALYST), 'Mary'));

        [, $pdf] = $this->service->print($user, $blank);
        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertSame(1, $blank->fresh()->print_count);
    }

    public function test_a_blank_is_filled_in_to_become_a_draft_and_cannot_be_approved_before(): void
    {
        $user = $this->user(UserRoleEnum::ADMIN);
        $blank = $this->service->issueBlank($user, 'Mary');

        $this->assertRefused('not_draft', fn () => $this->service->approve($user, $blank));

        $this->service->update($user, $blank, $this->data());

        $fresh = $blank->fresh();
        $this->assertTrue($fresh->isDraft());
        $log = ActivityLog::where('action', 'contract_updated')->sole();
        $this->assertSame('issued', $log->old_values['status']);
        $this->assertSame('draft', $log->new_values['status']);

        $this->service->approve($user, $fresh);
        $this->assertTrue($blank->fresh()->isApproved());
    }

    public function test_an_unused_blank_is_cancelled_without_an_alert(): void
    {
        $user = $this->user(UserRoleEnum::ADMIN);
        $blank = $this->service->issueBlank($user, 'Mary');

        $this->service->cancel($user, $blank, 'Spoilt paper');

        $this->assertTrue($blank->fresh()->isCancelled());
        $this->assertSame(0, SecurityAlert::count());
    }

    public function test_contracts_never_change_credits_or_payments(): void
    {
        $credit = CreditFactory::new()->debt('1500.00', '500.00')->create(['branch' => 'Mary', 'passport' => 'I-AŞ 350924']);
        $this->payment($credit, '500.00', '2026-06-01 10:00:00');

        $snapshot = fn () => [
            DB::table('credits')->orderBy('source_id')->get()->map(fn ($r) => (array) $r)->all(),
            DB::table('credit_payments')->orderBy('id')->get()->map(fn ($r) => (array) $r)->all(),
        ];
        $before = $snapshot();

        $admin = $this->user(UserRoleEnum::ADMIN);
        $form = $this->service->prefill($credit);
        $contract = $this->service->create($admin, $this->data(['creditSourceId' => $form['credit_source_id'], 'history' => $form['history']]));
        $this->service->update($admin, $contract, $this->data(['creditSourceId' => $form['credit_source_id'], 'address' => 'x']));
        $this->service->approve($admin, $contract);
        $this->service->print($admin, $contract->fresh());
        $this->service->cancel($admin, $contract->fresh(), 'x');
        $blank = $this->service->issueBlank($admin, 'Mary');
        $this->service->print($admin, $blank);

        $this->assertSame($before, $snapshot());
    }

    private function assertRefused(string $code, callable $action): void
    {
        try {
            $action();
        } catch (ContractException $e) {
            $this->assertSame($code, $e->errorCode);

            return;
        }

        $this->fail("Expected refusal [{$code}]");
    }
}
