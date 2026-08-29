<?php

namespace App\Models;

use App\Enums\CatalogStatus;
use App\Enums\PricingType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'short_description',
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
        static::creating(function (self $service) {
            if (empty($service->slug)) {
                $service->slug = self::generateUniqueSlug($service->name);
            }
        });
    }

    /**
     * Clean, unique slug derived from the name.
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

    public function tiers(): MorphMany
    {
        return $this->morphMany(PriceTier::class, 'priceable')->orderBy('min_quantity');
    }

    /**
     * Services the customer can currently request.
     */
    public function scopeRequestable(Builder $query): Builder
    {
        return $query
            ->where('status', CatalogStatus::ACTIVE)
            ->where('is_available', true)
            ->where(function (Builder $q) {
                // Services may live without a category; a missing category
                // does not block requestability — only a deactivated one does.
                $q->whereHas('category', fn (Builder $c) => $c->where('is_active', true))
                    ->orWhereNull('category_id');
            });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', CatalogStatus::ACTIVE);
    }
}
