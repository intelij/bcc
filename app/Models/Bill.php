<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bill extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ISSUED = 'issued';

    public const STATUS_PARTIALLY_PAID = 'partially_paid';

    public const STATUS_PAID = 'paid';

    public const STATUS_OVERDUE = 'overdue';

    public const STATUS_DISPUTED = 'disputed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT, self::STATUS_ISSUED, self::STATUS_PARTIALLY_PAID,
        self::STATUS_PAID, self::STATUS_OVERDUE, self::STATUS_DISPUTED, self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'invoice_number', 'municipal_account_id', 'property_id',
        'period_start', 'period_end', 'issue_date', 'due_date',
        'previous_balance_cents', 'charges_cents', 'adjustments_cents',
        'payments_cents', 'arrears_cents', 'total_cents', 'amount_paid_cents', 'status',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'issue_date' => 'date',
            'due_date' => 'date',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(MunicipalAccount::class, 'municipal_account_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BillItem::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function outstandingCents(): int
    {
        return max(0, $this->total_cents - $this->amount_paid_cents);
    }

    public function isPayable(): bool
    {
        return in_array($this->status, [self::STATUS_ISSUED, self::STATUS_PARTIALLY_PAID, self::STATUS_OVERDUE])
            && $this->outstandingCents() > 0;
    }

    /** Recalculate bill status from its paid amount + due date. */
    public function syncStatus(): void
    {
        if (in_array($this->status, [self::STATUS_DRAFT, self::STATUS_CANCELLED, self::STATUS_DISPUTED])) {
            return;
        }

        $this->status = match (true) {
            $this->amount_paid_cents >= $this->total_cents && $this->total_cents > 0 => self::STATUS_PAID,
            $this->amount_paid_cents > 0 => self::STATUS_PARTIALLY_PAID,
            $this->due_date->isPast() => self::STATUS_OVERDUE,
            default => self::STATUS_ISSUED,
        };
        $this->save();
    }
}
