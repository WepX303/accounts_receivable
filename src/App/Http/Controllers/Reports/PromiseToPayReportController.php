<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Credit;
use App\Models\CreditPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PromiseToPayReportController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'branch' => 'nullable|string|max:100',
            'status' => 'nullable|in:all,upcoming,today,broken,kept',
            'q' => 'nullable|string|max:100',
        ]);

        $today = now()->startOfDay();

        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->date_from)->startOfDay()
            : now()->startOfDay();

        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->date_to)->endOfDay()
            : now()->addDays(7)->endOfDay();

        $branch = trim((string) $request->get('branch', ''));
        $status = (string) $request->get('status', 'all');
        $q = trim((string) $request->get('q', ''));

        $query = Credit::query()
            ->where('active', true)
            ->where('is_blocked', 0)
            ->whereNotNull('willpaiddate')
            ->whereRaw('COALESCE(amount_local, amount, 0) > COALESCE(paid_local, paid, 0)')
            ->whereBetween('willpaiddate', [$dateFrom, $dateTo]);

        if ($branch !== '') {
            $query->where('branch', $branch);
        }

        if ($q !== '') {
            $like = '%' . $q . '%';

            $query->where(function ($sub) use ($like) {
                $sub->where('name', 'ilike', $like)
                    ->orWhere('phone', 'ilike', $like)
                    ->orWhere('passport', 'ilike', $like)
                    ->orWhere('contract', 'ilike', $like)
                    ->orWhere('clientref', 'ilike', $like);
            });
        }

        $credits = $query
            ->orderBy('willpaiddate')
            ->get([
                'source_id',
                'logicalref',
                'branch',
                'name',
                'phone',
                'passport',
                'contract',
                'clientref',
                'date_',
                'amount_local',
                'amount',
                'paid_local',
                'paid',
                'willpaiddate',
                'willpaidamount',
                'status',
            ]);

        $rows = collect();

        foreach ($credits as $credit) {
            $total = (float) ($credit->amount_local ?? $credit->amount ?? 0);
            $paid = (float) ($credit->paid_local ?? $credit->paid ?? 0);
            $remaining = round(max($total - $paid, 0), 2);

            if ($total <= 0 || $remaining <= 0.01) {
                continue;
            }

            $promiseDate = Carbon::parse($credit->willpaiddate)->startOfDay();
            $promiseAmount = (float) ($credit->willpaidamount ?? 0);

            $paidOnOrAfterPromise = CreditPayment::query()
                ->notVoided()
                ->where('credit_source_id', (int) $credit->source_id)
                ->whereDate('created_at', '>=', $promiseDate->toDateString())
                ->sum(DB::raw('pay_amount - COALESCE(change_amount, 0)'));

            $paidOnOrAfterPromise = round((float) $paidOnOrAfterPromise, 2);

            $promiseStatus = 'upcoming';

            if ($promiseDate->isToday()) {
                $promiseStatus = 'today';
            } elseif ($promiseDate->isPast()) {
                $promiseStatus = $paidOnOrAfterPromise > 0 ? 'kept' : 'broken';
            }

            if ($status !== 'all' && $promiseStatus !== $status) {
                continue;
            }

            $rows->push((object) [
                'source_id' => $credit->source_id,
                'logicalref' => $credit->logicalref,
                'branch' => $credit->branch,
                'name' => $credit->name,
                'phone' => $credit->phone,
                'passport' => $credit->passport,
                'contract' => $credit->contract,
                'clientref' => $credit->clientref,
                'credit_date' => $credit->date_ ? Carbon::parse($credit->date_) : null,
                'total' => round($total, 2),
                'paid' => round($paid, 2),
                'remaining' => $remaining,
                'promise_date' => $promiseDate,
                'promise_amount' => round($promiseAmount, 2),
                'paid_after_promise' => $paidOnOrAfterPromise,
                'promise_status' => $promiseStatus,
                'promise_days' => $promiseDate->isPast() ? $promiseDate->diffInDays($today) : 0,
                'status' => $credit->status,
            ]);
        }

        $rows = $rows
            ->sortBy(function ($row) {
                $priority = match ($row->promise_status) {
                    'broken' => 1,
                    'today' => 2,
                    'upcoming' => 3,
                    'kept' => 4,
                    default => 5,
                };

                return $priority . '_' . $row->promise_date->format('Y-m-d') . '_' . $row->name;
            })
            ->values();

        $summary = [
            'customers_count' => $rows->count(),
            'promise_amount' => round((float) $rows->sum('promise_amount'), 2),
            'remaining_total' => round((float) $rows->sum('remaining'), 2),
            'paid_after_promise' => round((float) $rows->sum('paid_after_promise'), 2),
            'today_count' => $rows->where('promise_status', 'today')->count(),
            'upcoming_count' => $rows->where('promise_status', 'upcoming')->count(),
            'kept_count' => $rows->where('promise_status', 'kept')->count(),
            'broken_count' => $rows->where('promise_status', 'broken')->count(),
        ];

        $perPage = 50;
        $page = LengthAwarePaginator::resolveCurrentPage();

        $paginatedRows = new LengthAwarePaginator(
            $rows->slice(($page - 1) * $perPage, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $branches = Credit::query()
            ->whereNotNull('branch')
            ->where('branch', '!=', '')
            ->distinct()
            ->orderBy('branch')
            ->pluck('branch');

        return view('pages.reports.promise-to-pay.index', [
            'rows' => $paginatedRows,
            'summary' => $summary,
            'branches' => $branches,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'branch' => $branch,
            'status' => $status,
            'q' => $q,
            'today' => $today,
        ]);
    }
}