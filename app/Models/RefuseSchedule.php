<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefuseSchedule extends Model
{
    use HasFactory;

    public const DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    protected $fillable = [
        'area', 'suburb', 'ward', 'collection_day', 'frequency',
        'next_collection_date', 'status', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return ['next_collection_date' => 'date'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function dayName(): string
    {
        return self::DAYS[$this->collection_day] ?? '—';
    }
}
