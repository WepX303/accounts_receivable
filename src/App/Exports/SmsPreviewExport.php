<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SmsPreviewExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function __construct(
        private array $rows
    ) {}

    public function headings(): array
    {
        return ['SMS NUMBER', 'SMS TEXT'];
    }

    public function array(): array
    {
        return $this->rows;
    }
}