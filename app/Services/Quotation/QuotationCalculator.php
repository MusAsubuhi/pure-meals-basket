<?php

namespace App\Services\Quotation;

use InvalidArgumentException;

class QuotationCalculator
{
    public function calculate(float $subtotal, float $discount): QuotationTotals
    {
        if ($subtotal < 0) {
            throw new InvalidArgumentException('Subtotal cannot be negative.');
        }

        if ($discount < 0) {
            throw new InvalidArgumentException('Discount cannot be negative.');
        }

        if ($discount > $subtotal) {
            throw new InvalidArgumentException('Discount cannot exceed subtotal.');
        }

        $total = $subtotal - $discount;

        return new QuotationTotals(
            subtotal: $subtotal,
            discount: $discount,
            total: $total,
        );
    }
}
