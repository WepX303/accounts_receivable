<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditListResource extends JsonResource
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
            'status' => $this->status,
            'active' => (bool) $this->active,
            'amount' => $this->amount,
            'paid' => $this->paid,
            'amount_local' => $this->amount_local,
            'paid_local' => $this->paid_local,
            'local_remaining' => $this->local_remaining,
            'local_closed' => $this->local_closed,
            'remote_remaining' => $this->remote_remaining,
            'remote_closed' => $this->remote_closed,
            'rv_bigint' => (int) $this->rv_bigint,
        ];
    }
}