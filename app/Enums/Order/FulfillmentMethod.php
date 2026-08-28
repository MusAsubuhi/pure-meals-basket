<?php

namespace App\Enums\Order;

enum FulfillmentMethod: string
{
    case DELIVERY          = 'DELIVERY';
    case CUSTOMER_COLLECTION = 'CUSTOMER_COLLECTION';
    case ON_SITE_SERVICE   = 'ON_SITE_SERVICE';

    public function label(): string
    {
        return match ($this) {
            self::DELIVERY           => 'Delivery',
            self::CUSTOMER_COLLECTION => 'Customer Collection',
            self::ON_SITE_SERVICE    => 'On-site Service',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::DELIVERY           => 'primary',
            self::CUSTOMER_COLLECTION => 'gray',
            self::ON_SITE_SERVICE    => 'success',
        };
    }
}
