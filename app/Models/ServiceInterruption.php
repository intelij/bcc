<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceInterruption extends Model
{
    use HasFactory;

    public const STATUS_PLANNED = 'planned';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_RESTORING = 'restoring';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUSES = [self::STATUS_PLANNED, self::STATUS_ACTIVE, self::STATUS_RESTORING, self::STATUS_RESOLVED];

    protected $fillable = [
        'title', 'type', 'service', 'area', 'suburb', 'ward',
        'starts_at', 'expected_restoration_at', 'resolved_at',
        'reason', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'expected_restoration_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeCurrent($query)
    {
        return $query->whereIn('status', [self::STATUS_PLANNED, self::STATUS_ACTIVE, self::STATUS_RESTORING])
            ->orderBy('starts_at');
    }
}
