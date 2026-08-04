<?php

namespace App\Exports;

use App\Models\Credit;
use App\Models\CreditPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OverduePaymentsReportExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(
        private Request $request
    ) {}

    public function headings(): array
    {
        return [
            __('pages/reports.common.credit_id'),
            __('pages/reports.export.logicalref'),
            __('pages/reports.common.customer'),
            __('pages/reports.common.contract'),
            __('pages/reports.common.phone'),
            __('pages/reports.common.passport'),
            __('pages/reports.common.branch'),
            __('pages/reports.export.clientref'),
            __('pages/reports.common.credit_date'),
            __('pages/reports.overdue_payments.last_due_date'),
            __('pages/reports.export.monthly_payment'),
            __('pages/reports.export.due_installments'),
            __('pages/reports.common.total_debt'),
            __('pages/reports.common.paid'),
            __('pages/reports.common.expected_paid'),
            __('pages/reports.overdue_payments.overdue_amount'),
            __('pages/reports.common.remaining'),
            __('pages/reports.overdue_payments.overdue_days'),
            __('pages/reports.export.credit_status'),

            __('pages/reports.export.payment_id'),
            __('pages/reports.export.payment_date'),
            __('pages/reports.export.payment_method'),
            __('pages/reports.export.received_amount'),
            __('pages/reports.export.change_amount'),
            __('pages/reports.export.net_applied'),
            __('pages/reports.export.cash_amount'),
            __('pages/reports.export.card_amount'),
            __('pages/reports.export.phone_amount'),
            __('pages/reports.common.cashier'),
            __('pages/reports.export.receiver_phone'),
            __('pages/reports.export.payment_note'),
        ];
    }

    public function collection(): Collection
    {
        $today = now()->startOfDay();
        $todaySql = $today->toDateString();

        $q = trim((string) $this->request->get('q', ''));
        $branch = trim((string) $this->request->get('branch', ''));
        $minOverdue = $this->request->filled('min_overdue')
            ? (float) $this->request->get('min_overdue')
            : null;

        $minDays = $this->request->filled('min_days')
            ? (int) $this->request->get('min_days')
            : null;

        $creditsQuery = Credit::query()
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
            ", [$todaySql]);

        if ($q !== '') {
            $like = '%' . $q . '%';

            $creditsQuery->where(function ($sub) use ($like) {
                $sub->where('name', 'ilike', $like)
                    ->orWhere('phone', 'ilike', $like)
                    ->orWhere('passport', 'ilike', $like)
                    ->orWhere('contract', 'ilike', $like)
                    ->orWhere('clientref', 'ilike', $like);
            });
        }

        if ($branch !== '') {
            $creditsQuery->where('branch', $branch);
        }

        $credits = $creditsQuery->get([
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
            'status',
        ]);

        $rows = collect();

        foreach ($credits as $credit) {
            $total = (float) ($credit->amount_local ?? $credit->amount ?? 0);
            $paid = (float) ($credit->paid_local ?? $credit->paid ?? 0);

            if ($total <= 0) {
                continue;
            }

            $monthly = round($total / 6, 2);
            $creditDate = Carbon::parse($credit->date_)->startOfDay();

            $dueInstallmentCount = 0;
            $lastDueDate = null;

            for ($i = 1; $i <= 6; $i++) {
                $dueDate = $creditDate->copy()->addMonthsNoOverflow($i)->startOfDay();

                if ($today->gte($dueDate)) {
                    $dueInstallmentCount = $i;
                    $lastDueDate = $dueDate;
                }
            }

            if ($dueInstallmentCount <= 0) {
                continue;
            }

            $expectedPaid = round(min($monthly * $dueInstallmentCount, $total), 2);
            $overdueAmount = round(max($expectedPaid - $paid, 0), 2);

            if ($overdueAmount < 10) {
                continue;
            }

            $overdueDays = $lastDueDate ? $lastDueDate->diffInDays($today) : 0;

            if ($minOverdue !== null && $overdueAmount < $minOverdue) {
                continue;
            }

            if ($minDays !== null && $overdueDays < $minDays) {
                continue;
            }

            $remaining = round(max($total - $paid, 0), 2);

            $payments = CreditPayment::query()
                ->notVoided()
                ->where('credit_source_id', (int) $credit->source_id)
                ->orderBy('created_at')
                ->get([
                    'id',
                    'created_at',
                    'method',
                    'pay_amount',
                    'change_amount',
                    'cash_amount',
                    'card_amount',
                    'phone_amount',
                    'created_by_name',
                    'receiver_phone_number',
                    'note',
                ]);

            if ($payments->isEmpty()) {
                $rows->push($this->baseRow(
                    $credit,
                    $creditDate,
                    $lastDueDate,
                    $monthly,
                    $dueInstallmentCount,
                    $total,
                    $paid,
                    $expectedPaid,
                    $overdueAmount,
                    $remaining,
                    $overdueDays
                ));

                continue;
            }

            foreach ($payments as $payment) {
                $received = (float) $payment->pay_amount;
                $change = (float) ($payment->change_amount ?? 0);
                $net = round($received - $change, 2);

                $rows->push(array_merge(
                    $this->baseRow(
                        $credit,
                        $creditDate,
                        $lastDueDate,
                        $monthly,
                        $dueInstallmentCount,
                        $total,
                        $paid,
                        $expectedPaid,
                        $overdueAmount,
                        $remaining,
                        $overdueDays
                    ),
                    [
                        $payment->id,
                        $payment->created_at ? Carbon::parse($payment->created_at)->format('Y-m-d H:i') : null,
                        $payment->method,
                        round($received, 2),
                        round($change, 2),
                        $net,
                        round((float) ($payment->cash_amount ?? 0), 2),
                        round((float) ($payment->card_amount ?? 0), 2),
                        round((float) ($payment->phone_amount ?? 0), 2),
                        $payment->created_by_name,
                        $payment->receiver_phone_number,
                        $payment->note,
                    ]
                ));
            }
        }

        $rows = $rows->sortBy([
            [6, 'asc'],
            [2, 'asc'],
        ])->values();

        $rows->push($this->totalRow($rows));

        return $rows;
    }

    private function baseRow(
        Credit $credit,
        Carbon $creditDate,
        ?Carbon $lastDueDate,
        float $monthly,
        int $dueInstallmentCount,
        float $total,
        float $paid,
        float $expectedPaid,
        float $overdueAmount,
        float $remaining,
        int $overdueDays
    ): array {
        return [
            $credit->source_id,
            $credit->logicalref,
            $credit->name,
            $credit->contract,
            $credit->phone,
            $credit->passport,
            $credit->branch,
            $credit->clientref,
            $creditDate->format('Y-m-d'),
            $lastDueDate?->format('Y-m-d'),
            $monthly,
            $dueInstallmentCount,
            round($total, 2),
            round($paid, 2),
            round($expectedPaid, 2),
            round($overdueAmount, 2),
            round($remaining, 2),
            $overdueDays,
            $credit->status,
        ];
    }

    private function totalRow(Collection $rows): array
    {
        return [
            '',
            '',
            __('pages/reports.export.total_upper'),
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            round((float) $rows->sum(fn ($r) => (float) ($r[12] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[13] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[14] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[15] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[16] ?? 0)), 2),
            '',
            '',
            '',
            '',
            '',
            round((float) $rows->sum(fn ($r) => (float) ($r[22] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[23] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[24] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[25] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[26] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[27] ?? 0)), 2),
            '',
            '',
            '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $highestRow = $sheet->getHighestRow();

        $sheet->getStyle('1:1')->getFont()->setBold(true);
        $sheet->getStyle($highestRow . ':' . $highestRow)->getFont()->setBold(true);

        return [];
    }
}