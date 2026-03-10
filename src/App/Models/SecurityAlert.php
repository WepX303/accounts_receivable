<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityAlert extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'alert_type',
        'risk_level',
        'is_resolved',
        'ip_address',
        'message',
        'meta',
        'created_at',
        'resolved_at',
    ];

    protected $casts = [
        'is_resolved' => 'boolean',
        'meta' => 'array',
        'created_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];
}