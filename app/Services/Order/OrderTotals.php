<?php

namespace App\Services\Order;

class OrderTotals
{
    public function __construct(
        public readonly float $subtotal,
        public readonly float $discount,
        public readonly float $total,
        public readonly float $paymentRequired,
        public readonly float $amountPaid,
        public readonly float $balanceDue,
    ) {}
}
