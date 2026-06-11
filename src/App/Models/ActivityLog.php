<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'user_name',
        'user_email',
        'user_phone',
        'user_role',
        'action',
        'category',
        'severity',
        'is_suspicious',
        'subject_type',
        'subject_id',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'platform',
        'http_method',
        'url',
        'route_name',
        'is_success',
        'message',
        'old_values',
        'new_values',
        'extra',
        'created_at',
    ];

    protected $casts = [
        'is_suspicious' => 'boolean',
        'is_success' => 'boolean',
        'old_values' => 'array',
        'new_values' => 'array',
        'extra' => 'array',
        'created_at' => 'datetime',
    ];
}