<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'logicalref' => (int) $this->logicalref,
            'branch' => $this->branch,
            'name' => $this->name,
            'passport' => $this->passport,
            'phone' => $this->phone,
            'contract' => $this->contract,
            'date_' => optional($this->date_)?->format('Y-m-d H:i:s'),
            'amount' => $this->amount,
            'paid' => $this->paid,
            'amount_local' => $this->amount_local,
            'paid_local' => $this->paid_local,
            'local_remaining' => $this->local_remaining,
            'local_closed' => $this->local_closed,
            'remote_remaining' => $this->remote_remaining,
            'remote_closed' => $this->remote_closed,
            'willpaiddate' => optional($this->willpaiddate)?->format('Y-m-d H:i:s'),
            'willpaidamount' => $this->willpaidamount,
            'note' => $this->note,
            'lastnoteddate' => optional($this->lastnoteddate)?->format('Y-m-d H:i:s'),
            'status' => $this->status,
            'active' => (bool) $this->active,
            'clientref' => $this->clientref,
            'custstatus' => $this->custstatus,
            'assurance' => $this->assurance,
            'ctype' => $this->ctype,
            'cardno' => $this->cardno,
            'fishno' => $this->fishno,
            'manager' => $this->manager,
            'confirmedby' => $this->confirmedby,
            'gstatus' => $this->gstatus,
            'rv_bigint' => (int) $this->rv_bigint,
            'paid_updated_at' => optional($this->paid_updated_at)?->format('Y-m-d H:i:s'),
            'amount_updated_at' => optional($this->amount_updated_at)?->format('Y-m-d H:i:s'),
            'paid_updated_by_user' => $this->whenLoaded('paidUpdatedByUser', function () {
                return [
                    'id' => $this->paidUpdatedByUser?->id,
                    'full_name' => $this->paidUpdatedByUser?->full_name,
                ];
            }),
            'amount_updated_by_user' => $this->whenLoaded('amountUpdatedByUser', function () {
                return [
                    'id' => $this->amountUpdatedByUser?->id,
                    'full_name' => $this->amountUpdatedByUser?->full_name,
                ];
            }),
        ];
    }
}