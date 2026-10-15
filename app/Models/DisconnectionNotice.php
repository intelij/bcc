<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisconnectionNotice extends Model
{
    use HasFactory;

    public const STATUS_WARNING = 'warning';

    public const STATUS_NOTICE_ISSUED = 'notice_issued';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_DISCONNECTED = 'disconnected';

    public const STATUS_RESTORATION_PENDING = 'restoration_pending';

    public const STATUS_RESTORED = 'restored';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_WARNING, self::STATUS_NOTICE_ISSUED, self::STATUS_SCHEDULED,
        self::STATUS_DISCONNECTED, self::STATUS_RESTORATION_PENDING,
        self::STATUS_RESTORED, self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'municipal_account_id', 'property_id', 'arrears_cents', 'status',
        'notice_date', 'scheduled_date', 'disconnected_at', 'restored_at',
        'reason', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'notice_date' => 'date',
            'scheduled_date' => 'date',
            'disconnected_at' => 'datetime',
            'restored_at' => 'datetime',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
