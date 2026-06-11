<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'login_value',
        'status',
        'fail_reason',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'platform',
        'is_suspicious',
        'message',
        'created_at',
    ];

    protected $casts = [
        'is_suspicious' => 'boolean',
        'created_at' => 'datetime',
    ];
}