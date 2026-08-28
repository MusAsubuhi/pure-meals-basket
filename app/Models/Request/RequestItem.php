<?php

namespace App\Models\Request;

use App\Enums\PricingType;
use App\Enums\Request\RequestItemPricingStatus;
use App\Models\Product;
use App\Models\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class RequestItem extends Model
{
    protected $table = 'request_items';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'request_id',
        'item_type',
        'product_id',
        'service_id',
        'name',
        'quantity',
        'unit',
        'options',
        'pricing_type',
        'pricing_status',
        'unit_price',
        'subtotal',
        'pricing_breakdown',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'options' => 'array',
        'pricing_breakdown' => 'array',
        'pricing_type' => PricingType::class,
        'pricing_status' => RequestItemPricingStatus::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $item) {
            if (empty($item->id)) {
                $item->id = (string) Str::uuid();
            }
        });
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * True when this item has been calculated (not awaiting manual quote)
     */
    public function isCalculated(): bool
    {
        return ($this->pricing_status ?? null) === RequestItemPricingStatus::CALCULATED;
    }

    /**
     * True when this item requires a PMB-generated quotation
     */
    public function isQuotationRequired(): bool
    {
        return ($this->pricing_status ?? null) === RequestItemPricingStatus::QUOTATION_REQUIRED;
    }
}
