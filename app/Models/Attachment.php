<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    protected $fillable = [
        'attachable_type', 'attachable_id', 'uploaded_by',
        'disk', 'path', 'original_name', 'mime_type', 'size',
    ];

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function url(): string
    {
        return route('attachments.show', $this);
    }

    public function stream()
    {
        return Storage::disk($this->disk)->response($this->path, $this->original_name, [
            'Content-Type' => $this->mime_type,
        ]);
    }
}
