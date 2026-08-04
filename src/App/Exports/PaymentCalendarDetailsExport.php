<?php

namespace App\Exports;

use App\Models\Credit;
use App\Models\CreditPayment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PaymentCalendarDetailsExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(
        private string $type,
        private string $date
    ) {}

    public function headings(): array
    {
        if ($this->type === 'received') {
            return [
                __('pages/reports.export.payment_id'),
                __('pages/reports.export.credit_source_id'),
                __('pages/reports.export.credit_logicalref'),
                __('pages/reports.common.customer'),
                __('pages/reports.common.contract'),
                __('pages/reports.common.phone'),
                __('pages/reports.common.passport'),
                __('pages/reports.common.branch'),
                __('pages/reports.common.cashier'),
                __('pages/reports.export.cashier_email'),
                __('pages/reports.export.cashier_phone'),
                __('pages/reports.export.payment_date'),
                __('pages/reports.common.method'),
                __('pages/reports.export.received_amount'),
                __('pages/reports.export.change_amount'),
                __('pages/reports.export.net_applied'),
                __('pages/reports.export.cash_amount'),
                __('pages/reports.export.card_amount'),
                __('pages/reports.export.phone_amount'),
                __('pages/reports.export.receiver_phone'),
                __('pages/reports.export.corrected'),
                __('pages/reports.export.corrected_at'),
                __('pages/reports.common.note'),
            ];
        }

        return [
            __('pages/reports.common.credit_id'),
            __('pages/reports.export.logicalref'),
            __('pages/reports.common.customer'),
            __('pages/reports.common.contract'),
            __('pages/reports.common.phone'),
            __('pages/reports.common.passport'),
            __('pages/reports.common.branch'),
            __('pages/reports.export.clientref'),
            __('pages/reports.export.assurance'),
            __('pages/reports.export.manager'),
            __('pages/reports.common.status'),
            __('pages/reports.common.credit_date'),
            __('pages/reports.export.due_date'),
            __('pages/reports.export.expected_installment'),
            __('pages/reports.export.paid_today'),
            __('pages/reports.export.gross_today'),
            __('pages/reports.export.change_today'),
            __('pages/reports.export.missing_today'),
            __('pages/reports.export.payment_count'),
            __('pages/reports.export.payment_status'),
            __('pages/reports.export.last_payment_at'),
            __('pages/reports.common.total_debt'),
            __('pages/reports.export.paid_total'),
            __('pages/reports.export.remaining_total'),
            __('pages/reports.export.remote_amount'),
            __('pages/reports.export.remote_paid'),
            __('pages/reports.export.local_amount'),
            __('pages/reports.export.local_paid'),
            __('pages/reports.export.will_pay_date'),
            __('pages/reports.export.will_pay_amount'),
            __('pages/reports.common.note'),
        ];
    }

    public function collection(): Collection
    {
        return $this->type === 'received'
            ? $this->receivedRows()
            : $this->expectedRows();
    }

    private function expectedRows(): Collection
    {
        $targetDate = Carbon::parse($this->date)->startOfDay();

        $credits = Credit::query()
            ->where('active', true)
            ->where('is_blocked', 0)
            ->whereNotNull('date_')
            ->whereRaw('COALESCE(amount_local, amount, 0) > COALESCE(paid_local, paid, 0)')
            ->whereRaw("
                EXISTS (
                    SELECT 1
                    FROM generate_series(1, 6) AS installment_no
                    WHERE (
                        date_::date + (installment_no * INTERVAL '1 month')
                    )::date = ?::date
                )
            ", [$targetDate->toDateString()])
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
                'assurance',
                'manager',
                'status',
                'date_',
                'amount_local',
                'amount',
                'paid_local',
                'paid',
                'willpaiddate',
                'willpaidamount',
                'note',
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
                $targetDate->copy()->startOfDay(),
                $targetDate->copy()->endOfDay(),
            ])
            ->selectRaw('credit_source_id')
            ->selectRaw('COUNT(*) as payment_count')
            ->selectRaw('COALESCE(SUM(pay_amount), 0) as gross_today')
            ->selectRaw('COALESCE(SUM(change_amount), 0) as change_today')
            ->selectRaw('COALESCE(SUM(pay_amount - COALESCE(change_amount, 0)), 0) as paid_today')
            ->selectRaw('MAX(created_at) as last_payment_at')
            ->groupBy('credit_source_id')
            ->get()
            ->keyBy('credit_source_id');

        $rows = collect();

        foreach ($credits as $c) {
            $amount = (float) ($c->amount_local ?? $c->amount ?? 0);
            $paid = (float) ($c->paid_local ?? $c->paid ?? 0);

            if ($amount <= 0) {
                continue;
            }

            $installment = round($amount / 6, 2);
            $remaining = round(max($amount - $paid, 0), 2);
            $creditDate = Carbon::parse($c->date_)->startOfDay();

            $paymentAgg = $paymentsByCredit->get((int) $c->source_id);

            $paidToday = round((float) ($paymentAgg->paid_today ?? 0), 2);
            $grossToday = round((float) ($paymentAgg->gross_today ?? 0), 2);
            $changeToday = round((float) ($paymentAgg->change_today ?? 0), 2);
            $paymentCount = (int) ($paymentAgg->payment_count ?? 0);

            if ($this->type === 'expected-paid' && $paidToday <= 0) {
                continue;
            }

            $missingToday = round(max($installment - $paidToday, 0), 2);

            $paymentStatus = __('pages/reports.payment_calendar.details.status_unpaid');

            if ($paidToday >= $installment && $installment > 0) {
                $paymentStatus = __('pages/reports.payment_calendar.details.status_paid');
            } elseif ($paidToday > 0 && $paidToday < $installment) {
                $paymentStatus = __('pages/reports.payment_calendar.details.status_partial');
            }

            $dueDate = $targetDate->copy();

            $rows->push([
                $c->source_id,
                $c->logicalref,
                $c->name,
                $c->contract,
                $c->phone,
                $c->passport,
                $c->branch,
                $c->clientref,
                $c->assurance,
                $c->manager,
                $c->status,
                $creditDate->format('Y-m-d'),
                $dueDate->format('Y-m-d'),
                $installment,
                $paidToday,
                $grossToday,
                $changeToday,
                $missingToday,
                $paymentCount,
                $paymentStatus,
                ! empty($paymentAgg?->last_payment_at)
                    ? Carbon::parse($paymentAgg->last_payment_at)->format('Y-m-d H:i:s')
                    : null,
                round($amount, 2),
                round($paid, 2),
                $remaining,
                $c->amount !== null ? round((float) $c->amount, 2) : null,
                $c->paid !== null ? round((float) $c->paid, 2) : null,
                $c->amount_local !== null ? round((float) $c->amount_local, 2) : null,
                $c->paid_local !== null ? round((float) $c->paid_local, 2) : null,
                $c->willpaiddate ? Carbon::parse($c->willpaiddate)->format('Y-m-d') : null,
                $c->willpaidamount,
                $c->note,
            ]);
        }

        $rows = $rows
            ->sortBy([
                [6, 'asc'],
                [2, 'asc'],
            ])
            ->values();

        $rows->push([
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
            '',
            round((float) $rows->sum(fn ($r) => (float) ($r[13] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[14] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[15] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[16] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[17] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[18] ?? 0)), 2),
            '',
            '',
            round((float) $rows->sum(fn ($r) => (float) ($r[21] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[22] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[23] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[24] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[25] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[26] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[27] ?? 0)), 2),
            '',
            '',
            '',
        ]);

        return $rows;
    }

    private function receivedRows(): Collection
    {
        $start = Carbon::parse($this->date)->startOfDay();
        $end = Carbon::parse($this->date)->endOfDay();

        $rows = CreditPayment::query()
            ->notVoided()
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('branch')
            ->orderBy('customer_name')
            ->get([
                'id',
                'credit_source_id',
                'credit_logicalref',
                'customer_name',
                'customer_contract',
                'customer_phone',
                'customer_passport',
                'branch',
                'created_by_name',
                'created_by_email',
                'created_by_phone',
                'created_at',
                'method',
                'pay_amount',
                'change_amount',
                'cash_amount',
                'card_amount',
                'phone_amount',
                'receiver_phone_number',
                'corrected_at',
                'note',
            ])
            ->map(function ($p) {
                $received = round((float) $p->pay_amount, 2);
                $change = round((float) ($p->change_amount ?? 0), 2);
                $net = round($received - $change, 2);

                return [
                    $p->id,
                    $p->credit_source_id,
                    $p->credit_logicalref,
                    $p->customer_name,
                    $p->customer_contract,
                    $p->customer_phone,
                    $p->customer_passport,
                    $p->branch,
                    $p->created_by_name,
                    $p->created_by_email,
                    $p->created_by_phone,
                    $p->created_at ? Carbon::parse($p->created_at)->format('Y-m-d H:i:s') : null,
                    $p->method,
                    $received,
                    $change,
                    $net,
                    round((float) ($p->cash_amount ?? 0), 2),
                    round((float) ($p->card_amount ?? 0), 2),
                    round((float) ($p->phone_amount ?? 0), 2),
                    $p->receiver_phone_number,
                    $p->corrected_at ? __('pages/reports.export.yes') : __('pages/reports.export.no'),
                    $p->corrected_at ? Carbon::parse($p->corrected_at)->format('Y-m-d H:i:s') : null,
                    $p->note,
                ];
            })
            ->values();

        $rows->push([
            '',
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
            round((float) $rows->sum(fn ($r) => (float) ($r[13] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[14] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[15] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[16] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[17] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[18] ?? 0)), 2),
            '',
            '',
            '',
            '',
        ]);

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();

        $sheet->getStyle('1:1')->getFont()->setBold(true);
        $sheet->getStyle($highestRow . ':' . $highestRow)->getFont()->setBold(true);

        return [];
    }
}