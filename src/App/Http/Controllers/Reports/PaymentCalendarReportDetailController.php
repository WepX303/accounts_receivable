<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Credit;
use App\Models\CreditPayment;
use App\Services\Reports\PaymentCalendarReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class PaymentCalendarReportDetailController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'type' => ['required', 'in:expected,expected-paid,received'],
            'q' => ['nullable', 'string', 'max:100'],
            'branch' => ['nullable', 'array'],
            'branch.*' => ['nullable', 'string', 'max:100'],
        ]);

        $date = Carbon::parse($data['date'])->startOfDay();
        $type = $data['type'];
        $q = trim((string) $request->get('q', ''));

        // Carried over from the calendar so the detail list shows the same
        // branches the totals were built from. Empty means all.
        $selectedBranches = PaymentCalendarReportService::normalizeBranches(
            $request->input('branch', [])
        );

        if ($type === 'received') {
            $rows = CreditPayment::query()
                ->notVoided()
                ->whereBetween('created_at', [
                    $date->copy()->startOfDay(),
                    $date->copy()->endOfDay(),
                ])
                ->when($selectedBranches !== [], fn ($query) => $query->whereIn('branch', $selectedBranches))
                ->when($q !== '', function ($query) use ($q) {
                    $like = '%' . $q . '%';

                    $query->where(function ($sub) use ($like) {
                        $sub->where('customer_name', 'ilike', $like)
                            ->orWhere('customer_contract', 'ilike', $like)
                            ->orWhere('customer_phone', 'ilike', $like)
                            ->orWhere('customer_passport', 'ilike', $like)
                            ->orWhere('branch', 'ilike', $like)
                            ->orWhere('created_by_name', 'ilike', $like);
                    });
                })
                ->orderByDesc('id')
                ->paginate(50)
                ->appends($request->query());

            return view('pages.reports.payment-calendar.details', compact('rows', 'date', 'type', 'q', 'selectedBranches'));
        }

        $credits = Credit::query()
            ->where('active', true)
            ->where('is_blocked', 0)
            ->whereNotNull('date_')
            ->whereRaw('COALESCE(amount_local, amount, 0) > COALESCE(paid_local, paid, 0)')
            ->when($selectedBranches !== [], fn ($query) => $query->whereIn('branch', $selectedBranches))
            ->whereRaw("
                EXISTS (
                    SELECT 1
                    FROM generate_series(1, 6) AS installment_no
                    WHERE (
                        date_::date + (installment_no * INTERVAL '1 month')
                    )::date = ?::date
                )
            ", [$date->toDateString()])
            ->orderBy('branch')
            ->orderBy('name')
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
            ]);

        $creditIds = $credits
            ->pluck('source_id')
            ->filter()
            ->map(fn ($v) => (int) $v)
            ->values();

        $paymentsByCredit = CreditPayment::query()
            ->notVoided()
            ->whereIn('credit_source_id', $creditIds)
            ->whereBetween('created_at', [
                $date->copy()->startOfDay(),
                $date->copy()->endOfDay(),
            ])
            ->selectRaw('credit_source_id')
            ->selectRaw('COUNT(*) as payment_count')
            ->selectRaw('COALESCE(SUM(pay_amount), 0) as gross_paid')
            ->selectRaw('COALESCE(SUM(change_amount), 0) as change_amount')
            ->selectRaw('COALESCE(SUM(pay_amount - COALESCE(change_amount, 0)), 0) as net_paid')
            ->selectRaw('MAX(created_at) as last_payment_at')
            ->groupBy('credit_source_id')
            ->get()
            ->keyBy('credit_source_id');

        $rows = collect();

        foreach ($credits as $credit) {
            $amount = (float) ($credit->amount_local ?? $credit->amount ?? 0);
            $paid = (float) ($credit->paid_local ?? $credit->paid ?? 0);
            $remaining = round(max($amount - $paid, 0), 2);
            $installment = round($amount / 6, 2);

            $paymentAgg = $paymentsByCredit->get((int) $credit->source_id);

            $paidToday = round((float) ($paymentAgg->net_paid ?? 0), 2);
            $grossToday = round((float) ($paymentAgg->gross_paid ?? 0), 2);
            $changeToday = round((float) ($paymentAgg->change_amount ?? 0), 2);
            $paymentCount = (int) ($paymentAgg->payment_count ?? 0);

            $missingToday = round(max($installment - $paidToday, 0), 2);

            $paymentStatus = 'unpaid';

            if ($paidToday >= $installment && $installment > 0) {
                $paymentStatus = 'paid';
            } elseif ($paidToday > 0 && $paidToday < $installment) {
                $paymentStatus = 'partial';
            }

            $rows->push((object) [
                'source_id' => $credit->source_id,
                'logicalref' => $credit->logicalref,
                'branch' => $credit->branch,
                'name' => $credit->name,
                'phone' => $credit->phone,
                'contract' => $credit->contract,
                'passport' => $credit->passport,
                'clientref' => $credit->clientref,
                'date_' => $credit->date_,

                'amount' => $amount,
                'paid' => $paid,
                'remaining' => $remaining,
                'installment' => $installment,

                'paid_today' => $paidToday,
                'gross_today' => $grossToday,
                'change_today' => $changeToday,
                'missing_today' => $missingToday,
                'payment_count' => $paymentCount,
                'payment_status' => $paymentStatus,
                'last_payment_at' => ! empty($paymentAgg?->last_payment_at)
                    ? Carbon::parse($paymentAgg->last_payment_at)
                    : null,
            ]);
        }

        if ($type === 'expected-paid') {
            $rows = $rows
                ->where('paid_today', '>', 0)
                ->sortByDesc('paid_today')
                ->values();
        }

        if ($q !== '') {
            $needle = mb_strtolower($q);

            $rows = $rows->filter(function ($row) use ($needle) {
                return str_contains(mb_strtolower((string) $row->name), $needle)
                    || str_contains(mb_strtolower((string) $row->contract), $needle)
                    || str_contains(mb_strtolower((string) $row->phone), $needle)
                    || str_contains(mb_strtolower((string) $row->passport), $needle)
                    || str_contains(mb_strtolower((string) $row->branch), $needle)
                    || str_contains(mb_strtolower((string) $row->clientref), $needle)
                    || str_contains(mb_strtolower((string) $row->source_id), $needle)
                    || str_contains(mb_strtolower((string) $row->logicalref), $needle);
            })->values();
        }

        $perPage = 50;
        $page = LengthAwarePaginator::resolveCurrentPage();

        $rows = new LengthAwarePaginator(
            $rows->slice(($page - 1) * $perPage, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('pages.reports.payment-calendar.details', compact('rows', 'date', 'type', 'q', 'selectedBranches'));
    }
}