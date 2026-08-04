<?php

namespace App\Services\Reports;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Builds the daily management report that is mailed out every evening.
 *
 * Reporting axis is credit_payments.created_at, which holds the *payment* date
 * (the payment screen lets the cashier set it), so the numbers here always match
 * the on-screen daily cash closing report. The real entry time lives in
 * activity_logs.created_at, and that is what the backdating section compares
 * against.
 *
 * Money convention, identical to DailyCashClosingReportController:
 *   gross = pay_amount, change = change_amount, net = gross - change.
 * Net is the amount that actually reduced customer debt.
 */
class DailyPaymentReportService
{
    private const TREND_DAYS = 14;
    private const TOP_PAYMENTS = 5;
    private const AUDIT_ROWS = 20;

    /**
     * Branches this run is limited to, or null for every branch.
     *
     * The service reads through the query builder, so BranchScope never fires
     * here — which is what lets the nightly command build one unrestricted
     * report for the configured addresses and a narrowed one per recipient.
     *
     * @var string[]|null
     */
    private ?array $branches = null;

    public function getTodayReport(): array
    {
        return $this->getReport(Carbon::today());
    }

    /**
     * @param  string[]|null  $branches  Null means every branch.
     */
    public function getReport(?CarbonInterface $date = null, ?array $branches = null): array
    {
        $this->branches = $this->normalizeBranches($branches);

        $day = $date ? Carbon::parse($date)->startOfDay() : Carbon::today();
        $start = $day->copy()->startOfDay();
        $end = $day->copy()->endOfDay();

        $payments = $this->paymentsBetween($start, $end);

        return [
            'meta' => [
                'date' => $start->format('Y-m-d'),
                'date_label' => $start->format('d.m.Y'),
                'weekday' => (int) $start->dayOfWeekIso,
                'generated_at' => now()->format('d.m.Y H:i'),
                'currency' => 'TMT',
                'branches' => $this->branches,
            ],
            'summary' => $this->summary($payments),
            'comparison' => $this->comparison($start),
            'trend' => $this->trend($start),
            'methods' => $this->methodBreakdown($payments),
            'branches' => $this->branchBreakdown($payments),
            'cashiers' => $this->cashierBreakdown($payments),
            'top_payments' => $this->topPayments($payments),
            'schedule' => $this->schedule($start, $payments),
            'audit' => $this->audit($start, $end, $payments),
        ];
    }

    /**
     * @param  string[]|null  $branches
     * @return string[]|null
     */
    private function normalizeBranches(?array $branches): ?array
    {
        if ($branches === null) {
            return null;
        }

        $clean = collect($branches)
            ->map(fn ($b) => trim((string) $b))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $clean === [] ? null : $clean;
    }

    /**
     * Adds the branch restriction to a query, if this run has one.
     */
    private function scopeBranch(mixed $query, string $column): mixed
    {
        if ($this->branches !== null) {
            $query->whereIn($column, $this->branches);
        }

        return $query;
    }

    /* ---------------------------------------------------------------- data */

    private function paymentsBetween(Carbon $start, Carbon $end): Collection
    {
        return DB::table('credit_payments')
            ->select([
                'id',
                'branch',
                'method',
                'pay_amount',
                'change_amount',
                'cash_amount',
                'card_amount',
                'phone_amount',
                'created_at',
                'created_by_name',
                'customer_name',
                'customer_contract',
            ])
            ->whereNull('voided_at')
            ->whereBetween('created_at', [$start, $end])
            ->tap(fn ($q) => $this->scopeBranch($q, 'branch'))
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Aggregate net/count for a date range without pulling the rows.
     *
     * @return array{net: float, tx_count: int}
     */
    private function netBetween(Carbon $start, Carbon $end): array
    {
        $row = DB::table('credit_payments')
            ->whereNull('voided_at')
            ->whereBetween('created_at', [$start, $end])
            ->tap(fn ($q) => $this->scopeBranch($q, 'branch'))
            ->selectRaw('COUNT(*) as tx_count')
            ->selectRaw('COALESCE(SUM(pay_amount - COALESCE(change_amount, 0)), 0) as net')
            ->first();

        return [
            'net' => (float) ($row->net ?? 0),
            'tx_count' => (int) ($row->tx_count ?? 0),
        ];
    }

    /* ------------------------------------------------------------- summary */

    private function summary(Collection $payments): array
    {
        $gross = $this->sum($payments, 'pay_amount');
        $change = $this->sum($payments, 'change_amount');
        $net = $gross - $change;
        $count = $payments->count();

        return [
            'tx_count' => $count,
            'customer_count' => $payments->pluck('customer_contract')->filter()->unique()->count(),
            'gross' => $gross,
            'change' => $change,
            'net' => $net,
            'cash' => $this->sum($payments, 'cash_amount'),
            'card' => $this->sum($payments, 'card_amount'),
            'phone' => $this->sum($payments, 'phone_amount'),
            'avg_ticket' => $count > 0 ? $net / $count : 0.0,
            'max_ticket' => $count > 0 ? (float) $payments->max(fn ($p) => $this->net($p)) : 0.0,
        ];
    }

    /**
     * Yesterday, the trailing 7-day average, month-to-date and the same
     * month-to-date window of the previous month.
     */
    private function comparison(Carbon $day): array
    {
        $yesterday = $this->netBetween(
            $day->copy()->subDay()->startOfDay(),
            $day->copy()->subDay()->endOfDay()
        );

        $prev7 = $this->netBetween(
            $day->copy()->subDays(7)->startOfDay(),
            $day->copy()->subDay()->endOfDay()
        );
        $avg7 = $prev7['net'] / 7;

        $mtd = $this->netBetween($day->copy()->startOfMonth(), $day->copy()->endOfDay());
        $daysElapsed = max(1, $day->day);

        // The same calendar day one month back, so the comparison is day to day
        // rather than a running total against a running total.
        $prevMonthDay = $day->copy()->subMonthNoOverflow();
        $prevMonthSame = $this->netBetween(
            $prevMonthDay->copy()->startOfDay(),
            $prevMonthDay->copy()->endOfDay()
        );

        $today = $this->netBetween($day->copy()->startOfDay(), $day->copy()->endOfDay());

        return [
            'today' => $today,
            'yesterday' => [
                'net' => $yesterday['net'],
                'tx_count' => $yesterday['tx_count'],
                'net_delta_pct' => $this->deltaPct($today['net'], $yesterday['net']),
                'tx_delta_pct' => $this->deltaPct($today['tx_count'], $yesterday['tx_count']),
            ],
            'avg7' => [
                'net' => $avg7,
                'net_delta_pct' => $this->deltaPct($today['net'], $avg7),
            ],
            'mtd' => [
                'net' => $mtd['net'],
                'tx_count' => $mtd['tx_count'],
                'days_elapsed' => $daysElapsed,
                'daily_avg' => $mtd['net'] / $daysElapsed,
            ],
            'prev_month_day' => [
                'net' => $prevMonthSame['net'],
                'tx_count' => $prevMonthSame['tx_count'],
                'label' => $prevMonthDay->format('d.m.Y'),
                'net_delta_pct' => $this->deltaPct($today['net'], $prevMonthSame['net']),
            ],
        ];
    }

    /**
     * Last N days including the report day, oldest first, for the in-mail bar chart.
     */
    private function trend(Carbon $day): array
    {
        $from = $day->copy()->subDays(self::TREND_DAYS - 1)->startOfDay();

        $rows = DB::table('credit_payments')
            ->whereNull('voided_at')
            ->whereBetween('created_at', [$from, $day->copy()->endOfDay()])
            ->tap(fn ($q) => $this->scopeBranch($q, 'branch'))
            ->selectRaw('DATE(created_at)::text as d')
            ->selectRaw('COUNT(*) as tx_count')
            ->selectRaw('COALESCE(SUM(pay_amount - COALESCE(change_amount, 0)), 0) as net')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->get()
            ->keyBy('d');

        $trend = [];

        for ($i = 0; $i < self::TREND_DAYS; $i++) {
            $cursor = $from->copy()->addDays($i);
            $key = $cursor->format('Y-m-d');
            $hit = $rows->get($key);

            $trend[] = [
                'date' => $key,
                'label' => $cursor->format('d.m'),
                'weekday' => (int) $cursor->dayOfWeekIso,
                'is_report_day' => $cursor->isSameDay($day),
                'tx_count' => (int) ($hit->tx_count ?? 0),
                'net' => (float) ($hit->net ?? 0),
            ];
        }

        return $trend;
    }

    /* ---------------------------------------------------------- breakdowns */

    private function methodBreakdown(Collection $payments): array
    {
        $net = $this->sum($payments, 'pay_amount') - $this->sum($payments, 'change_amount');

        return $payments
            ->groupBy(fn ($p) => (string) ($p->method ?: 'unknown'))
            ->map(function (Collection $rows, string $method) use ($net) {
                $rowNet = $this->sumNet($rows);

                return [
                    'method' => $method,
                    'tx_count' => $rows->count(),
                    'net' => $rowNet,
                    'share' => $net > 0 ? $rowNet / $net * 100 : 0.0,
                ];
            })
            ->sortByDesc('net')
            ->values()
            ->all();
    }

    private function branchBreakdown(Collection $payments): array
    {
        $net = $this->sum($payments, 'pay_amount') - $this->sum($payments, 'change_amount');

        return $payments
            ->groupBy(fn ($p) => (string) ($p->branch ?: '-'))
            ->map(function (Collection $rows, string $branch) use ($net) {
                $rowNet = $this->sumNet($rows);

                return [
                    'branch' => $branch,
                    'tx_count' => $rows->count(),
                    'gross' => $this->sum($rows, 'pay_amount'),
                    'change' => $this->sum($rows, 'change_amount'),
                    'net' => $rowNet,
                    'cash' => $this->sum($rows, 'cash_amount'),
                    'card' => $this->sum($rows, 'card_amount'),
                    'phone' => $this->sum($rows, 'phone_amount'),
                    'share' => $net > 0 ? $rowNet / $net * 100 : 0.0,
                ];
            })
            ->sortByDesc('net')
            ->values()
            ->all();
    }

    private function cashierBreakdown(Collection $payments): array
    {
        return $payments
            ->groupBy(fn ($p) => (string) ($p->created_by_name ?: '-'))
            ->map(fn (Collection $rows, string $cashier) => [
                'cashier' => $cashier,
                'branch' => $rows->pluck('branch')->filter()->unique()->implode(', ') ?: '-',
                'tx_count' => $rows->count(),
                'net' => $this->sumNet($rows),
            ])
            ->sortByDesc('net')
            ->values()
            ->all();
    }

    private function topPayments(Collection $payments): array
    {
        return $payments
            ->sortByDesc(fn ($p) => $this->net($p))
            ->take(self::TOP_PAYMENTS)
            ->map(fn ($p) => [
                'customer' => (string) ($p->customer_name ?: '-'),
                'contract' => (string) ($p->customer_contract ?: '-'),
                'branch' => (string) ($p->branch ?: '-'),
                'cashier' => (string) ($p->created_by_name ?: '-'),
                'method' => (string) ($p->method ?: '-'),
                'time' => $p->created_at ? Carbon::parse($p->created_at)->format('H:i') : '-',
                'net' => $this->net($p),
            ])
            ->values()
            ->all();
    }

    /* ------------------------------------------------------------ schedule */

    /**
     * Splits the day's money by whether the customer was actually due today.
     *
     * "Due today" follows the same six-installment plan the payment calendar
     * uses: a credit is due on its start date plus one through six months.
     *
     * Payments from customers who were not due today are then split into paid
     * early and paid late, using the project's existing overdue rule — the
     * instalments that have come due by today against what the customer had
     * paid *before* today. Someone who was already behind is catching up; the
     * rest are paying ahead of their next instalment.
     */
    private function schedule(Carbon $day, Collection $payments): array
    {
        $dateKey = $day->toDateString();

        $dueCredits = DB::table('credits')
            ->where('active', true)
            ->where('is_blocked', 0)
            ->whereNotNull('date_')
            ->whereRaw('COALESCE(amount_local, amount, 0) > 0')
            ->whereRaw('COALESCE(amount_local, amount, 0) > COALESCE(paid_local, paid, 0)')
            ->tap(fn ($q) => $this->scopeBranch($q, 'branch'))
            ->whereRaw("
                EXISTS (
                    SELECT 1 FROM generate_series(1, 6) AS installment_no
                    WHERE (date_::date + (installment_no * INTERVAL '1 month'))::date = ?::date
                )
            ", [$dateKey])
            ->selectRaw('source_id')
            ->selectRaw('ROUND((COALESCE(amount_local, amount, 0) / 6)::numeric, 2) as installment')
            ->get()
            ->keyBy('source_id');

        // The day's payments, netted per credit. paymentsBetween() does not
        // carry credit_source_id, so this is read separately.
        $paidByCredit = DB::table('credit_payments')
            ->whereNull('voided_at')
            ->whereBetween('created_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])
            ->tap(fn ($q) => $this->scopeBranch($q, 'branch'))
            ->selectRaw('credit_source_id')
            ->selectRaw('COUNT(*) as payment_count')
            ->selectRaw('COALESCE(SUM(pay_amount - COALESCE(change_amount, 0)), 0) as net')
            ->groupBy('credit_source_id')
            ->get()
            ->keyBy('credit_source_id');

        $dueExpected = (float) $dueCredits->sum(fn ($c) => (float) $c->installment);

        $duePaidCredits = 0;
        $duePaidPayments = 0;
        $duePaidAmount = 0.0;

        foreach ($dueCredits as $sourceId => $credit) {
            $hit = $paidByCredit->get($sourceId);

            if ($hit === null) {
                continue;
            }

            $duePaidCredits++;
            $duePaidPayments += (int) $hit->payment_count;
            $duePaidAmount += (float) $hit->net;
        }

        $notDue = $paidByCredit->reject(fn ($row, $sourceId) => $dueCredits->has($sourceId));

        $early = ['credit_count' => 0, 'payment_count' => 0, 'amount' => 0.0];
        $late = ['credit_count' => 0, 'payment_count' => 0, 'amount' => 0.0];

        if ($notDue->isNotEmpty()) {
            $states = DB::table('credits')
                ->whereIn('source_id', $notDue->keys()->all())
                ->selectRaw('source_id')
                ->selectRaw("
                    LEAST(GREATEST(FLOOR((?::date - date_::date) / 30), 0), 6)
                    * ROUND((COALESCE(amount_local, amount, 0) / 6)::numeric, 2) as expected_to_date
                ", [$dateKey])
                ->selectRaw('COALESCE(paid_local, paid, 0) as paid_total')
                ->get()
                ->keyBy('source_id');

            foreach ($notDue as $sourceId => $row) {
                $state = $states->get($sourceId);
                $net = (float) $row->net;

                // paid_local already includes today's money, so today's net is
                // taken back out to judge where the customer stood beforehand.
                $paidBefore = (float) ($state->paid_total ?? 0) - $net;
                $expected = (float) ($state->expected_to_date ?? 0);

                $bucket = ($expected - $paidBefore) > 0.01 ? 'late' : 'early';

                ${$bucket}['credit_count']++;
                ${$bucket}['payment_count'] += (int) $row->payment_count;
                ${$bucket}['amount'] += $net;
            }
        }

        return [
            'due' => [
                'credit_count' => $dueCredits->count(),
                'expected' => round($dueExpected, 2),
                'paid_credit_count' => $duePaidCredits,
                'paid_payment_count' => $duePaidPayments,
                'paid_amount' => round($duePaidAmount, 2),
                'unpaid_credit_count' => $dueCredits->count() - $duePaidCredits,
                'missing' => round(max($dueExpected - $duePaidAmount, 0), 2),
                'rate' => $dueExpected > 0 ? round($duePaidAmount / $dueExpected * 100, 2) : 0.0,
            ],
            'not_due' => [
                'credit_count' => $notDue->count(),
                'payment_count' => (int) $notDue->sum(fn ($r) => (int) $r->payment_count),
                'amount' => round((float) $notDue->sum(fn ($r) => (float) $r->net), 2),
                'early' => [
                    'credit_count' => $early['credit_count'],
                    'payment_count' => $early['payment_count'],
                    'amount' => round($early['amount'], 2),
                ],
                'late' => [
                    'credit_count' => $late['credit_count'],
                    'payment_count' => $late['payment_count'],
                    'amount' => round($late['amount'], 2),
                ],
            ],
        ];
    }

    /* --------------------------------------------------------------- audit */

    private function audit(Carbon $start, Carbon $end, Collection $payments): array
    {
        return [
            'voids' => $this->voids($start, $end),
            'corrections' => $this->corrections($start, $end),
            'backdated' => $this->backdated($start, $end),
            'idle_branches' => $this->idleBranches($payments),
        ];
    }

    /**
     * Payments voided on the report day. A correction voids the old row as its
     * first step, so those rows are excluded here and counted as corrections.
     */
    private function voids(Carbon $start, Carbon $end): array
    {
        $rows = DB::table('credit_payments as p')
            ->leftJoin('users as u', 'u.id', '=', 'p.voided_by')
            ->whereNotNull('p.voided_at')
            ->whereBetween('p.voided_at', [$start, $end])
            ->tap(fn ($q) => $this->scopeBranch($q, 'p.branch'))
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('credit_payments as c')
                    ->whereColumn('c.corrected_from_payment_id', 'p.id');
            })
            ->selectRaw('p.id, p.customer_name, p.customer_contract, p.branch, p.created_at as payment_at')
            ->selectRaw('p.pay_amount - COALESCE(p.change_amount, 0) as net')
            ->selectRaw('p.void_reason, p.voided_at')
            ->selectRaw("TRIM(CONCAT(u.firstname, ' ', u.lastname)) as actor")
            ->orderByDesc('p.voided_at')
            ->get();

        return [
            'count' => $rows->count(),
            'amount' => (float) $rows->sum(fn ($r) => (float) $r->net),
            'rows' => $rows->take(self::AUDIT_ROWS)->map(fn ($r) => [
                'customer' => (string) ($r->customer_name ?: '-'),
                'contract' => (string) ($r->customer_contract ?: '-'),
                'branch' => (string) ($r->branch ?: '-'),
                'payment_at' => $r->payment_at ? Carbon::parse($r->payment_at)->format('d.m.Y H:i') : '-',
                'net' => (float) $r->net,
                'actor' => (string) ($r->actor ?: '-'),
                'reason' => (string) ($r->void_reason ?: '-'),
            ])->values()->all(),
        ];
    }

    /**
     * Corrections applied on the report day, with the old row joined in so both
     * the amount and the payment date that moved are visible. A correction that
     * only changes the date still matters: it shifts money between cash days.
     */
    private function corrections(Carbon $start, Carbon $end): array
    {
        $rows = DB::table('credit_payments as p')
            ->join('credit_payments as prev', 'prev.id', '=', 'p.corrected_from_payment_id')
            ->leftJoin('users as u', 'u.id', '=', 'p.corrected_by')
            ->whereNotNull('p.corrected_at')
            ->whereBetween('p.corrected_at', [$start, $end])
            ->tap(fn ($q) => $this->scopeBranch($q, 'p.branch'))
            ->selectRaw('p.customer_name, p.customer_contract, p.branch')
            ->selectRaw('prev.created_at as old_payment_at, p.created_at as new_payment_at')
            ->selectRaw('prev.pay_amount - COALESCE(prev.change_amount, 0) as old_net')
            ->selectRaw('p.pay_amount - COALESCE(p.change_amount, 0) as new_net')
            ->selectRaw('prev.method as old_method, p.method as new_method')
            ->selectRaw('p.correct_reason')
            ->selectRaw("TRIM(CONCAT(u.firstname, ' ', u.lastname)) as actor")
            ->orderByDesc('p.corrected_at')
            ->get();

        $dateMoved = 0;

        $mapped = $rows->take(self::AUDIT_ROWS)->map(function ($r) use (&$dateMoved) {
            $oldAt = $r->old_payment_at ? Carbon::parse($r->old_payment_at) : null;
            $newAt = $r->new_payment_at ? Carbon::parse($r->new_payment_at) : null;
            $moved = $oldAt && $newAt && !$oldAt->isSameDay($newAt);

            if ($moved) {
                $dateMoved++;
            }

            return [
                'customer' => (string) ($r->customer_name ?: '-'),
                'contract' => (string) ($r->customer_contract ?: '-'),
                'branch' => (string) ($r->branch ?: '-'),
                'old_payment_at' => $oldAt?->format('d.m.Y H:i') ?? '-',
                'new_payment_at' => $newAt?->format('d.m.Y H:i') ?? '-',
                'date_moved' => $moved,
                'old_net' => (float) $r->old_net,
                'new_net' => (float) $r->new_net,
                'diff' => (float) $r->new_net - (float) $r->old_net,
                'old_method' => (string) ($r->old_method ?: '-'),
                'new_method' => (string) ($r->new_method ?: '-'),
                'method_changed' => $r->old_method !== $r->new_method,
                'actor' => (string) ($r->actor ?: '-'),
                'reason' => (string) ($r->correct_reason ?: '-'),
            ];
        })->values()->all();

        return [
            'count' => $rows->count(),
            'diff' => (float) $rows->sum(fn ($r) => (float) $r->new_net - (float) $r->old_net),
            'date_moved_count' => $dateMoved,
            'rows' => $mapped,
        ];
    }

    /**
     * Payments entered on the report day whose payment date falls on an earlier
     * calendar day. Those are the entries that reopen a closed cash day; a
     * payment timed a few minutes before it was saved does not.
     */
    private function backdated(Carbon $start, Carbon $end): array
    {
        $rows = DB::table('activity_logs')
            ->where('action', 'payment_created')
            ->whereBetween('created_at', [$start, $end])
            // The payment screen flags anything entered a minute after the time
            // in the date field, which the field's minute precision makes true
            // for most ordinary entries. What matters here is an entry landing
            // on an earlier calendar day, so the day is compared directly.
            ->whereRaw("extra->>'payment_at' IS NOT NULL")
            ->whereRaw("(extra->>'payment_at')::date < activity_logs.created_at::date")
            // activity_logs has no branch of its own, so the entry is matched
            // back to its credit to decide whether it belongs in this report.
            ->when($this->branches !== null, function ($q) {
                $placeholders = implode(',', array_fill(0, count($this->branches), '?'));

                $q->whereRaw(
                    "EXISTS (
                        SELECT 1 FROM credits bc
                        WHERE bc.source_id = (activity_logs.extra->>'credit_source_id')::bigint
                          AND bc.branch IN ($placeholders)
                    )",
                    $this->branches
                );
            })
            ->selectRaw('user_name, created_at as entered_at')
            ->selectRaw("extra->>'customer_name' as customer_name")
            ->selectRaw("extra->>'customer_contract' as customer_contract")
            ->selectRaw("extra->>'payment_at' as payment_at")
            ->selectRaw("COALESCE((extra->>'applied')::numeric, 0) as net")
            ->orderByDesc('created_at')
            ->get();

        return [
            'count' => $rows->count(),
            'amount' => (float) $rows->sum(fn ($r) => (float) $r->net),
            'rows' => $rows->take(self::AUDIT_ROWS)->map(function ($r) {
                $paymentAt = $r->payment_at ? Carbon::parse($r->payment_at) : null;
                $enteredAt = $r->entered_at ? Carbon::parse($r->entered_at) : null;

                return [
                    'customer' => (string) ($r->customer_name ?: '-'),
                    'contract' => (string) ($r->customer_contract ?: '-'),
                    'actor' => (string) ($r->user_name ?: '-'),
                    'payment_at' => $paymentAt?->format('d.m.Y H:i') ?? '-',
                    'entered_at' => $enteredAt?->format('d.m.Y H:i') ?? '-',
                    'days_back' => $paymentAt && $enteredAt
                        ? $paymentAt->copy()->startOfDay()->diffInDays($enteredAt->copy()->startOfDay())
                        : 0,
                    'net' => (float) $r->net,
                ];
            })->values()->all(),
        ];
    }

    /**
     * Branches that have credits on the books but took no payment on the report
     * day — the "nothing happened here" list management asks about.
     */
    private function idleBranches(Collection $payments): array
    {
        $active = $payments->pluck('branch')->filter()->unique();

        return DB::table('credits')
            ->where('active', true)
            ->where('is_blocked', 0)
            ->whereNotNull('branch')
            ->where('branch', '!=', '')
            ->tap(fn ($q) => $this->scopeBranch($q, 'branch'))
            ->whereRaw('COALESCE(amount_local, amount, 0) > COALESCE(paid_local, paid, 0)')
            ->distinct()
            ->orderBy('branch')
            ->pluck('branch')
            ->reject(fn ($b) => $active->contains($b))
            ->values()
            ->all();
    }

    /* --------------------------------------------------------------- utils */

    private function net(object $payment): float
    {
        return (float) $payment->pay_amount - (float) ($payment->change_amount ?? 0);
    }

    private function sumNet(Collection $rows): float
    {
        return (float) $rows->sum(fn ($p) => $this->net($p));
    }

    private function sum(Collection $rows, string $column): float
    {
        return (float) $rows->sum(fn ($p) => (float) ($p->{$column} ?? 0));
    }

    private function deltaPct(float|int $current, float|int $previous): ?float
    {
        if ((float) $previous == 0.0) {
            return null;
        }

        return ((float) $current - (float) $previous) / abs((float) $previous) * 100;
    }
}
