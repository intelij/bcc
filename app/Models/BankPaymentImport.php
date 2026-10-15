<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankPaymentImport extends Model
{
    protected $fillable = ['imported_by', 'filename', 'rows_total', 'rows_matched', 'rows_unmatched', 'status'];

    public function importer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}
