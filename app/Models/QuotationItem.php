<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class QuotationItem extends Model
{
    protected $table = 'quotation_items';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'quotation_id',
        'request_item_id',
        'name',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'subtotal',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $item) {
            if (empty($item->id)) {
                $item->id = (string) Str::uuid();
            }

            $item->subtotal = $item->unit_price * $item->quantity;
        });

        static::updating(function (self $item) {
            $item->subtotal = $item->unit_price * $item->quantity;
        });
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function requestItem(): BelongsTo
    {
        return $this->belongsTo(RequestItem::class, 'request_item_id');
    }
}
