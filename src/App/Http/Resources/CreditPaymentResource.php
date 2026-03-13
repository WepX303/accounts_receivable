<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'credit_logicalref' => (int) $this->credit_logicalref,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'customer_passport' => $this->customer_passport,
            'customer_contract' => $this->customer_contract,
            'branch' => $this->branch,
            'created_by' => $this->created_by,
            'created_by_name' => $this->created_by_name,
            'created_by_email' => $this->created_by_email,
            'created_by_phone' => $this->created_by_phone,
            'pay_amount' => $this->pay_amount,
            'change_amount' => $this->change_amount,
            'applied_amount' => $this->applied_amount,
            'method' => $this->method,
            'cash_amount' => $this->cash_amount,
            'card_amount' => $this->card_amount,
            'phone_amount' => $this->phone_amount,
            'old_amount_local' => $this->old_amount_local,
            'new_amount_local' => $this->new_amount_local,
            'old_paid_local' => $this->old_paid_local,
            'new_paid_local' => $this->new_paid_local,
            'note' => $this->note,
            'created_at' => optional($this->created_at)?->format('Y-m-d H:i:s'),
            'is_voided' => $this->is_voided,
            'voided_at' => optional($this->voided_at)?->format('Y-m-d H:i:s'),
            'void_reason' => $this->void_reason,
            'corrected_at' => optional($this->corrected_at)?->format('Y-m-d H:i:s'),
            'correct_reason' => $this->correct_reason,
            'corrected_from_payment_id' => $this->corrected_from_payment_id,
            'corrected_by_user' => $this->whenLoaded('correctedByUser', function () {
                return [
                    'id' => $this->correctedByUser?->id,
                    'full_name' => $this->correctedByUser?->full_name,
                ];
            }),
            'voided_by_user' => $this->whenLoaded('voidedByUser', function () {
                return [
                    'id' => $this->voidedByUser?->id,
                    'full_name' => $this->voidedByUser?->full_name,
                ];
            }),
        ];
    }
}