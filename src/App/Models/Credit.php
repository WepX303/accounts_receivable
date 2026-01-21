<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Credit extends Model
{
    protected $table = 'credits_test';
    protected $primaryKey = 'logicalref';

    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'paid_local',
        'paid_note',
        'paid_updated_by',
        'paid_updated_at',

        'amount_local',
        'amount_note',
        'amount_updated_by',
        'amount_updated_at',
    ];

    protected $casts = [
        'paid_local'   => 'decimal:2',
        'amount_local' => 'decimal:2',

        'paid_updated_at'   => 'datetime',
        'amount_updated_at' => 'datetime',

        'date_' => 'datetime',
        'willpaiddate' => 'datetime',
        'lastnoteddate' => 'datetime',
        'active' => 'boolean',
    ];
}
