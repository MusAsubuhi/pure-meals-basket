<?php

namespace App\Enums\Payment;

enum PaymentProvider: string
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
}
