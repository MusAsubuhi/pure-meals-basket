<?php

namespace App\Services\Pricing;

/**
 * The catalogue item cannot currently be requested
 * (inactive, unavailable, or archived).
 */
class UnavailableItemException extends PricingException
{
    public function __construct()
    {
        parent::__construct('This item is not available right now.');
    }
}
