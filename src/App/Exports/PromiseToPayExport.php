<?php

namespace App\Exports;

use App\Models\Credit;
use App\Models\CreditPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PromiseToPayExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(private Request $request) {}

    public function headings(): array
    {
        return [
            __('pages/reports.common.credit_id'),
            __('pages/reports.export.logicalref'),
            __('pages/reports.common.customer'),
            __('pages/reports.common.phone'),
            __('pages/reports.common.passport'),
            __('pages/reports.common.contract'),
            __('pages/reports.common.branch'),
            __('pages/reports.export.clientref'),
            __('pages/reports.export.customer_status'),
            __('pages/reports.export.assurance'),
            __('pages/reports.export.credit_type'),
            __('pages/reports.export.card_no'),
            __('pages/reports.export.fish_no'),
            __('pages/reports.export.manager'),
            __('pages/reports.export.confirmed_by'),
            __('pages/reports.export.g_status'),

            __('pages/reports.common.credit_date'),
            __('pages/reports.common.total_debt'),
            __('pages/reports.common.paid'),
            __('pages/reports.common.remaining'),
            __('pages/reports.export.remote_amount'),
            __('pages/reports.export.remote_paid'),
            __('pages/reports.export.local_amount'),
            __('pages/reports.export.local_paid'),

            __('pages/reports.promise_to_pay.promise_date'),
            __('pages/reports.promise_to_pay.promise_amount'),
            __('pages/reports.promise_to_pay.paid_after_promise'),
            __('pages/reports.promise_to_pay.promise_status'),
            __('pages/reports.promise_to_pay.promise_days'),

            __('pages/reports.export.credit_status'),
            __('pages/reports.export.active'),
            __('pages/reports.export.is_blocked'),

            __('pages/reports.export.will_pay_date_raw'),
            __('pages/reports.export.will_pay_amount_raw'),
            __('pages/reports.common.note'),
            __('pages/reports.export.last_noted_date'),

            __('pages/reports.export.paid_updated_at'),
            __('pages/reports.export.paid_updated_by'),
            __('pages/reports.export.paid_note'),

            __('pages/reports.export.created_at'),
            __('pages/reports.export.updated_at'),
        ];
    }

    public function collection(): Collection
    {
        $today = now()->startOfDay();

        $dateFrom = $this->request->filled('date_from')
            ? Carbon::parse($this->request->date_from)->startOfDay()
            : now()->startOfDay();

        $dateTo = $this->request->filled('date_to')
            ? Carbon::parse($this->request->date_to)->endOfDay()
            : now()->addDays(7)->endOfDay();

        $branch = trim((string) $this->request->get('branch', ''));
        $status = (string) $this->request->get('status', 'all');
        $q = trim((string) $this->request->get('q', ''));

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
                'passport',
                'phone',
                'contract',
                'date_',
                'amount',
                'amount_local',
                'paid',
                'paid_local',
                'willpaiddate',
                'willpaidamount',
                'note',
                'lastnoteddate',
                'status',
                'active',
                'initiator_i',
                'clientref',
                'custstatus',
                'assurance',
                'ctype',
                'cardno',
                'fishno',
                'manager',
                'confirmedby',
                'gstatus',
                'is_blocked',
                'paid_updated_at',
                'paid_updated_by',
                'paid_note',
                'created_at',
                'updated_at',
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

            $promiseDays = $promiseDate->isPast()
                ? $promiseDate->diffInDays($today)
                : 0;

            $rows->push([
                $credit->source_id,
                $credit->logicalref,
                $credit->name,
                $credit->phone,
                $credit->passport,
                $credit->contract,
                $credit->branch,
                $credit->clientref,
                $credit->custstatus,
                $credit->assurance,
                $credit->ctype,
                $credit->cardno,
                $credit->fishno,
                $credit->manager,
                $credit->confirmedby,
                $credit->gstatus,

                $credit->date_ ? Carbon::parse($credit->date_)->format('Y-m-d') : null,
                round($total, 2),
                round($paid, 2),
                $remaining,
                $credit->amount !== null ? round((float) $credit->amount, 2) : null,
                $credit->paid !== null ? round((float) $credit->paid, 2) : null,
                $credit->amount_local !== null ? round((float) $credit->amount_local, 2) : null,
                $credit->paid_local !== null ? round((float) $credit->paid_local, 2) : null,

                $promiseDate->format('Y-m-d'),
                round($promiseAmount, 2),
                $paidOnOrAfterPromise,
                match ($promiseStatus) {
                    'today' => __('pages/reports.promise_to_pay.status.today'),
                    'upcoming' => __('pages/reports.promise_to_pay.status.upcoming'),
                    'kept' => __('pages/reports.promise_to_pay.status.kept'),
                    'broken' => __('pages/reports.promise_to_pay.status.broken'),
                    default => ucfirst((string) $promiseStatus),
                },
                $promiseDays,

                $credit->status,
                $credit->active ? __('pages/reports.export.active') : __('pages/reports.export.inactive'),
                (int) $credit->is_blocked,

                $credit->willpaiddate ? Carbon::parse($credit->willpaiddate)->format('Y-m-d H:i:s') : null,
                $credit->willpaidamount,
                $credit->note,
                $credit->lastnoteddate ? Carbon::parse($credit->lastnoteddate)->format('Y-m-d H:i:s') : null,

                $credit->paid_updated_at ? Carbon::parse($credit->paid_updated_at)->format('Y-m-d H:i:s') : null,
                $credit->paid_updated_by,
                $credit->paid_note,

                $credit->created_at ? Carbon::parse($credit->created_at)->format('Y-m-d H:i:s') : null,
                $credit->updated_at ? Carbon::parse($credit->updated_at)->format('Y-m-d H:i:s') : null,
            ]);
        }

        $rows = $rows
            ->sortBy(function ($row) {
                $status = strtolower((string) ($row[27] ?? ''));

                $priority = match ($status) {
                    'broken' => 1,
                    'today' => 2,
                    'upcoming' => 3,
                    'kept' => 4,
                    default => 5,
                };

                return $priority . '_' . ($row[24] ?? '') . '_' . ($row[2] ?? '');
            })
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
            '',
            '',
            '',

            '',
            round((float) $rows->sum(fn ($r) => (float) ($r[17] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[18] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[19] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[20] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[21] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[22] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[23] ?? 0)), 2),

            '',
            round((float) $rows->sum(fn ($r) => (float) ($r[25] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[26] ?? 0)), 2),
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
            '',
            '',
            '',
            '',
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