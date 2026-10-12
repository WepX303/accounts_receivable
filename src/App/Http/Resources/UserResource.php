<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\User
 */
class UserResource extends JsonResource
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
            // null means every branch
            'branches' => $this->allowedBranches(),
            'status' => (bool) $this->status,
        ];
    }
}
