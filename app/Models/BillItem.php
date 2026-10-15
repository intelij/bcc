<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillItem extends Model
{
    public const TYPE_RATES = 'rates';

    public const TYPE_WATER = 'water';

    public const TYPE_REFUSE = 'refuse';

    public const TYPE_SEWER = 'sewer';

    public const TYPE_OTHER = 'other';

    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'bill_id', 'charge_type', 'description', 'quantity',
        'unit_price_cents', 'amount_cents', 'meter_reading_id',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }
}
