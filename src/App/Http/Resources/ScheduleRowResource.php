<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row of the instalment plan, with every column the panel's table shows.
 *
 * @mixin \App\Models\AvshocrecatReport
 */
class ScheduleRowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store' => $this->magazyn,
            'borrower' => $this->karz_alyjy,
            'phone' => $this->telefon_belgisi,
            'passport' => $this->pasport_belgisi,
            'contract' => $this->sertnama_nomeri,
            'tiger_code' => $this->tiger_kody,
            'kt_expense' => $this->kt_cykdajy,
            'dt_income' => $this->dt_girdeji,
            'm1' => $this->m1,
            'm2' => $this->m2,
            'm3' => $this->m3,
            'm4' => $this->m4,
            'm5' => $this->m5,
            'm6' => $this->m6,
            'balance' => $this->galyndy,
            'monthly_payment' => $this->aylyk_tolegi,
            'loan_date' => $this->karz_alan_senesi,
            'end_date' => $this->gutaryan_senesi,
            'category' => $this->kategoriyasy,
            'info' => $this->maglumat,
            'note' => $this->bellik,
            'will_pay_date' => $this->tolejek_senesi,
            'status' => $this->statusy,
        ];
    }
}
