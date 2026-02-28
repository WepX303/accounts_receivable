<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Credit extends Model
{
    protected $table = 'credits';

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
        'amount' => 'decimal:2',
        'paid' => 'decimal:2',
        'amount_local' => 'decimal:2',
        'paid_local' => 'decimal:2',

        'paid_updated_at' => 'datetime',
        'amount_updated_at' => 'datetime',

        'date_' => 'datetime',
        'willpaiddate' => 'datetime',
        'lastnoteddate' => 'datetime',
        'active' => 'boolean',
    ];

    public function paidUpdatedByUser()
    {
        return $this->belongsTo(User::class, 'paid_updated_by');
    }

    public function amountUpdatedByUser()
    {
        return $this->belongsTo(User::class, 'amount_updated_by');
    }

    public function payments()
    {
        return $this->hasMany(CreditPayment::class, 'credit_logicalref', 'logicalref')
            ->orderByDesc('id');
    }

    // Local remaining accessor (LOCAL ONLY)
    public function getLocalRemainingAttribute(): ?float
    {
        if ($this->amount_local === null || $this->paid_local === null) {
            return null;
        }

        $total = (float) $this->amount_local;
        $paid = (float) $this->paid_local;

        $rem = $total - $paid;
        if ($rem < 0) {
            $rem = 0;
        }

        return round($rem, 2);
    }

    public function getLocalClosedAttribute(): bool
    {
        if ($this->amount_local === null || $this->paid_local === null) {
            return true;
        }

        $total = (float) $this->amount_local;
        $paid = (float) $this->paid_local;

        return $paid >= $total - 0.01;
    }

    // Remote balance (amount - paid) => may be negative
    public function getRemoteRemainingAttribute(): ?float
    {
        if ($this->amount === null || $this->paid === null) {
            return null;
        }

        return round(((float) $this->amount - (float) $this->paid), 2);
    }

    // Remote closed
    public function getRemoteClosedAttribute(): bool
    {
        if ($this->amount === null || $this->paid === null) {
            return true;
        }

        return (float) $this->paid >= (float) $this->amount - 0.01;
    }
}
