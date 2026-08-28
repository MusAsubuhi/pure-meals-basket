<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class OrderItem extends Model
{
    protected $table = 'order_items';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'order_id',
        'quotation_item_id',
        'item_type',
        'name',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'subtotal',
        'options',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'options' => 'array',
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

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function quotationItem(): BelongsTo
    {
        return $this->belongsTo(QuotationItem::class, 'quotation_item_id');
    }
}
