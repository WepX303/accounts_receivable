<?php

namespace App\Services\Reports;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Balance reconciliation audit.
 *
 * credits.paid_local is only ever written by the payment, void and correction
 * flows (CustomersPaymentController, PaymentVoidController, PaymentCorrectController)
 * plus the one-time local seeding. Every payment row also stores the balance it
 * started from (old_paid_local), so the balance a credit *should* carry is:
 *
 *   baseline (old_paid_local of the first payment row)
 *   + SUM(applied) over every payment that is not voided
 *
 * Anything else means paid_local moved outside the payment flow.
 */
class BalanceIntegrityReportService
{
    private const TOLERANCE = 0.01;

    private string $branch;

    private string $q;

    private float $minDiff;

    private bool $onlyMismatch;

    private string $sort;

    private ?Carbon $dateFrom;

    private ?Carbon $dateTo;

    public function __construct(Request $request)
    {
        $this->branch = trim((string) $request->get('branch', ''));
        $this->q = trim((string) $request->get('q', ''));
        $this->minDiff = (float) $request->get('min_diff', 0);
        $this->onlyMismatch = $request->get('only_mismatch', '1') === '1';
        $this->sort = (string) $request->get('sort', 'diff_desc');

        $this->dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->get('date_from'))->startOfDay()
            : null;

        $this->dateTo = $request->filled('date_to')
            ? Carbon::parse($request->get('date_to'))->endOfDay()
            : null;
    }

    public function dateFrom(): ?Carbon
    {
        return $this->dateFrom;
    }

    public function dateTo(): ?Carbon
    {
        return $this->dateTo;
    }

    /**
     * Branches the signed-in user may see, or null when unrestricted.
     *
     * This service reaches for the query builder rather than the Eloquent
     * models, so the BranchScope global scope never fires here and the
     * restriction has to be applied by hand. Returns null in console context,
     * which is what keeps scheduled runs seeing everything.
     *
     * @return string[]|null
     */
    private function viewerBranches(): ?array
    {
        return auth()->user()?->allowedBranches();
    }

    public function rows(): Collection
    {
        $baseline = DB::table('credit_payments')
            ->selectRaw('DISTINCT ON (credit_source_id) credit_source_id, old_paid_local as baseline_paid')
            ->whereNotNull('old_paid_local')
            ->orderBy('credit_source_id')
            ->orderBy('id');

        $applied = DB::table('credit_payments')
            ->selectRaw('credit_source_id')
            ->selectRaw('COALESCE(SUM(GREATEST(pay_amount - COALESCE(change_amount, 0), 0)), 0) as applied_sum')
            ->selectRaw('COUNT(*) as active_payment_count')
            ->whereNull('voided_at')
            ->groupBy('credit_source_id');

        $voided = DB::table('credit_payments')
            ->selectRaw('credit_source_id')
            ->selectRaw('COUNT(*) as voided_payment_count')
            ->whereNotNull('voided_at')
            ->groupBy('credit_source_id');

        $query = DB::table('credits as c')
            ->joinSub($baseline, 'b', fn ($j) => $j->on('b.credit_source_id', '=', 'c.source_id'))
            ->leftJoinSub($applied, 'a', fn ($j) => $j->on('a.credit_source_id', '=', 'c.source_id'))
            ->leftJoinSub($voided, 'v', fn ($j) => $j->on('v.credit_source_id', '=', 'c.source_id'))
            ->whereNotNull('c.paid_local')
            ->when($this->branch !== '', fn ($q) => $q->where('c.branch', $this->branch))
            ->when(
                $this->viewerBranches() !== null,
                fn ($q) => $q->whereIn('c.branch', $this->viewerBranches())
            )
            ->when($this->dateFrom !== null, fn ($q) => $q->where('c.paid_updated_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== null, fn ($q) => $q->where('c.paid_updated_at', '<=', $this->dateTo))
            ->when($this->q !== '', function ($q) {
                $like = '%' . $this->q . '%';

                $q->where(function ($sub) use ($like) {
                    $sub->where('c.name', 'ilike', $like)
                        ->orWhere('c.contract', 'ilike', $like)
                        ->orWhere('c.phone', 'ilike', $like)
                        ->orWhere('c.passport', 'ilike', $like);
                });
            })
            ->select([
                'c.source_id',
                'c.logicalref',
                'c.name',
                'c.contract',
                'c.phone',
                'c.branch',
                'c.amount_local',
                'c.paid_local',
                'c.paid_updated_at',
                'c.paid_note',
                'c.active',
                'b.baseline_paid',
            ])
            ->selectRaw('COALESCE(a.applied_sum, 0) as applied_sum')
            ->selectRaw('COALESCE(a.active_payment_count, 0) as active_payment_count')
            ->selectRaw('COALESCE(v.voided_payment_count, 0) as voided_payment_count');

        $rows = $query->get()->map(function ($r) {
            $baselinePaid = round((float) $r->baseline_paid, 2);
            $appliedSum = round((float) $r->applied_sum, 2);
            $expected = round($baselinePaid + $appliedSum, 2);
            $actual = round((float) $r->paid_local, 2);
            $diff = round($actual - $expected, 2);

            return [
                'source_id' => (int) $r->source_id,
                'logicalref' => (int) $r->logicalref,
                'name' => $r->name,
                'contract' => $r->contract,
                'phone' => $r->phone,
                'branch' => $r->branch,
                'amount_local' => round((float) $r->amount_local, 2),
                'baseline_paid' => $baselinePaid,
                'applied_sum' => $appliedSum,
                'expected_paid' => $expected,
                'actual_paid' => $actual,
                'diff' => $diff,
                'abs_diff' => abs($diff),
                'active_payment_count' => (int) $r->active_payment_count,
                'voided_payment_count' => (int) $r->voided_payment_count,
                'paid_updated_at' => $r->paid_updated_at,
                'paid_note' => $r->paid_note,
                'is_mismatch' => abs($diff) > self::TOLERANCE,
            ];
        });

        if ($this->onlyMismatch) {
            $rows = $rows->where('is_mismatch', true);
        }

        if ($this->minDiff > 0) {
            $rows = $rows->where('abs_diff', '>=', $this->minDiff);
        }

        $rows = match ($this->sort) {
            'diff_asc' => $rows->sortBy('abs_diff'),
            'customer_asc' => $rows->sortBy('name'),
            'branch_asc' => $rows->sortBy('branch'),
            'recent' => $rows->sortByDesc('paid_updated_at'),
            default => $rows->sortByDesc('abs_diff'),
        };

        return $rows->values();
    }

    public function summary(Collection $rows): array
    {
        $mismatches = $rows->where('is_mismatch', true);
        $over = $mismatches->where('diff', '>', 0);
        $under = $mismatches->where('diff', '<', 0);

        return [
            'checked_count' => $rows->count(),
            'mismatch_count' => $mismatches->count(),
            'over_count' => $over->count(),
            'over_total' => round((float) $over->sum('diff'), 2),
            'under_count' => $under->count(),
            'under_total' => round((float) $under->sum('diff'), 2),
            'net_total' => round((float) $mismatches->sum('diff'), 2),
            'abs_total' => round((float) $mismatches->sum('abs_diff'), 2),
        ];
    }

    /**
     * Branch level rollup of the discrepancies.
     */
    public function branchRows(Collection $rows): Collection
    {
        return $rows
            ->where('is_mismatch', true)
            ->groupBy(fn ($r) => $r['branch'] !== null && $r['branch'] !== '' ? $r['branch'] : '-')
            ->map(fn (Collection $group, $branch) => [
                'branch' => $branch,
                'credit_count' => $group->count(),
                'net_total' => round((float) $group->sum('diff'), 2),
                'abs_total' => round((float) $group->sum('abs_diff'), 2),
            ])
            ->sortByDesc('abs_total')
            ->values();
    }

    public function branches(): Collection
    {
        return DB::table('credits')
            ->whereNotNull('branch')
            ->where('branch', '!=', '')
            ->when(
                $this->viewerBranches() !== null,
                fn ($q) => $q->whereIn('branch', $this->viewerBranches())
            )
            ->distinct()
            ->orderBy('branch')
            ->pluck('branch');
    }
}
