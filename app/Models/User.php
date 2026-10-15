<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_LOCKED = 'locked';

    public const ROLE_RESIDENT = 'resident';

    public const ROLE_OWNER = 'property_owner';

    public const ROLE_TENANT = 'tenant';

    public const ROLE_CUSTOMER_SERVICE = 'customer_service';

    public const ROLE_FINANCE = 'finance';

    public const ROLE_CONTENT_ADMIN = 'content_admin';

    public const ROLE_ADMIN = 'admin';

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'national_id', 'status',
        'failed_login_attempts', 'locked_until', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $attributes = [
        'status' => 'active',
        'failed_login_attempts' => 0,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'locked_until' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function propertyUsers(): HasMany
    {
        return $this->hasMany(PropertyUser::class);
    }

    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 'property_users')
            ->withPivot(['relationship', 'status', 'can_view_bills', 'can_view_account', 'can_pay', 'can_submit_requests', 'can_view_statements'])
            ->withTimestamps();
    }

    public function approvedPropertyLinks(): HasMany
    {
        return $this->propertyUsers()->where('property_users.status', 'approved');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class)->latest();
    }

    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class);
    }

    // ---- roles / permissions ----

    public function hasRole(string ...$slugs): bool
    {
        return $this->roles->whereIn('slug', $slugs)->isNotEmpty();
    }

    public function isStaff(): bool
    {
        return $this->hasRole(
            self::ROLE_ADMIN, self::ROLE_FINANCE, self::ROLE_CUSTOMER_SERVICE, self::ROLE_CONTENT_ADMIN
        );
    }

    public function hasPermission(string $permission): bool
    {
        return $this->roles->loadMissing('permissions')
            ->flatMap->permissions
            ->pluck('slug')
            ->contains($permission);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isLocked(): bool
    {
        return $this->status === self::STATUS_LOCKED
            || ($this->locked_until !== null && $this->locked_until->isFuture());
    }

    /**
     * Verified properties this user may access, optionally filtered by a pivot capability.
     */
    public function accessibleProperties(?string $capability = null)
    {
        $query = $this->properties()
            ->where('property_users.status', 'approved')
            ->where('properties.status', Property::STATUS_VERIFIED);

        if ($capability) {
            $query->where("property_users.$capability", true);
        }

        return $query;
    }

    public function canAccessProperty(Property $property, ?string $capability = null): bool
    {
        $link = $this->propertyUsers()
            ->where('property_id', $property->id)
            ->where('status', 'approved')
            ->first();

        if (! $link || ! $property->isVerified()) {
            return false;
        }

        return $capability === null || (bool) $link->{$capability};
    }
}
