<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyUser extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REVOKED = 'revoked';

    public const REL_OWNER = 'owner';

    public const REL_TENANT = 'tenant';

    public const REL_AGENT = 'agent';

    protected $fillable = [
        'property_id', 'user_id', 'relationship', 'status',
        'can_view_bills', 'can_view_account', 'can_pay', 'can_submit_requests', 'can_view_statements',
        'authorized_by', 'authorized_at',
    ];

    protected function casts(): array
    {
        return [
            'can_view_bills' => 'boolean',
            'can_view_account' => 'boolean',
            'can_pay' => 'boolean',
            'can_submit_requests' => 'boolean',
            'can_view_statements' => 'boolean',
            'authorized_at' => 'datetime',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function authorizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }
}
