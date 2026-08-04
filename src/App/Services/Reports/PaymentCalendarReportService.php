<?php

namespace App\Services\Reports;

use App\Models\Credit;
use App\Models\CreditPayment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Day-by-day expected vs. collected calendar for a single month.
 *
 * Shared by the screen and the Excel export so the two cannot drift apart.
 *
 * Branch filtering: an empty selection means every branch. Expected amounts are
 * filtered on credits.branch and received amounts on credit_payments.branch;
 * the payment row carries a snapshot of the credit's branch, so the two sides
 * stay on the same footing. "Paid expected" needs no branch clause of its own —
 * it is already scoped to the credits that survived the filter.
 */
class PaymentCalendarReportService
{
    private const INSTALLMENTS = 6;

    private Carbon $month;
    private Carbon $start;
    private Carbon $end;

    /** @var string[] */
    private array $branches;

    private ?Collection $days = null;
    private ?array $summary = null;

    /**
     * @param  string[]  $branches  Empty means all branches.
     */
    public function __construct(?string $month = null, array $branches = [])
    {
        try {
            $this->month = $month
                ? Carbon::parse($month . '-01')->startOfMonth()
                : now()->startOfMonth();
        } catch (\Throwable) {
            $this->month = now()->startOfMonth();
        }

        $this->start = $this->month->copy()->startOfMonth();
        $this->end = $this->month->copy()->endOfMonth();
        $this->branches = self::normalizeBranches($branches);
    }

    /**
     * Trim, drop blanks and de-duplicate whatever came in from the request.
     *
     * @return string[]
     */
    public static function normalizeBranches(mixed $branches): array
    {
        return collect(is_array($branches) ? $branches : [$branches])
            ->map(fn ($b) => trim((string) $b))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function month(): Carbon
    {
        return $this->month->copy();
    }

    /** @return string[] */
    public function branches(): array
    {
        return $this->branches;
    }

    public function hasBranchFilter(): bool
    {
        return $this->branches !== [];
    }

    /**
     * Every branch that appears on a credit, for the filter dropdown.
     */
    public function availableBranches(): Collection
    {
        // Keyed by the viewer's branch access — the list is scoped, so one cache
        // shared across users would hand out branches they cannot open.
        $key = 'payment_calendar:branches:' . (auth()->user()?->branchCacheKey() ?? 'guest');

        return Cache::remember($key, now()->addMinutes(30), function () {
            return Credit::query()
                ->whereNotNull('branch')
                ->where('branch', '!=', '')
                ->distinct()
                ->orderBy('branch')
                ->pluck('branch');
        });
    }

    public function days(): Collection
    {
        return $this->days ??= $this->buildDays();
    }

    public function summary(): array
    {
        if ($this->summary !== null) {
            return $this->summary;
        }

        $days = $this->days();

        $summary = [
            'expected_total' => round((float) $days->sum('expected'), 2),
            'paid_expected_total' => round((float) $days->sum('paid_expected'), 2),
            'total_received_total' => round((float) $days->sum('total_received'), 2),
            'difference_total' => round((float) $days->sum('difference'), 2),
            'change_total' => round((float) $days->sum('change'), 2),
        ];

        // Kept for the older blade/export markup that still reads this name.
        $summary['received_total'] = $summary['paid_expected_total'];

        $summary['percent_total'] = $summary['expected_total'] > 0
            ? round(($summary['paid_expected_total'] / $summary['expected_total']) * 100, 2)
            : 0;

        return $this->summary = $summary;
    }

    /* ---------------------------------------------------------------- build */

    private function buildDays(): Collection
    {
        [$expectedByDate, $creditIdsByDate] = $this->expectedSchedule();

        $totalReceived = $this->paymentSums('pay_amount - COALESCE(change_amount, 0)');
        $change = $this->paymentSums('COALESCE(change_amount, 0)');
        $paidExpected = $this->paidExpected($creditIdsByDate);

        $days = collect();
        $cursor = $this->start->copy();

        while ($cursor <= $this->end) {
            $key = $cursor->toDateString();

            $expected = round((float) ($expectedByDate[$key] ?? 0), 2);
            $paid = round((float) ($paidExpected[$key] ?? 0), 2);
            $received = round((float) ($totalReceived[$key] ?? 0), 2);

            $days->push([
                'date' => $cursor->copy(),
                'date_key' => $key,
                'day_name' => $cursor->format('l'),

                'expected' => $expected,
                'paid_expected' => $paid,
                'total_received' => $received,

                // Kept for the older blade/export markup that still reads this name.
                'received' => $paid,

                'difference' => round($paid - $expected, 2),
                'change' => round((float) ($change[$key] ?? 0), 2),
                'percent' => $expected > 0 ? round($paid / $expected * 100, 2) : 0,

                'is_today' => $cursor->isToday(),
                'is_past' => $cursor->isPast() && ! $cursor->isToday(),
                'is_future' => $cursor->isFuture(),
            ]);

            $cursor->addDay();
        }

        return $days;
    }

    /**
     * Spread each credit over its six monthly installments and keep the ones
     * falling inside the reported month.
     *
     * @return array{0: Collection, 1: Collection}
     */
    private function expectedSchedule(): array
    {
        $credits = Credit::query()
            ->where('active', true)
            ->where('is_blocked', 0)
            ->whereNotNull('date_')
            ->whereRaw('COALESCE(amount_local, amount, 0) > COALESCE(paid_local, paid, 0)')
            ->when($this->hasBranchFilter(), fn ($q) => $q->whereIn('branch', $this->branches))
            ->get(['source_id', 'date_', 'amount_local', 'amount', 'paid_local', 'paid']);

        $expectedByDate = collect();
        $creditIdsByDate = collect();

        foreach ($credits as $credit) {
            $amount = (float) ($credit->amount_local ?? $credit->amount ?? 0);

            if ($amount <= 0) {
                continue;
            }

            $installment = round($amount / self::INSTALLMENTS, 2);

            if ($installment <= 0) {
                continue;
            }

            $creditDate = Carbon::parse($credit->date_)->startOfDay();

            for ($i = 1; $i <= self::INSTALLMENTS; $i++) {
                $due = $creditDate->copy()->addMonthsNoOverflow($i)->startOfDay();

                if (! $due->betweenIncluded($this->start, $this->end)) {
                    continue;
                }

                $key = $due->toDateString();

                $expectedByDate[$key] = (float) ($expectedByDate[$key] ?? 0) + $installment;

                if (! isset($creditIdsByDate[$key])) {
                    $creditIdsByDate[$key] = collect();
                }

                $creditIdsByDate[$key]->push((int) $credit->source_id);
            }
        }

        return [$expectedByDate, $creditIdsByDate];
    }

    /**
     * Daily totals over every payment in the month, keyed by date.
     */
    private function paymentSums(string $expression): Collection
    {
        return CreditPayment::query()
            ->notVoided()
            ->selectRaw('DATE(created_at)::text as pay_date')
            ->selectRaw("COALESCE(SUM($expression), 0) as amount")
            ->whereBetween('created_at', [
                $this->start->copy()->startOfDay(),
                $this->end->copy()->endOfDay(),
            ])
            ->when($this->hasBranchFilter(), fn ($q) => $q->whereIn('branch', $this->branches))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('amount', 'pay_date');
    }

    /**
     * Of the money taken on a given day, how much came from customers who were
     * actually due that day. One grouped query rather than one per date.
     */
    private function paidExpected(Collection $creditIdsByDate): Collection
    {
        if ($creditIdsByDate->isEmpty()) {
            return collect();
        }

        $allIds = $creditIdsByDate
            ->flatten()
            ->filter()
            ->unique()
            ->values();

        if ($allIds->isEmpty()) {
            return collect();
        }

        $paidByDateAndCredit = CreditPayment::query()
            ->notVoided()
            ->whereIn('credit_source_id', $allIds)
            ->whereBetween('created_at', [
                $this->start->copy()->startOfDay(),
                $this->end->copy()->endOfDay(),
            ])
            ->selectRaw('DATE(created_at)::text as pay_date')
            ->selectRaw('credit_source_id')
            ->selectRaw('COALESCE(SUM(pay_amount - COALESCE(change_amount, 0)), 0) as net')
            ->groupBy(DB::raw('DATE(created_at)'), 'credit_source_id')
            ->get()
            ->groupBy('pay_date');

        $result = collect();

        foreach ($creditIdsByDate as $dateKey => $creditIds) {
            $dueIds = $creditIds->filter()->unique()->flip();
            $rows = $paidByDateAndCredit->get($dateKey);

            $result[$dateKey] = $rows === null
                ? 0.0
                : (float) $rows
                    ->filter(fn ($r) => $dueIds->has((int) $r->credit_source_id))
                    ->sum(fn ($r) => (float) $r->net);
        }

        return $result;
    }
}
