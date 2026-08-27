<?php

namespace App\Services\Pricing;

/**
 * The quantity supplied falls outside the item's configured bounds.
 */
class InvalidQuantityException extends PricingException
{
    public function __construct(
        public readonly ?float $minimum_quantity,
        public readonly ?float $maximum_quantity,
    ) {
        if ($minimum_quantity !== null && $maximum_quantity !== null) {
            $message = "Quantity must be between {$minimum_quantity} and {$maximum_quantity}.";
        } elseif ($minimum_quantity !== null) {
            $message = "Minimum quantity is {$minimum_quantity}.";
        } else {
            $message = "Maximum quantity is {$maximum_quantity}.";
        }

        parent::__construct($message);
    }
}
