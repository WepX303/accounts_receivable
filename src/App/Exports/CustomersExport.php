<?php

namespace App\Exports;

use App\Models\Credit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Facades\App;

class CustomersExport implements FromQuery, WithHeadings, WithMapping, WithChunkReading
{


    public function __construct(private $request)
    {
        $lang = $this->request->get('lang');
        if (in_array($lang, ['tk', 'tr', 'ru', 'en'])) {
            App::setLocale($lang);
        }
    }

    public function query()
    {
        $q = trim((string) ($this->request->get('q') ?? ''));
        if ($q === 'null') $q = '';

        $quick = (string) $this->request->get('quick_filter', 'all');

        [$from, $to] = match ($quick) {
            'paid_today' => [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()],
            'paid_yesterday' => [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()],
            'paid_7d' => [Carbon::now()->subDays(7), Carbon::now()],
            'paid_14d' => [Carbon::now()->subDays(14), Carbon::now()],
            'paid_1m' => [Carbon::now()->subMonth(), Carbon::now()],
            'paid_3m' => [Carbon::now()->subMonths(3), Carbon::now()],
            'paid_6m' => [Carbon::now()->subMonths(6), Carbon::now()],
            'paid_9m' => [Carbon::now()->subMonths(9), Carbon::now()],
            'paid_12m' => [Carbon::now()->subMonths(12), Carbon::now()],
            default => [null, null],
        };

        $query = Credit::query()
            ->when($q !== '', function ($query) use ($q) {
                $like = '%' . $q . '%';
                $query->where(function ($qq) use ($like) {
                    $qq->where('name', 'ilike', $like)
                        ->orWhere('phone', 'ilike', $like)
                        ->orWhere('passport', 'ilike', $like)
                        ->orWhere('contract', 'ilike', $like)
                        ->orWhere('clientref', 'ilike', $like)
                        ->orWhere('assurance', 'ilike', $like);
                });
            })
            ->when($from && $to, function ($query) use ($from, $to) {
                $table = $query->getModel()->getTable();

                $query->whereExists(function ($sub) use ($from, $to, $table) {
                    $sub->selectRaw('1')
                        ->from('credit_payments')
                        ->whereColumn(
                            'credit_payments.credit_source_id',
                            $table . '.source_id'
                        )
                        ->whereBetween('credit_payments.created_at', [$from, $to]);
                });
            })
            ->when($quick === 'has_debt', fn($q) => $q->whereRaw('COALESCE(amount, 0) > COALESCE(paid, 0)'))
            ->when($quick === 'no_debt', fn($q) => $q->whereRaw('COALESCE(amount, 0) <= COALESCE(paid, 0)'))
            ->when($quick === 'no_payment', function ($query) {
                $table = $query->getModel()->getTable();
                $query->whereNotExists(function ($sub) use ($table) {
                    $sub->selectRaw('1')
                        ->from('credit_payments')
                        ->whereColumn(
                            'credit_payments.credit_source_id',
                            $table . '.source_id'
                        );
                });
            })
            ->when($quick === 'blocked', fn($q) => $q->where('active', false))
            ->when($quick === 'active', fn($q) => $q->where('active', true))
            ->when($quick === 'bermejek', function ($q) {
                $q->whereNotNull('status')
                    ->where('status', '!=', '')
                    ->whereRaw('LOWER(TRIM(status)) = ?', ['bermejek']);
            })
            ->when($quick === 'paid_mismatch', function ($query) {
                $query->whereNotNull('paid_local')
                    ->whereRaw('ROUND(COALESCE(paid_local,0)::numeric, 2) <> ROUND(COALESCE(paid,0)::numeric, 2)');
            })
            ->orderByDesc('rv_bigint');

        return $query;
    }

    public function chunkSize(): int
    {
        return 50; // istersen 1000 de yapabiliriz
    }


    public function headings(): array
    {
        $locale = App::getLocale(); // tk / tr / ru / en

        $t = [
            'tk' => [
                'logicalref' => 'ID',
                'date' => 'Senesi',
                'name' => 'Ady',
                'phone' => 'Telefon',
                'passport' => 'Pasport',
                'clientref' => 'ClientRef',
                'branch' => 'Şahamça',
                'contract' => 'Şertnama',
                'amount_local' => 'Möçber (Local)',
                'amount_remote' => 'Möçber (Merkez)',
                'paid_local' => 'Tölenen (Local)',
                'paid_remote' => 'Tölenen (Merkez)',
                'local_remaining' => 'Galan (Local)',
                'remote_remaining' => 'Galan (Merkez)',
                'status' => 'Status',
                'active' => 'Aktiw',
                'note' => 'Bellik',
            ],
            'tr' => [
                'logicalref' => 'ID',
                'date' => 'Tarih',
                'name' => 'Ad Soyad',
                'phone' => 'Telefon',
                'passport' => 'Pasaport',
                'clientref' => 'ClientRef',
                'branch' => 'Şube',
                'contract' => 'Sözleşme',
                'amount_local' => 'Tutar (Local)',
                'amount_remote' => 'Tutar (Merkez)',
                'paid_local' => 'Ödenen (Local)',
                'paid_remote' => 'Ödenen (Merkez)',
                'local_remaining' => 'Kalan (Local)',
                'remote_remaining' => 'Kalan (Merkez)',
                'status' => 'Durum',
                'active' => 'Aktif',
                'note' => 'Not',
            ],
            'ru' => [
                'logicalref' => 'ID',
                'date' => 'Дата',
                'name' => 'ФИО',
                'phone' => 'Телефон',
                'passport' => 'Паспорт',
                'clientref' => 'ClientRef',
                'branch' => 'Филиал',
                'contract' => 'Договор',
                'amount_local' => 'Сумма (Local)',
                'amount_remote' => 'Сумма (Центр)',
                'paid_local' => 'Оплачено (Local)',
                'paid_remote' => 'Оплачено (Центр)',
                'local_remaining' => 'Остаток (Local)',
                'remote_remaining' => 'Остаток (Центр)',
                'status' => 'Статус',
                'active' => 'Активный',
                'note' => 'Примечание',
            ],
            'en' => [
                'logicalref' => 'ID',
                'date' => 'Date',
                'name' => 'Name',
                'phone' => 'Phone',
                'passport' => 'Passport',
                'clientref' => 'ClientRef',
                'branch' => 'Branch',
                'contract' => 'Contract',
                'amount_local' => 'Amount (Local)',
                'amount_remote' => 'Amount (Center)',
                'paid_local' => 'Paid (Local)',
                'paid_remote' => 'Paid (Center)',
                'local_remaining' => 'Remaining (Local)',
                'remote_remaining' => 'Remaining (Center)',
                'status' => 'Status',
                'active' => 'Active',
                'note' => 'Note',
            ],
        ];

        // Dil yoksa tr’ye düş
        $L = $t[$locale] ?? $t['tk'];

        return [
            $L['logicalref'],
            $L['date'],
            $L['name'],
            $L['phone'],
            $L['passport'],
            $L['clientref'],
            $L['branch'],
            $L['contract'],
            $L['amount_local'],
            $L['amount_remote'],
            $L['paid_local'],
            $L['paid_remote'],
            $L['local_remaining'],
            $L['remote_remaining'],
            $L['status'],
            $L['active'],
            $L['note'],
        ];
    }
    public function map($c): array
    {
        return [
            $c->logicalref,
            $c->date_ ? Carbon::parse($c->date_)->format('Y-m-d') : null,
            $c->name,
            $c->phone,
            $c->passport,
            $c->clientref,
            $c->branch,
            $c->contract,
            round((float) ($c->amount_local ?? $c->amount ?? 0), 2),
            round((float) ($c->amount ?? 0), 2),
            round((float) ($c->paid_local ?? $c->paid ?? 0), 2),
            round((float) ($c->paid ?? 0), 2),
            $c->local_remaining,
            $c->remote_remaining,
            $c->status,
            $c->active ? 'YES' : 'NO',
            $c->note,
        ];
    }
}
