<?php

namespace App\Services\Quotation;

class QuotationTotals
{
    public function __construct(
        public readonly float $subtotal,
        public readonly float $discount,
        public readonly float $total,
    ) {}
}
