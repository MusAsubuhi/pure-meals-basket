<?php

namespace App\Services\Delivery;

use App\Enums\DeliveryType;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Request\Request;

/**
 * Validates that an order's delivery address falls inside an active
 * delivery zone, and resolves the zone + applicable charge.
 */
class DeliveryZoneValidator
{
    /**
     * Resolve the active delivery zone for a location string.
     *
     * Returns null when no active zone matches — the caller is then
     * responsible for showing the "we can't deliver here" message.
     */
    public function resolve(?string $location): ?DeliveryZone
    {
        if ($location === null || trim($location) === '') {
            return null;
        }

        return DeliveryZone::resolveForLocation($location)->first();
    }

    /**
     * Whether PMB can deliver to the given location at all.
     */
    public function canDeliver(?string $location): bool
    {
        return $this->resolve($location) !== null;
    }

    /**
     * Validate a delivery address at request submission time.
     *
     * Throws when the location is outside every active zone so the
     * checkout form can surface a clear error to the customer.
     */
    public function validateLocation(?string $location): void
    {
        if (! $this->canDeliver($location)) {
            throw new \RuntimeException(
                'We cannot deliver to the address you provided. Please choose a different location or contact PMB directly.'
            );
        }
    }

    /**
     * Compute the delivery charge for an order given a zone and type.
     */
    public function chargeFor(Order $order, DeliveryZone $zone, DeliveryType $type): float
    {
        return $zone->chargeFor($type, $order->subtotal);
    }
}