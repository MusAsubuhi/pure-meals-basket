<?php

namespace App\Enums\Request;

enum RequestItemPricingStatus: string
{
    case CALCULATED           = 'CALCULATED';
    case QUOTATION_REQUIRED   = 'QUOTATION_REQUIRED';

    public function label(): string
    {
        return match ($this) {
            self::CALCULATED          => 'Calculated',
            self::QUOTATION_REQUIRED  => 'Quotation Required',
        };
    }
}
