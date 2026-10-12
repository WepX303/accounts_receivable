<?php

namespace App\Http\Resources;

use App\Services\Payments\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\CreditPayment
 */
class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'credit_id' => $this->credit_source_id,
            'customer_name' => $this->customer_name,
            'contract' => $this->customer_contract,
            'branch' => $this->branch,

            'method' => $this->method,
            'amount' => $this->pay_amount,
            'change' => $this->change_amount,
            'applied' => Money::format($this->applied_amount),
            'cash_amount' => $this->cash_amount,
            'card_amount' => $this->card_amount,
            'phone_amount' => $this->phone_amount,
            'receiver_phone_number' => $this->receiver_phone_number,

            'remaining_before' => $this->old_amount_local,
            'remaining_after' => $this->new_amount_local,

            'note' => $this->note,
            'paid_at' => $this->created_at?->toIso8601String(),
            // The cashier's current name when it was loaded, as the panel shows; otherwise the name kept at the time.
            'cashier' => ($this->relationLoaded('createdByUser') ? $this->createdByUser?->full_name : null) ?? $this->created_by_name,

            'is_voided' => $this->is_voided,
            'voided_at' => $this->voided_at?->toIso8601String(),
            'voided_by' => $this->whenLoaded('voidedByUser', fn () => $this->voidedByUser?->full_name),
            'void_reason' => $this->void_reason,

            'corrected_at' => $this->corrected_at?->toIso8601String(),
            'corrected_by' => $this->whenLoaded('correctedByUser', fn () => $this->correctedByUser?->full_name),
            'correct_reason' => $this->correct_reason,
            'corrected_from_payment_id' => $this->corrected_from_payment_id,
        ];
    }
}
