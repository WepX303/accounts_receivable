<?php

namespace App\Http\Resources;

use App\Services\Payments\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Amounts go out as strings with two decimals; the client must not do float maths on money.
 *
 * @mixin \App\Models\Credit
 */
class CreditResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->source_id,
            'name' => $this->name,
            'phone' => $this->phone,
            'passport' => $this->passport,
            'contract' => $this->contract,
            'client_ref' => $this->clientref,
            'branch' => $this->branch,
            'status' => $this->status,
            'date' => $this->date_?->toDateString(),
            'amount' => $this->amount_local,
            'paid' => $this->paid_local,
            'remaining' => $this->local_remaining === null ? null : Money::format($this->local_remaining),
            'is_closed' => $this->local_closed,
            'is_blocked' => (bool) $this->is_blocked,
        ];
    }
}
