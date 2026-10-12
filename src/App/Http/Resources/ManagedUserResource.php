<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A user as the user-management screen shows and edits them.
 *
 * @mixin \App\Models\User
 */
class ManagedUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phonenumber' => $this->phonenumber,
            'position' => $this->position,
            'role' => $this->role->value,
            'role_label' => $this->role->label(),
            // As stored: an empty list means every branch.
            'branches' => $this->branches ?? [],
            'daily_report' => (bool) $this->daily_report,
            'status' => (bool) $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
