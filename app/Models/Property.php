<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUSES = [self::STATUS_PENDING, self::STATUS_VERIFIED, self::STATUS_REJECTED, self::STATUS_SUSPENDED];

    public const TYPES = ['residential', 'commercial', 'industrial', 'institutional'];

    protected $fillable = [
        'reference', 'address_line', 'suburb', 'ward', 'city', 'property_type',
        'status', 'verified_by', 'verified_at', 'rejection_reason',
    ];

    protected function casts(): array
    {
        return ['verified_at' => 'datetime'];
    }

    public function propertyUsers(): HasMany
    {
        return $this->hasMany(PropertyUser::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'property_users')
            ->withPivot(['relationship', 'status', 'can_view_bills', 'can_view_account', 'can_pay', 'can_submit_requests', 'can_view_statements'])
            ->withTimestamps();
    }

    public function account(): HasOne
    {
        return $this->hasOne(MunicipalAccount::class);
    }

    public function meters(): HasMany
    {
        return $this->hasMany(WaterMeter::class);
    }

    public function disconnectionNotices(): HasMany
    {
        return $this->hasMany(DisconnectionNotice::class);
    }

    public function isVerified(): bool
    {
        return $this->status === self::STATUS_VERIFIED;
    }

    public function fullAddress(): string
    {
        return "{$this->address_line}, {$this->suburb}, {$this->city}";
    }
}
