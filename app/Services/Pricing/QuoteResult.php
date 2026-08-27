<?php

namespace App\Services\Pricing;

use App\Enums\PricingType;
use Illuminate\Contracts\Support\Arrayable;

/**
 * Result of a pricing calculation. Pure data — the Request Engine will
 * snapshot these fields onto order items later, so history never depends
 * on live catalogue prices.
 */
class QuoteResult implements Arrayable
{
    public function __construct(
        public readonly PricingType $pricing_type,
        /** null when PMB must provide a quotation (custom pricing) */
        public readonly ?float $unit_price,
        public readonly ?float $quantity,
        public readonly ?string $unit,
        public readonly float $option_total,
        public readonly ?float $subtotal,
        public readonly ?float $total,
        public readonly string $currency = 'KSh',
        public readonly bool $requires_pmb_quote = false,
        /** Human-readable calculation lines for receipts and quotations */
        public readonly array $breakdown = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'pricing_type' => $this->pricing_type->value,
            'unit_price' => $this->unit_price,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'option_total' => $this->option_total,
            'subtotal' => $this->subtotal,
            'total' => $this->total,
            'currency' => $this->currency,
            'requires_pmb_quote' => $this->requires_pmb_quote,
            'breakdown' => $this->breakdown,
        ];
    }
}
