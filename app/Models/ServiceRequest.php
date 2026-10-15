<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ServiceRequest extends Model
{
    use HasFactory;

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_ACKNOWLEDGED = 'acknowledged';

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_AWAITING_INFO = 'awaiting_information';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_SUBMITTED, self::STATUS_ACKNOWLEDGED, self::STATUS_ASSIGNED,
        self::STATUS_IN_PROGRESS, self::STATUS_AWAITING_INFO,
        self::STATUS_RESOLVED, self::STATUS_CLOSED, self::STATUS_REJECTED,
    ];

    public const OPEN_STATUSES = [
        self::STATUS_SUBMITTED, self::STATUS_ACKNOWLEDGED, self::STATUS_ASSIGNED,
        self::STATUS_IN_PROGRESS, self::STATUS_AWAITING_INFO,
    ];

    public const CATEGORIES = [
        'water_leak' => 'Water leak',
        'no_water' => 'No water',
        'burst_pipe' => 'Burst pipe',
        'refuse' => 'Refuse not collected',
        'road_damage' => 'Road damage',
        'streetlight' => 'Streetlight',
        'sewer' => 'Sewer',
        'billing' => 'Billing',
        'payment' => 'Payment',
        'other' => 'Other',
    ];

    protected $fillable = [
        'ticket_number', 'user_id', 'property_id', 'category', 'description',
        'location_text', 'latitude', 'longitude', 'priority', 'assigned_to',
        'status', 'acknowledged_at', 'resolved_at', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'acknowledged_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ServiceRequestComment::class)->oldest();
    }

    public function publicComments(): HasMany
    {
        return $this->comments()->where('is_internal', false);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst($this->category);
    }
}
