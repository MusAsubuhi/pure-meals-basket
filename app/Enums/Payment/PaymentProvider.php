<?php

namespace App\Enums\Payment;

enum PaymentProvider: string
{
    case PAYNEXUS = 'PAYNEXUS';
    case CASH = 'CASH';

    public function label(): string
    {
        return match ($this) {
            self::PAYNEXUS => 'PayNexus',
            self::CASH => 'Cash',
        };
    }
}
