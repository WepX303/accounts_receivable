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

class CustomerStatementExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(private Request $request) {}

    public function headings(): array
    {
        return [
            __('pages/reports.common.date'),
            __('pages/reports.customer_statement.type'),
            __('pages/reports.customer_statement.description'),
            __('pages/reports.customer_statement.debit'),
            __('pages/reports.customer_statement.credit'),
            __('pages/reports.common.change'),
            __('pages/reports.common.net'),
            __('pages/reports.customer_statement.balance'),
            __('pages/reports.common.method'),
            __('pages/reports.common.cashier'),
            __('pages/reports.common.note'),

            __('pages/reports.export.payment_id'),
            __('pages/reports.common.customer'),
            __('pages/reports.common.contract'),
            __('pages/reports.common.phone'),
            __('pages/reports.common.passport'),
            __('pages/reports.common.branch'),
            __('pages/reports.common.credit_id'),
            __('pages/reports.export.logicalref'),
            __('pages/reports.export.clientref'),
            __('pages/reports.common.credit_date'),
            __('pages/reports.common.total_debt'),
            __('pages/reports.common.paid'),
            __('pages/reports.common.remaining'),
            __('pages/reports.export.credit_status'),
        ];
    }

    public function collection(): Collection
    {
        $contract = trim((string) $this->request->get('contract', ''));
        $phone = trim((string) $this->request->get('phone', ''));
        $customer = trim((string) $this->request->get('customer', ''));
        $branch = trim((string) $this->request->get('branch', ''));

        $credit = Credit::query()
            ->when($contract !== '', fn ($q) => $q->where('contract', $contract))
            ->when($phone !== '', fn ($q) => $q->where('phone', 'ilike', '%' . $phone . '%'))
            ->when($customer !== '', fn ($q) => $q->where('name', 'ilike', '%' . $customer . '%'))
            ->when($branch !== '', fn ($q) => $q->where('branch', $branch))
            ->orderByDesc('source_id')
            ->first();

        if (! $credit) {
            return collect();
        }

        $amount = (float) ($credit->amount_local ?? $credit->amount ?? 0);
        $paid = (float) ($credit->paid_local ?? $credit->paid ?? 0);
        $remaining = round(max($amount - $paid, 0), 2);

        $rows = collect();
        $balance = round($amount, 2);

        $rows->push([
            $credit->date_ ? Carbon::parse($credit->date_)->format('Y-m-d H:i') : null,
            __('pages/reports.customer_statement.type_credit_created'),
            __('pages/reports.customer_statement.description_credit_created'),
            round($amount, 2),
            0,
            0,
            0,
            $balance,
            '-',
            '-',
            $credit->note,

            '',
            $credit->name,
            $credit->contract,
            $credit->phone,
            $credit->passport,
            $credit->branch,
            $credit->source_id,
            $credit->logicalref,
            $credit->clientref,
            $credit->date_ ? Carbon::parse($credit->date_)->format('Y-m-d') : null,
            round($amount, 2),
            round($paid, 2),
            $remaining,
            $credit->status,
        ]);

        $payments = CreditPayment::query()
            ->where('credit_source_id', (int) $credit->source_id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        foreach ($payments as $payment) {
            $gross = (float) ($payment->pay_amount ?? 0);
            $change = (float) ($payment->change_amount ?? 0);
            $net = round($gross - $change, 2);

            $isVoided = ! empty($payment->voided_at);
            $isCorrected = ! empty($payment->corrected_at);

            if (! $isVoided) {
                $balance = round(max($balance - $net, 0), 2);
            }

            $type = $isVoided
                ? __('pages/reports.customer_statement.type_voided_payment')
                : ($isCorrected
                    ? __('pages/reports.customer_statement.type_corrected_payment')
                    : __('pages/reports.customer_statement.type_payment'));

            $rows->push([
                $payment->created_at ? Carbon::parse($payment->created_at)->format('Y-m-d H:i') : null,
                $type,
                __('pages/reports.customer_statement.description_payment', ['id' => $payment->id]),
                0,
                $isVoided ? 0 : $net,
                round($change, 2),
                $isVoided ? 0 : $net,
                $balance,
                strtoupper((string) ($payment->method ?? '-')),
                $payment->created_by_name ?? '-',
                $payment->note,

                $payment->id,
                $credit->name,
                $credit->contract,
                $credit->phone,
                $credit->passport,
                $credit->branch,
                $credit->source_id,
                $credit->logicalref,
                $credit->clientref,
                $credit->date_ ? Carbon::parse($credit->date_)->format('Y-m-d') : null,
                round($amount, 2),
                round($paid, 2),
                $remaining,
                $credit->status,
            ]);
        }

        $rows->push([
            '',
            __('pages/reports.export.total_upper'),
            '',
            round($amount, 2),
            round($paid, 2),
            '',
            '',
            $remaining,
            '',
            '',
            '',
            '',
            $credit->name,
            $credit->contract,
            $credit->phone,
            $credit->passport,
            $credit->branch,
            $credit->source_id,
            $credit->logicalref,
            $credit->clientref,
            '',
            round($amount, 2),
            round($paid, 2),
            $remaining,
            $credit->status,
        ]);

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $highestRow = $sheet->getHighestRow();

        $sheet->getStyle('1:1')->getFont()->setBold(true);
        $sheet->getStyle($highestRow . ':' . $highestRow)->getFont()->setBold(true);

        return [];
    }
}