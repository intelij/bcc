<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeterReading extends Model
{
    use HasFactory;

    public const TYPE_ACTUAL = 'actual';

    public const TYPE_ESTIMATED = 'estimated';

    public const TYPE_CORRECTED = 'corrected';

    public const SOURCES = ['manual', 'resident', 'smart_meter', 'import'];

    protected $fillable = [
        'water_meter_id', 'previous_reading', 'current_reading', 'consumption',
        'reading_date', 'reading_type', 'source', 'recorded_by',
    ];

    protected function casts(): array
    {
        return ['reading_date' => 'date'];
    }

    public function meter(): BelongsTo
    {
        return $this->belongsTo(WaterMeter::class, 'water_meter_id');
    }
}
