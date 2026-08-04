<?php

namespace App\Models;

use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Model;

class Credit extends Model
{
    protected static function booted()
    {
        static::addGlobalScope(new BranchScope);
    }

    protected $table = 'credits';

    protected $primaryKey = 'source_id';

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
        'is_blocked' => 'integer',

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

    // public function payments()
    // {
    //     return $this->hasMany(CreditPayment::class, 'credit_logicalref', 'logicalref')
    //         ->orderByDesc('id');
    // }
    public function payments()
    {
        return $this->hasMany(CreditPayment::class, 'credit_source_id', 'source_id')

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


    //new model
    public function getSmsMonthlyPaymentAttribute(): float
    {
        $amount = (float) ($this->amount_local ?? $this->amount ?? 0);

        if ($amount <= 0) {
            return 0;
        }

        return round($amount / 6, 2);
    }

    public function getSmsDueInstallmentCountAttribute(): int
    {
        if (! $this->date_) {
            return 0;
        }

        $start = $this->date_->copy()->startOfDay();
        $today = now()->startOfDay();

        $count = 0;

        for ($i = 1; $i <= 6; $i++) {
            $dueDate = $start->copy()->addMonthsNoOverflow($i)->startOfDay();

            if ($today->gte($dueDate)) {
                $count = $i;
            }
        }

        return $count;
    }

    public function getSmsExpectedPaidAttribute(): float
    {
        $amount = (float) ($this->amount_local ?? $this->amount ?? 0);

        if ($amount <= 0) {
            return 0;
        }

        $expected = $this->sms_monthly_payment * $this->sms_due_installment_count;

        return round(min($expected, $amount), 2);
    }

    public function getSmsActualPaidAttribute(): float
    {
        return round((float) ($this->paid_local ?? $this->paid ?? 0), 2);
    }

    public function getSmsOverdueAmountAttribute(): float
    {
        $overdue = $this->sms_expected_paid - $this->sms_actual_paid;

        return round(max($overdue, 0), 2);
    }

    public function getSmsIsOverdueAttribute(): bool
    {
        return $this->sms_overdue_amount > 0.01;
    }
}
