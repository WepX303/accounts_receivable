<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;


class CreditPayment extends Model
{
    protected $table = 'credit_payments';

    public $timestamps = false;

    protected $fillable = [
        'credit_logicalref',

        'customer_name',
        'customer_phone',
        'customer_passport',
        'customer_contract',
        'branch',

        'created_by_name',
        'created_by_email',
        'created_by_phone',

        // pay_amount: the money given by the customer (RECEIVED)
        'pay_amount',

        // change_amount: change
        'change_amount',

        'method',
        'cash_amount',
        'card_amount',
        'phone_amount',

        'old_amount_local',
        'new_amount_local',

        'old_paid_local',
        'new_paid_local',

        'note',
        'created_by',
        'created_at',

        'corrected_by',
        'corrected_at',
        'correct_reason',
    ];

    protected $casts = [
        'credit_logicalref' => 'integer',
        'created_by' => 'integer',
        'created_at' => 'datetime',

        'pay_amount' => 'decimal:2',
        'change_amount' => 'decimal:2',

        'cash_amount' => 'decimal:2',
        'card_amount' => 'decimal:2',
        'phone_amount' => 'decimal:2',

        'old_amount_local' => 'decimal:2',
        'new_amount_local' => 'decimal:2',

        'old_paid_local' => 'decimal:2',
        'new_paid_local' => 'decimal:2',

        'voided_by' => 'integer',
        'voided_at' => 'datetime',

        'corrected_by' => 'integer',
        'corrected_at' => 'datetime',
    ];

    public function credit()
    {
        return $this->belongsTo(Credit::class, 'credit_logicalref', 'logicalref');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForCashier($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    // computed: amount applied to the debt
    public function getAppliedAmountAttribute(): float
    {
        $received = (float) $this->pay_amount;
        $change = (float) ($this->change_amount ?? 0);
        $applied = $received - $change;
        if ($applied < 0) {
            $applied = 0;
        }

        return round($applied, 2);
    }

    public function scopeNotVoided($query)
    {
        return $query->whereNull('voided_at');
    }

    public function getIsVoidedAttribute(): bool
    {
        return $this->voided_at !== null;
    }

    public function voidedByUser()
    {
        return $this->belongsTo(User::class, 'voided_by');
    }
    public function correctedByUser()
    {
        return $this->belongsTo(User::class, 'corrected_by');
    }

    protected static function booted()
    {

        $bump = function () {
            if (!Cache::has('admin_dashboard:v')) {
                Cache::forever('admin_dashboard:v', 1);
            }

            Cache::increment('admin_dashboard:v');
        };

        static::created($bump);
        static::updated($bump);
        static::deleted($bump);
    }
}
