<?php

namespace App\Exports;

use App\Models\Credit;
use App\Models\CreditPayment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
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
                'Payment ID',
                'Credit Source ID',
                'Credit LogicalRef',
                'Customer',
                'Contract',
                'Phone',
                'Passport',
                'Branch',
                'Cashier',
                'Cashier Email',
                'Cashier Phone',
                'Payment Date',
                'Method',
                'Received Amount',
                'Change Amount',
                'Net Applied',
                'Cash Amount',
                'Card Amount',
                'Phone Amount',
                'Receiver Phone',
                'Corrected',
                'Corrected At',
                'Note',
            ];
        }

        return [
            'Credit ID',
            'LogicalRef',
            'Customer',
            'Contract',
            'Phone',
            'Passport',
            'Branch',
            'ClientRef',
            'Assurance',
            'Manager',
            'Status',
            'Credit Date',
            'Due Date',
            'Expected Installment',
            'Total Debt',
            'Paid',
            'Remaining',
            'Remote Amount',
            'Remote Paid',
            'Local Amount',
            'Local Paid',
            'Will Pay Date',
            'Will Pay Amount',
            'Note',
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

        $rows = collect();

        foreach ($credits as $c) {
            $amount = (float) ($c->amount_local ?? $c->amount ?? 0);
            $paid = (float) ($c->paid_local ?? $c->paid ?? 0);

            if ($amount <= 0) {
                continue;
            }

            $monthlyPayment = round($amount / 6, 2);
            $remaining = round(max($amount - $paid, 0), 2);
            $creditDate = Carbon::parse($c->date_)->startOfDay();

            for ($i = 1; $i <= 6; $i++) {
                $dueDate = $creditDate->copy()->addMonthsNoOverflow($i)->startOfDay();

                if (! $dueDate->isSameDay($targetDate)) {
                    continue;
                }

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
                    $monthlyPayment,
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
            'TOTAL',
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
            round((float) $rows->sum(fn ($r) => (float) $r[13]), 2),
            round((float) $rows->sum(fn ($r) => (float) $r[14]), 2),
            round((float) $rows->sum(fn ($r) => (float) $r[15]), 2),
            round((float) $rows->sum(fn ($r) => (float) $r[16]), 2),
            '',
            '',
            '',
            '',
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
                    $p->corrected_at ? 'YES' : 'NO',
                    $p->corrected_at ? Carbon::parse($p->corrected_at)->format('Y-m-d H:i:s') : null,
                    $p->note,
                ];
            })
            ->values();

        $rows->push([
            '',
            '',
            '',
            'TOTAL',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            round((float) $rows->sum(fn ($r) => (float) $r[13]), 2),
            round((float) $rows->sum(fn ($r) => (float) $r[14]), 2),
            round((float) $rows->sum(fn ($r) => (float) $r[15]), 2),
            round((float) $rows->sum(fn ($r) => (float) $r[16]), 2),
            round((float) $rows->sum(fn ($r) => (float) $r[17]), 2),
            round((float) $rows->sum(fn ($r) => (float) $r[18]), 2),
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