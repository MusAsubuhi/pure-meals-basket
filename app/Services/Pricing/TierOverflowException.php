<?php

namespace App\Services\Pricing;

/**
 * A tiered price was requested with a quantity above the top bracket.
 * Per the catalogue rules this requires a custom quotation from PMB.
 */
class TierOverflowException extends PricingException
{
    public function __construct(public readonly ?float $highest_bracket_max)
    {
        parent::__construct('Quantities above the highest tier require a quotation.');
    }
}
