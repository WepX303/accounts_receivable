<?php

namespace App\Models;

use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * A şertnama made in the panel.
 *
 * Only a draft, or a blank printed with its number for filling in by hand,
 * can be changed. Once approved, what was signed stays as it was:
 * the row may still be cancelled and its prints counted, nothing else. A
 * contract is never deleted.
 */
class CustomerContract extends Model
{
    public const DRAFT = 'draft';

    // A blank form printed with its number only, to be filled in by hand.
    public const ISSUED = 'issued';

    public const APPROVED = 'approved';

    public const CANCELLED = 'cancelled';

    /**
     * What may still change on an approved contract.
     */
    private const AFTER_APPROVAL = [
        'status',
        'cancelled_by',
        'cancelled_by_name',
        'cancelled_at',
        'cancel_reason',
        'print_count',
        'last_printed_at',
        'updated_at',
    ];

    /**
     * What may still change on a cancelled one.
     */
    private const AFTER_CANCEL = [
        'print_count',
        'last_printed_at',
        'updated_at',
    ];

    protected $table = 'customer_contracts';

    protected $fillable = [
        'number',
        'status',
        'template_version',
        'branch',
        'credit_source_id',
        'contract_date',

        'customer_name',
        'passport_series',
        'passport_number',
        'address',
        'phone',

        'months',
        'monthly_amount',
        'installment_part',

        'goods',
        'history',

        'created_by',
        'created_by_name',
    ];

    protected $casts = [
        'credit_source_id' => 'integer',
        'contract_date' => 'date',
        'months' => 'integer',
        'monthly_amount' => 'decimal:2',
        'goods' => 'array',
        'history' => 'array',
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'last_printed_at' => 'datetime',
        'print_count' => 'integer',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new BranchScope);

        static::updating(function (CustomerContract $contract) {
            $was = $contract->getOriginal('status');

            $allowed = match ($was) {
                self::DRAFT, self::ISSUED => null,
                self::APPROVED => self::AFTER_APPROVAL,
                default => self::AFTER_CANCEL,
            };

            if ($allowed !== null && array_diff(array_keys($contract->getDirty()), $allowed) !== []) {
                throw new LogicException("Contract {$contract->number} is {$was} and cannot be changed.");
            }

            // An approved contract can only move on to cancelled; nothing goes back to a draft.
            if (! in_array($was, [self::DRAFT, self::ISSUED], true) && $contract->isDirty('status') && ! ($was === self::APPROVED && $contract->status === self::CANCELLED)) {
                throw new LogicException("Contract {$contract->number} cannot go from {$was} to {$contract->status}.");
            }
        });

        static::deleting(function (CustomerContract $contract) {
            throw new LogicException("Contract {$contract->number} cannot be deleted; cancel it instead.");
        });
    }

    public function isDraft(): bool
    {
        return $this->status === self::DRAFT;
    }

    public function isIssued(): bool
    {
        return $this->status === self::ISSUED;
    }

    /**
     * Whether its details may still be changed.
     */
    public function isOpen(): bool
    {
        return $this->isDraft() || $this->isIssued();
    }

    public function isApproved(): bool
    {
        return $this->status === self::APPROVED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::CANCELLED;
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
