<?php

namespace App\Exports;

use App\Models\CreditPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CollectionPerformanceDetailsExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(private Request $request) {}

    public function headings(): array
    {
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

            'Old Remaining',
            'New Remaining',
            'Old Paid',
            'New Paid',

            'Corrected By',
            'Corrected At',
            'Correct Reason',
            'Corrected From Payment ID',

            'Note',
        ];
    }

    public function collection(): Collection
    {
        $type = (string) $this->request->get('type');
        $value = trim((string) $this->request->get('value'));

        $dateFrom = $this->request->filled('date_from')
            ? Carbon::parse($this->request->date_from)->startOfDay()
            : now()->startOfDay();

        $dateTo = $this->request->filled('date_to')
            ? Carbon::parse($this->request->date_to)->endOfDay()
            : now()->endOfDay();

        $method = (string) $this->request->get('method', 'all');

        $query = CreditPayment::query()
            ->with(['correctedByUser:id,firstname,lastname'])
            ->notVoided()
            ->whereBetween('created_at', [$dateFrom, $dateTo]);

        if ($type === 'cashier') {
            $query->where('created_by_name', $value);
        }

        if ($type === 'branch') {
            $query->where('branch', $value);
        }

        $query
            ->when($method === 'cash', fn ($q) => $q->where('cash_amount', '>', 0))
            ->when($method === 'card', fn ($q) => $q->where('card_amount', '>', 0))
            ->when($method === 'phone', fn ($q) => $q->where('phone_amount', '>', 0));

        $rows = $query
            ->orderByDesc('created_at')
            ->get();

        $exportRows = $rows->map(function ($p) {
            $received = (float) $p->pay_amount;
            $change = (float) ($p->change_amount ?? 0);
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

                $p->created_at ? Carbon::parse($p->created_at)->format('Y-m-d H:i') : null,
                $p->method,

                round($received, 2),
                round($change, 2),
                $net,

                round((float) ($p->cash_amount ?? 0), 2),
                round((float) ($p->card_amount ?? 0), 2),
                round((float) ($p->phone_amount ?? 0), 2),

                $p->receiver_phone_number,

                $p->old_amount_local,
                $p->new_amount_local,
                $p->old_paid_local,
                $p->new_paid_local,

                $p->correctedByUser?->full_name,
                $p->corrected_at ? Carbon::parse($p->corrected_at)->format('Y-m-d H:i') : null,
                $p->correct_reason,
                $p->corrected_from_payment_id,

                $p->note,
            ];
        });

        $exportRows->push([
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
            round((float) $exportRows->sum(fn ($r) => (float) ($r[13] ?? 0)), 2),
            round((float) $exportRows->sum(fn ($r) => (float) ($r[14] ?? 0)), 2),
            round((float) $exportRows->sum(fn ($r) => (float) ($r[15] ?? 0)), 2),
            round((float) $exportRows->sum(fn ($r) => (float) ($r[16] ?? 0)), 2),
            round((float) $exportRows->sum(fn ($r) => (float) ($r[17] ?? 0)), 2),
            round((float) $exportRows->sum(fn ($r) => (float) ($r[18] ?? 0)), 2),
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
        ]);

        return $exportRows;
    }

    public function styles(Worksheet $sheet): array
    {
        $highestRow = $sheet->getHighestRow();

        $sheet->getStyle('1:1')->getFont()->setBold(true);
        $sheet->getStyle($highestRow . ':' . $highestRow)->getFont()->setBold(true);

        return [];
    }
}