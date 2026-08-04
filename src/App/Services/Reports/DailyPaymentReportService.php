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

    public function getTodayReport(): array
    {
        return $this->getReport(Carbon::today());
    }

    public function getReport(?CarbonInterface $date = null): array
    {
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
            ],
            'summary' => $this->summary($payments),
            'comparison' => $this->comparison($start),
            'trend' => $this->trend($start),
            'methods' => $this->methodBreakdown($payments),
            'branches' => $this->branchBreakdown($payments),
            'cashiers' => $this->cashierBreakdown($payments),
            'top_payments' => $this->topPayments($payments),
            'audit' => $this->audit($start, $end, $payments),
            'portfolio' => $this->portfolio($start),
        ];
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

        // Same slice of the previous month: 1st through the same day number,
        // clamped so that e.g. the 31st does not overflow a 30-day month.
        $prevMonthStart = $day->copy()->subMonthNoOverflow()->startOfMonth();
        $prevMonthEnd = $prevMonthStart->copy()
            ->addDays(min($day->day, $prevMonthStart->daysInMonth) - 1)
            ->endOfDay();
        $prevMtd = $this->netBetween($prevMonthStart, $prevMonthEnd);

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
            'prev_mtd' => [
                'net' => $prevMtd['net'],
                'label' => $prevMonthStart->format('m.Y'),
                'net_delta_pct' => $this->deltaPct($mtd['net'], $prevMtd['net']),
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
     * Payments *entered* on the report day that carry an earlier payment date.
     * The payment screen already flags these, so we read the flag rather than
     * re-deriving it. These entries change days that were already closed.
     */
    private function backdated(Carbon $start, Carbon $end): array
    {
        $rows = DB::table('activity_logs')
            ->where('action', 'payment_created')
            ->whereBetween('created_at', [$start, $end])
            ->whereRaw("extra->>'backdated' = 'true'")
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
            ->whereRaw('COALESCE(amount_local, amount, 0) > COALESCE(paid_local, paid, 0)')
            ->distinct()
            ->orderBy('branch')
            ->pluck('branch')
            ->reject(fn ($b) => $active->contains($b))
            ->values()
            ->all();
    }

    /* ----------------------------------------------------------- portfolio */

    /**
     * Context for the day's number: what is still on the books, and how much of
     * it is already late. Overdue matches OverduePaymentsReportController — the
     * 6-installment plan derived from the credit date.
     */
    private function portfolio(Carbon $day): array
    {
        $today = $day->toDateString();

        $open = DB::table('credits')
            ->where('active', true)
            ->where('is_blocked', 0)
            ->whereRaw('COALESCE(amount_local, amount, 0) > COALESCE(paid_local, paid, 0)')
            ->selectRaw('COUNT(*) as credit_count')
            ->selectRaw('COALESCE(SUM(COALESCE(amount_local, amount, 0) - COALESCE(paid_local, paid, 0)), 0) as open_balance')
            ->first();

        $overdue = DB::table('credits')
            ->where('active', true)
            ->where('is_blocked', 0)
            ->whereNotNull('date_')
            ->whereRaw('COALESCE(amount_local, amount, 0) > 0')
            ->whereRaw('COALESCE(amount_local, amount, 0) > COALESCE(paid_local, paid, 0)')
            ->whereRaw("
                (
                    LEAST(GREATEST(FLOOR((?::date - date_::date) / 30), 0), 6)
                    * ROUND((COALESCE(amount_local, amount, 0) / 6)::numeric, 2)
                ) > COALESCE(paid_local, paid, 0)
            ", [$today])
            ->selectRaw('COUNT(*) as credit_count')
            ->selectRaw("
                COALESCE(SUM(
                    LEAST(GREATEST(FLOOR((?::date - date_::date) / 30), 0), 6)
                    * ROUND((COALESCE(amount_local, amount, 0) / 6)::numeric, 2)
                    - COALESCE(paid_local, paid, 0)
                ), 0) as overdue_amount
            ", [$today])
            ->first();

        $openBalance = (float) ($open->open_balance ?? 0);
        $overdueAmount = (float) ($overdue->overdue_amount ?? 0);

        return [
            'credit_count' => (int) ($open->credit_count ?? 0),
            'open_balance' => $openBalance,
            'overdue_credit_count' => (int) ($overdue->credit_count ?? 0),
            'overdue_amount' => $overdueAmount,
            'overdue_share' => $openBalance > 0 ? $overdueAmount / $openBalance * 100 : 0.0,
        ];
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
