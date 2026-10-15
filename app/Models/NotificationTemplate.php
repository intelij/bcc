<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    protected $fillable = ['event', 'channel', 'subject', 'body_template', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** Render {{placeholder}} tokens against a context array. */
    public function render(array $context): string
    {
        return preg_replace_callback('/\{\{\s*([\w.]+)\s*\}\}/', function ($m) use ($context) {
            return (string) data_get($context, $m[1], '');
        }, $this->body_template);
    }
}
