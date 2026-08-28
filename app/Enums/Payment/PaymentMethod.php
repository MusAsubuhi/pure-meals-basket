<?php

namespace App\Enums\Payment;

enum PaymentMethod: string
{
    case MPESA = 'MPESA';
    case CASH = 'CASH';

    public function label(): string
    {
        return match ($this) {
            self::MPESA => 'M-Pesa',
            self::CASH => 'Cash',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::MPESA => 'primary',
            self::CASH => 'gray',
        };
    }
}
