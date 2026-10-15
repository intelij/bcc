<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WaterMeter extends Model
{
    use HasFactory;

    protected $fillable = ['meter_number', 'property_id', 'status'];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function readings(): HasMany
    {
        return $this->hasMany(MeterReading::class)->latest('reading_date');
    }

    public function latestReading()
    {
        return $this->hasOne(MeterReading::class)->latestOfMany('reading_date');
    }
}
