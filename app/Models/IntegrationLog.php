<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'integration', 'direction', 'operation', 'status',
        'request_summary', 'response_summary', 'duration_ms', 'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }
}
