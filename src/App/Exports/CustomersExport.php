<?php

namespace App\Exports;

use App\Services\Customers\CustomerListService;
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

    /**
     * The list's own query: the spreadsheet holds exactly the customers the
     * screen shows for the same search, quick filter and dates.
     */
    public function query()
    {
        return app(CustomerListService::class)->query(
            $this->request->only(['q', 'quick_filter', 'date_from', 'date_to'])
        );
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
