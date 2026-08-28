<?php

namespace App\Enums\Order;

enum PaymentStatus: string
{
    case UNPAID           = 'UNPAID';
    case PARTIALLY_PAID   = 'PARTIALLY_PAID';
    case PAID             = 'PAID';

    public function label(): string
    {
        return match ($this) {
            self::UNPAID         => 'Unpaid',
            self::PARTIALLY_PAID => 'Partially Paid',
            self::PAID           => 'Paid',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::UNPAID         => 'danger',
            self::PARTIALLY_PAID => 'warning',
            self::PAID           => 'success',
        };
    }
}
