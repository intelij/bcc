<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_SUCCESSFUL = 'successful';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUS_UNALLOCATED = 'unallocated';

    public const STATUSES = [
        self::STATUS_PENDING, self::STATUS_PROCESSING, self::STATUS_SUCCESSFUL,
        self::STATUS_FAILED, self::STATUS_CANCELLED, self::STATUS_REFUNDED, self::STATUS_UNALLOCATED,
    ];

    /** States that can still transition. */
    public const OPEN_STATUSES = [self::STATUS_PENDING, self::STATUS_PROCESSING];

    protected $fillable = [
        'uuid', 'municipal_account_id', 'property_id', 'user_id',
        'amount_cents', 'currency', 'provider', 'payment_method', 'reference',
        'idempotency_key', 'provider_reference', 'status', 'reconciliation_status',
        'source', 'meta', 'failure_reason', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Payment $payment) {
            $payment->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(MunicipalAccount::class, 'municipal_account_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function allocatedCents(): int
    {
        return (int) $this->allocations()->sum('amount_cents');
    }

    public function unallocatedCents(): int
    {
        return max(0, $this->amount_cents - $this->allocatedCents());
    }

    public function isFinal(): bool
    {
        return ! in_array($this->status, self::OPEN_STATUSES);
    }
}
