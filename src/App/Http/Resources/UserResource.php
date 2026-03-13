<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'role' => is_object($this->role) ? $this->role->value : $this->role,
            'status' => (bool) $this->status,
        ];
    }
}