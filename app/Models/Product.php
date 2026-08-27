<?php

namespace App\Models;

use App\Enums\CatalogStatus;
use App\Enums\PricingType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'short_description',
        'image_path',
        'pricing_type',
        'base_price',
        'unit',
        'minimum_quantity',
        'maximum_quantity',
        'is_available',
        'requires_review',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'pricing_type' => PricingType::class,
        'status' => CatalogStatus::class,
        'base_price' => 'decimal:2',
        'minimum_quantity' => 'decimal:3',
        'maximum_quantity' => 'decimal:3',
        'is_available' => 'boolean',
        'requires_review' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $product) {
            if (empty($product->slug)) {
                $product->slug = self::generateUniqueSlug($product->name);
            }
        });
    }

    /**
     * Clean, unique slug derived from the name (e.g. "chocolate-cake",
     * "chocolate-cake-2" on a collision) — no random suffixes.
     */
    public static function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 2;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class)->orderBy('sort_order');
    }

    public function tiers(): MorphMany
    {
        return $this->morphMany(PriceTier::class, 'priceable')->orderBy('min_quantity');
    }

    /**
     * Items the customer can currently request:
     * active status + available + active parent category.
     */
    public function scopeRequestable(Builder $query): Builder
    {
        return $query
            ->where('status', CatalogStatus::ACTIVE)
            ->where('is_available', true)
            ->whereHas('category', fn (Builder $q) => $q->where('is_active', true));
    }
}
