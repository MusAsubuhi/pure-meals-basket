<?php

namespace App\Models;

use App\Enums\DeliveryType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DeliveryZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'boundary',
        'delivery_charge',
        'base_charge',
        'door_to_door_surcharge',
        'beyond_stage_surcharge',
        'free_delivery_above',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'boundary' => 'array',
        'delivery_charge' => 'decimal:2',
        'base_charge' => 'decimal:2',
        'door_to_door_surcharge' => 'decimal:2',
        'beyond_stage_surcharge' => 'decimal:2',
        'free_delivery_above' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $zone) {
            if (empty($zone->slug)) {
                $zone->slug = Str::slug($zone->name);
            }

            // Keep the legacy flat column in sync with the tiered charges so
            // existing code reading `delivery_charge` still works.
            $zone->delivery_charge = $zone->chargeFor(DeliveryType::STANDARD, 0);
        });

        static::updating(function (self $zone) {
            if ($zone->isDirty(['base_charge', 'door_to_door_surcharge', 'beyond_stage_surcharge'])) {
                $zone->delivery_charge = $zone->chargeFor(DeliveryType::STANDARD, 0);
            }
        });
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function fulfillments(): HasMany
    {
        return $this->hasMany(Fulfillment::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Resolve the active delivery zone whose boundary contains the given
     * location string. Zones are tried in sort order so the most specific
     * (highest sort order) match wins; the first zone whose boundary array
     * contains the location is returned.
     */
    public function scopeResolveForLocation(Builder $query, ?string $location): Builder
    {
        if ($location === null || trim($location) === '') {
            return $query->whereRaw('1 = 0');
        }

        $needle = Str::of($location)->lower()->trim()->__toString();

        $isPgsql = $query->getConnection()->getDriverName() === 'pgsql';

        return $query->active()
            ->ordered()
            ->where(function (Builder $q) use ($needle, $isPgsql) {
                if ($isPgsql) {
                    // Postgres has no JSON_EXTRACT and no LIKE operator for json
                    // columns, so compare the boundary as lower-cased text.
                    $q->whereRaw('LOWER(boundary::text) LIKE ?', ["%{$needle}%"]);

                    return;
                }

                $q->whereRaw("JSON_EXTRACT(boundary, '$[*]') LIKE ?", "%{$needle}%")
                  ->orWhere('boundary', 'like', "%{$needle}%");
            });
    }

    /**
     * Whether delivery is free for an order total that meets or exceeds
     * the configured free-delivery threshold.
     */
    public function freeDeliveryFor(float $orderTotal): bool
    {
        return $this->free_delivery_above !== null && $orderTotal >= (float) $this->free_delivery_above;
    }

    /**
     * The delivery charge applicable to this zone for a given order total
     * and delivery type. Returns 0 when the order qualifies for free
     * delivery (free_delivery_above threshold met).
     *
     * The total charge is the zone's base_charge plus whichever tier
     * surcharges the requested delivery type triggers:
     *   - standard: base only
     *   - door_to_door: base + door_to_door_surcharge
     *   - beyond_stage: base + beyond_stage_surcharge
     */
    public function chargeFor(DeliveryType $type, float $orderTotal): float
    {
        if ($this->freeDeliveryFor($orderTotal)) {
            return 0.0;
        }

        $charge = (float) $this->base_charge;

        if ($type === DeliveryType::DOOR_TO_DOOR) {
            $charge += (float) $this->door_to_door_surcharge;
        } elseif ($type === DeliveryType::BEYOND_STAGE) {
            $charge += (float) $this->beyond_stage_surcharge;
        }

        return $charge;
    }
}