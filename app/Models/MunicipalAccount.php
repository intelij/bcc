<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MunicipalAccount extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'account_number', 'property_id', 'holder_name', 'currency',
        'balance_cents', 'arrears_cents', 'status', 'last_synced_at',
    ];

    protected $attributes = [
        'balance_cents' => 0,
        'arrears_cents' => 0,
        'currency' => 'USD',
        'status' => 'active',
    ];

    protected function casts(): array
    {
        return ['last_synced_at' => 'datetime'];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class)->latest('issue_date');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest();
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function disconnectionNotices(): HasMany
    {
        return $this->hasMany(DisconnectionNotice::class)->latest();
    }

    /**
     * Outstanding = issued/partial/overdue bills totals minus what was paid against them.
     */
    public function outstandingCents(): int
    {
        return (int) $this->bills()
            ->whereIn('status', [Bill::STATUS_ISSUED, Bill::STATUS_PARTIALLY_PAID, Bill::STATUS_OVERDUE])
            ->selectRaw('COALESCE(SUM(total_cents - amount_paid_cents), 0) as due')
            ->value('due');
    }
}
