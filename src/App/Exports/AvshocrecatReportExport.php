<?php

namespace App\Exports;

use App\Models\AvshocrecatReport;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AvshocrecatReportExport implements FromQuery, WithHeadings, WithChunkReading
{
    protected string $q;

    public function __construct(string $q = '')
    {
        $this->q = trim($q);
    }

    public function query()
    {
        $query = AvshocrecatReport::query()
            ->select([
                'magazyn',
                'karz_alyjy',
                'telefon_belgisi',
                'pasport_belgisi',
                'sertnama_nomeri',
                'tiger_kody',
                'kt_cykdajy',
                'dt_girdeji',
                'm1',
                'm2',
                'm3',
                'm4',
                'm5',
                'm6',
                'galyndy',
                'aylyk_tolegi',
                'karz_alan_senesi',
                'gutaryan_senesi',
                'kategoriyasy',
                'maglumat',
                'bellik',
                'tolejek_senesi',
                'statusy',
            ])
            ->orderByDesc('id');

        if ($this->q !== '') {
            $q = $this->q;

            $query->where(function ($sub) use ($q) {
                $sub->where('karz_alyjy', 'ILIKE', "%{$q}%")
                    ->orWhere('pasport_belgisi', 'ILIKE', "%{$q}%")
                    ->orWhere('telefon_belgisi', 'ILIKE', "%{$q}%")
                    ->orWhere('tiger_kody', 'ILIKE', "%{$q}%")
                    ->orWhere('magazyn', 'ILIKE', "%{$q}%")
                    ->orWhere('maglumat', 'ILIKE', "%{$q}%")
                    ->orWhere('sertnama_nomeri', 'ILIKE', "%{$q}%");
            });
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'Magazyn',
            'Karz alyjy',
            'Telefon belgisi',
            'Pasport belgisi',
            'Sertnama nomeri',
            'Tiger kody',
            'KT',
            'DT',
            '1 Ay',
            '2 Ay',
            '3 Ay',
            '4 Ay',
            '5 Ay',
            '6 Ay',
            'Galyndy',
            'Aylyk tolegi',
            'Karz alan senesi',
            'Gutaryan senesi',
            'Kategoriyasy',
            'Maglumat',
            'Bellik',
            'Tolejek senesi',
            'Statusy',
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}