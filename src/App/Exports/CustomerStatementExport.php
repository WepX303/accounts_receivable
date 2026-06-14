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
            'Date',
            'Type',
            'Description',
            'Debit',
            'Credit',
            'Change',
            'Net',
            'Balance',
            'Method',
            'Cashier',
            'Note',

            'Payment ID',
            'Customer',
            'Contract',
            'Phone',
            'Passport',
            'Branch',
            'Credit ID',
            'LogicalRef',
            'ClientRef',
            'Credit Date',
            'Total Debt',
            'Paid',
            'Remaining',
            'Credit Status',
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
            'Credit Created',
            'Credit amount created',
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
                ? 'Voided Payment'
                : ($isCorrected ? 'Corrected Payment' : 'Payment');

            $rows->push([
                $payment->created_at ? Carbon::parse($payment->created_at)->format('Y-m-d H:i') : null,
                $type,
                'Payment #' . $payment->id,
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
            'TOTAL',
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