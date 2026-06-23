<?php

namespace App\Exports;

use App\Models\AvshocrecatReport;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AvshocrecatReportExport implements FromQuery, WithHeadings, WithChunkReading
{
    public function __construct(
        private string $q = '',
        private ?string $dateFrom = null,
        private ?string $dateTo = null,
        private array $branches = []
    ) {
        $this->q = trim($this->q);
    }

    public function query()
    {
        $query = AvshocrecatReport::query()
            ->select([
                'id',
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
                'created_at',
                'updated_at',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($this->q !== '') {

            $like = "%{$this->q}%";

            $query->where(function ($sub) use ($like) {

                $sub->where('karz_alyjy', 'ILIKE', $like)
                    ->orWhere('pasport_belgisi', 'ILIKE', $like)
                    ->orWhere('telefon_belgisi', 'ILIKE', $like)
                    ->orWhere('tiger_kody', 'ILIKE', $like)
                    ->orWhere('magazyn', 'ILIKE', $like)
                    ->orWhere('maglumat', 'ILIKE', $like)
                    ->orWhere('sertnama_nomeri', 'ILIKE', $like)
                    ->orWhere('kategoriyasy', 'ILIKE', $like)
                    ->orWhere('bellik', 'ILIKE', $like)
                    ->orWhere('statusy', 'ILIKE', $like);
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Date Filter
        |--------------------------------------------------------------------------
        */

        if ($this->dateFrom) {
            $query->whereDate('tolejek_senesi', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('tolejek_senesi', '<=', $this->dateTo);
        }

        /*
        |--------------------------------------------------------------------------
        | Branch Filter
        |--------------------------------------------------------------------------
        */

        if (!empty($this->branches)) {
            $query->whereIn('magazyn', $this->branches);
        }

        return $query->orderByDesc('id');
    }

    public function headings(): array
    {
        return [
            'ID',
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
            'Created At',
            'Updated At',
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
