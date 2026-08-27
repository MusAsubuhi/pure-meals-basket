<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PriceTier extends Model
{
    protected $fillable = [
        'priceable_type',
        'priceable_id',
        'min_quantity',
        'max_quantity',
        'unit_price',
        'label',
    ];

    protected $casts = [
        'min_quantity' => 'decimal:3',
        'max_quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
    ];

    public function priceable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Resolve the tier bracket covering the given quantity.
     * A null max_quantity means "and above" (top bracket).
     */
    public static function forQuantity(iterable $tiers, float $quantity): ?self
    {
        foreach ($tiers as $tier) {
            if ($quantity < (float) $tier->min_quantity) {
                continue;
            }

            if ($tier->max_quantity !== null && $quantity > (float) $tier->max_quantity) {
                continue;
            }

            return $tier;
        }

        return null;
    }
}
