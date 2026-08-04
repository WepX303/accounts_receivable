<?php

namespace App\Services\Reports;

use App\Models\CreditPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Void & correction audit.
 *
 * A correction (PaymentCorrectController) voids the original row AND inserts a new
 * row pointing back at it via corrected_from_payment_id. So a voided row is only a
 * real void when no other row was corrected from it - otherwise the same event
 * would be counted twice.
 */
class VoidCorrectionReportService
{
    public const TYPE_ALL = 'all';

    public const TYPE_VOID = 'void';

    public const TYPE_CORRECTION = 'correction';

    private Carbon $dateFrom;

    private Carbon $dateTo;

    private string $branch;

    private string $cashier;

    private string $actor;

    private string $type;

    private string $q;

    public function __construct(Request $request)
    {
        $this->dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->get('date_from'))->startOfDay()
            : now()->startOfMonth();

        $this->dateTo = $request->filled('date_to')
            ? Carbon::parse($request->get('date_to'))->endOfDay()
            : now()->endOfDay();

        $this->branch = trim((string) $request->get('branch', ''));
        $this->cashier = trim((string) $request->get('cashier', ''));
        $this->actor = trim((string) $request->get('actor', ''));
        $this->q = trim((string) $request->get('q', ''));

        $type = (string) $request->get('type', self::TYPE_ALL);
        $this->type = in_array($type, [self::TYPE_ALL, self::TYPE_VOID, self::TYPE_CORRECTION], true)
            ? $type
            : self::TYPE_ALL;
    }

    public function dateFrom(): Carbon
    {
        return $this->dateFrom;
    }

    public function dateTo(): Carbon
    {
        return $this->dateTo;
    }

    /**
     * Unified event list: one row per void or correction, newest event first.
     */
    public function rows(): Collection
    {
        $rows = collect();

        if ($this->type !== self::TYPE_CORRECTION) {
            $rows = $rows->merge($this->voidRows());
        }

        if ($this->type !== self::TYPE_VOID) {
            $rows = $rows->merge($this->correctionRows());
        }

        return $rows->sortByDesc('event_at')->values();
    }

    private function voidRows(): Collection
    {
        return $this->applyCommonFilters(
            CreditPayment::query()
                ->whereNotNull('credit_payments.voided_at')
                ->whereBetween('credit_payments.voided_at', [$this->dateFrom, $this->dateTo])
                // exclude the rows that were voided as the first half of a correction
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('credit_payments as corrected')
                        ->whereColumn('corrected.corrected_from_payment_id', 'credit_payments.id');
                })
                ->leftJoin('users as actor_user', 'actor_user.id', '=', 'credit_payments.voided_by')
                ->when($this->actor !== '', fn ($q) => $q->whereRaw(
                    "TRIM(CONCAT(actor_user.firstname, ' ', actor_user.lastname)) = ?",
                    [$this->actor]
                ))
                ->select('credit_payments.*')
                ->selectRaw("TRIM(CONCAT(actor_user.firstname, ' ', actor_user.lastname)) as actor_name")
        )
            ->orderByDesc('credit_payments.voided_at')
            ->get()
            ->map(function ($p) {
                $applied = $this->applied($p->pay_amount, $p->change_amount);

                return [
                    'type' => self::TYPE_VOID,
                    'event_at' => $p->voided_at,
                    'payment_id' => (int) $p->id,
                    'related_payment_id' => null,
                    'payment_at' => $p->created_at,
                    'customer_name' => $p->customer_name,
                    'customer_contract' => $p->customer_contract,
                    'customer_phone' => $p->customer_phone,
                    'branch' => $p->branch,
                    'cashier' => $p->created_by_name,
                    'old_amount' => $applied,
                    'new_amount' => 0.0,
                    'delta' => round(-$applied, 2),
                    'actor_name' => $p->actor_name,
                    'reason' => $p->void_reason,
                ];
            });
    }

    private function correctionRows(): Collection
    {
        return $this->applyCommonFilters(
            CreditPayment::query()
                ->whereNotNull('credit_payments.corrected_at')
                ->whereBetween('credit_payments.corrected_at', [$this->dateFrom, $this->dateTo])
                ->leftJoin('users as actor_user', 'actor_user.id', '=', 'credit_payments.corrected_by')
                ->leftJoin('credit_payments as previous', 'previous.id', '=', 'credit_payments.corrected_from_payment_id')
                ->when($this->actor !== '', fn ($q) => $q->whereRaw(
                    "TRIM(CONCAT(actor_user.firstname, ' ', actor_user.lastname)) = ?",
                    [$this->actor]
                ))
                ->select('credit_payments.*')
                ->selectRaw("TRIM(CONCAT(actor_user.firstname, ' ', actor_user.lastname)) as actor_name")
                ->selectRaw('previous.pay_amount as previous_pay_amount')
                ->selectRaw('previous.change_amount as previous_change_amount')
        )
            ->orderByDesc('credit_payments.corrected_at')
            ->get()
            ->map(function ($p) {
                $old = $this->applied($p->previous_pay_amount, $p->previous_change_amount);
                $new = $this->applied($p->pay_amount, $p->change_amount);

                return [
                    'type' => self::TYPE_CORRECTION,
                    'event_at' => $p->corrected_at,
                    'payment_id' => (int) $p->id,
                    'related_payment_id' => $p->corrected_from_payment_id !== null
                        ? (int) $p->corrected_from_payment_id
                        : null,
                    'payment_at' => $p->created_at,
                    'customer_name' => $p->customer_name,
                    'customer_contract' => $p->customer_contract,
                    'customer_phone' => $p->customer_phone,
                    'branch' => $p->branch,
                    'cashier' => $p->created_by_name,
                    'old_amount' => $old,
                    'new_amount' => $new,
                    'delta' => round($new - $old, 2),
                    'actor_name' => $p->actor_name,
                    'reason' => $p->correct_reason,
                ];
            });
    }

    private function applyCommonFilters($query)
    {
        return $query
            ->when($this->branch !== '', fn ($q) => $q->where('credit_payments.branch', $this->branch))
            ->when($this->cashier !== '', fn ($q) => $q->where('credit_payments.created_by_name', $this->cashier))
            ->when($this->q !== '', function ($q) {
                $like = '%' . $this->q . '%';

                $q->where(function ($sub) use ($like) {
                    $sub->where('credit_payments.customer_name', 'ilike', $like)
                        ->orWhere('credit_payments.customer_contract', 'ilike', $like)
                        ->orWhere('credit_payments.customer_phone', 'ilike', $like)
                        ->orWhere('credit_payments.customer_passport', 'ilike', $like);
                });
            });
    }

    public function summary(Collection $rows): array
    {
        $voids = $rows->where('type', self::TYPE_VOID);
        $corrections = $rows->where('type', self::TYPE_CORRECTION);

        $voidAmount = round((float) $voids->sum(fn ($r) => (float) $r['old_amount']), 2);
        $periodApplied = $this->periodAppliedTotal();

        return [
            'void_count' => $voids->count(),
            'void_amount' => $voidAmount,
            'correction_count' => $corrections->count(),
            'correction_delta' => round((float) $corrections->sum(fn ($r) => (float) $r['delta']), 2),
            'total_events' => $rows->count(),
            'period_applied' => $periodApplied,
            'void_rate' => $periodApplied > 0 ? round($voidAmount / $periodApplied * 100, 2) : 0.0,
        ];
    }

    /**
     * Net amount applied to debt by every payment booked in the period, voided
     * ones included - the denominator the void ratio is measured against.
     */
    private function periodAppliedTotal(): float
    {
        $row = $this->applyCommonFilters(
            CreditPayment::query()
                ->whereBetween('credit_payments.created_at', [$this->dateFrom, $this->dateTo])
        )
            ->selectRaw('COALESCE(SUM(GREATEST(pay_amount - COALESCE(change_amount, 0), 0)), 0) as applied_total')
            ->first();

        return round((float) ($row->applied_total ?? 0), 2);
    }

    /**
     * Per-actor breakdown: who voided/corrected how much.
     */
    public function actorRows(Collection $rows): Collection
    {
        return $rows
            ->groupBy(fn ($r) => $r['actor_name'] !== null && $r['actor_name'] !== ''
                ? $r['actor_name']
                : '-')
            ->map(function (Collection $group, $actor) {
                $voids = $group->where('type', self::TYPE_VOID);
                $corrections = $group->where('type', self::TYPE_CORRECTION);

                return [
                    'actor_name' => $actor,
                    'void_count' => $voids->count(),
                    'void_amount' => round((float) $voids->sum(fn ($r) => (float) $r['old_amount']), 2),
                    'correction_count' => $corrections->count(),
                    'correction_delta' => round((float) $corrections->sum(fn ($r) => (float) $r['delta']), 2),
                    'event_count' => $group->count(),
                    'net_effect' => round((float) $group->sum(fn ($r) => (float) $r['delta']), 2),
                ];
            })
            ->sortByDesc('event_count')
            ->values();
    }

    private function applied($payAmount, $changeAmount): float
    {
        $applied = (float) $payAmount - (float) ($changeAmount ?? 0);

        return round(max($applied, 0), 2);
    }

    public function branches(): Collection
    {
        return CreditPayment::query()
            ->whereNotNull('branch')
            ->where('branch', '!=', '')
            ->distinct()
            ->orderBy('branch')
            ->pluck('branch');
    }

    public function cashiers(): Collection
    {
        return CreditPayment::query()
            ->whereNotNull('created_by_name')
            ->where('created_by_name', '!=', '')
            ->distinct()
            ->orderBy('created_by_name')
            ->pluck('created_by_name');
    }

    /**
     * Users who have actually voided or corrected something.
     */
    public function actors(): Collection
    {
        return DB::table('users')
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('credit_payments')
                    ->whereColumn('credit_payments.voided_by', 'users.id');
            })
            ->orWhereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('credit_payments')
                    ->whereColumn('credit_payments.corrected_by', 'users.id');
            })
            ->selectRaw("TRIM(CONCAT(firstname, ' ', lastname)) as full_name")
            ->distinct()
            ->orderBy('full_name')
            ->pluck('full_name');
    }
}
