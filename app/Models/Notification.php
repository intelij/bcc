<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * In-app notification record. Not Laravel's DatabaseNotification —
 * this is the platform's own notification centre record.
 */
class Notification extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'event', 'title', 'body', 'data', 'read_at'];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class);
    }

    public function markRead(): void
    {
        $this->forceFill(['read_at' => now()])->save();
    }
}
