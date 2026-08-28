<?php

namespace App\Services\Order;

use InvalidArgumentException;

class OrderCalculator
{
    public function calculate(float $subtotal, float $discount, float $paymentRequired = 0): OrderTotals
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

        if ($paymentRequired < 0) {
            throw new InvalidArgumentException('Payment required cannot be negative.');
        }

        $total = $subtotal - $discount;
        $balanceDue = max(0, $total - $paymentRequired);

        return new OrderTotals(
            subtotal: $subtotal,
            discount: $discount,
            total: $total,
            paymentRequired: $paymentRequired,
            amountPaid: $paymentRequired,
            balanceDue: $balanceDue,
        );
    }
}
