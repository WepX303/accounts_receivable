<?php

namespace App\Http\Resources;

use App\Services\Payments\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A row of the customer list, with every column the panel's table shows.
 *
 * @mixin \App\Models\Credit
 */
class CustomerRowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $money = fn ($value) => $value === null ? null : Money::format((float) $value);

        return [
            ...(new CreditResource($this->resource))->toArray($request),
            'logical_ref' => $this->logicalref,
            'is_active' => (bool) $this->active,
            'customer_status' => $this->custstatus,
            'note' => $this->note,
            // What the source system holds, shown next to the local figures.
            'source_amount' => $this->amount,
            'source_paid' => $this->paid,
            'source_remaining' => $money($this->remote_remaining),
            // Collected in the period the list is showing, change given back taken off.
            'period_paid' => Money::format((float) $this->period_pay_sum - (float) $this->period_change_sum),
        ];
    }
}
