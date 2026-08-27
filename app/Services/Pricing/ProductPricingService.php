<?php

namespace App\Services\Pricing;

use App\Enums\PricingType;
use App\Models\PriceTier;
use App\Models\Product;
use App\Models\ProductOptionValue;
use App\Models\Service;
use Illuminate\Support\Collection;

/**
 * The single authority for catalogue pricing.
 *
 * Customers, controllers and Blade views must never calculate prices —
 * they call this service, which validates the request, applies the pricing
 * rule (fixed / per-unit / per-weight / per-volume / per-person / tiered /
 * custom), adds option modifiers and returns a QuoteResult.
 *
 * No database writes happen here; quoting is pure computation. The future
 * Request Engine will snapshot the returned fields onto order items so
 * historical prices never depend on the live catalogue.
 */
class ProductPricingService
{
    /**
     * Calculate the price for a catalogue item.
     *
     * @param  Product|Service  $item
     * @param  float|null  $quantity  Customer-entered quantity in the item's unit
     * @param  array<int, int>  $optionValueIds  Selected ProductOptionValue IDs
     */
    public function quote(Product|Service $item, ?float $quantity = null, array $optionValueIds = []): QuoteResult
    {
        $this->assertRequestable($item);

        return match ($item->pricing_type) {
            PricingType::FIXED => $this->quoteFixed($item, $optionValueIds),
            PricingType::CUSTOM => $this->quoteCustom($item),
            default => $this->quoteQuantified($item, $quantity, $optionValueIds),
        };
    }

    protected function assertRequestable(Product|Service $item): void
    {
        // Treat "not explicitly unavailable" as available (freshly created
        // models carry no value yet; the DB default is available).
        if (! $item->status->isRequestable() || $item->is_available === false) {
            throw new UnavailableItemException();
        }

        // Products additionally require an active parent category
        if ($item instanceof Product && ($item->category === null || ! $item->category->is_active)) {
            throw new UnavailableItemException();
        }
    }

    protected function quoteFixed(Product|Service $item, array $optionValueIds): QuoteResult
    {
        [$optionTotal, $optionLines] = $this->resolveOptions($item, $optionValueIds);

        $base = (float) $item->base_price;

        return new QuoteResult(
            pricing_type: PricingType::FIXED,
            unit_price: $base,
            quantity: null,
            unit: null,
            option_total: $optionTotal,
            subtotal: $base,
            total: round($base + $optionTotal, 2),
            requires_pmb_quote: false,
            breakdown: array_merge(
                ['Base price — ' . $this->money($base)],
                $optionLines,
                ['Total — ' . $this->money($base + $optionTotal)]
            ),
        );
    }

    protected function quoteCustom(Product|Service $item): QuoteResult
    {
        // Custom items are quoted by PMB; the request travels without a
        // numeric price. No automatic calculation exists.
        return new QuoteResult(
            pricing_type: PricingType::CUSTOM,
            unit_price: null,
            quantity: null,
            unit: $item->unit,
            option_total: 0.0,
            subtotal: null,
            total: null,
            requires_pmb_quote: true,
            breakdown: ['Custom item — price determined by PMB.'],
        );
    }

    /**
     * All quantity-based rules: PER_UNIT, PER_WEIGHT, PER_VOLUME, PER_PERSON, TIERED.
     */
    protected function quoteQuantified(Product|Service $item, ?float $quantity, array $optionValueIds): QuoteResult
    {
        if ($quantity === null || $quantity <= 0) {
            throw new InvalidQuantityException($item->minimum_quantity, $item->maximum_quantity);
        }

        $this->assertQuantityWithinBounds($item, $quantity);

        if ($item->pricing_type === PricingType::TIERED) {
            $tier = PriceTier::forQuantity($item->tiers, $quantity);

            if ($tier === null) {
                // Above the top bracket → custom quotation
                throw new TierOverflowException($this->highestBracketMax($item));
            }

            $unitPrice = (float) $tier->unit_price;
        } else {
            $unitPrice = (float) $item->base_price;
        }

        [$optionTotal, $optionLines] = $this->resolveOptions($item, $optionValueIds);

        $subtotal = round($unitPrice * $quantity, 2);
        $total = round($subtotal + $optionTotal, 2);

        return new QuoteResult(
            pricing_type: $item->pricing_type,
            unit_price: $unitPrice,
            quantity: $quantity,
            unit: $item->unit,
            option_total: $optionTotal,
            subtotal: $subtotal,
            total: $total,
            requires_pmb_quote: (bool) $item->requires_review,
            breakdown: array_merge(
                ["{$quantity} {$item->unit} × " . $this->money($unitPrice) . ' — ' . $this->money($subtotal)],
                $optionLines,
                ['Estimated total — ' . $this->money($total)]
            ),
        );
    }
    protected function assertQuantityWithinBounds(Product|Service $item, float $quantity): void
    {
        $min = $item->minimum_quantity !== null ? (float) $item->minimum_quantity : null;
        $max = $item->maximum_quantity !== null ? (float) $item->maximum_quantity : null;

        if (($min !== null && $quantity < $min) || ($max !== null && $quantity > $max)) {
            throw new InvalidQuantityException($min, $max);
        }
    }

    protected function highestBracketMax(Product|Service $item): ?float
    {
        $highest = $item->tiers
            ->filter(fn (PriceTier $t) => $t->max_quantity !== null)
            ->max(fn (PriceTier $t) => (float) $t->max_quantity);

        return $highest !== null ? (float) $highest : null;
    }

    /**
     * Sum selected option-value modifiers, validating that each value
     * belongs to an option of this very product and is available.
     *
     * @return array{0: float, 1: string[]} [total modifier, human-readable lines]
     */
    protected function resolveOptions(Product|Service $item, array $optionValueIds): array
    {
        if (empty($optionValueIds)) {
            return [0.0, []];
        }

        $values = ProductOptionValue::query()
            ->whereIn('id', $optionValueIds)
            ->with('option')
            ->get()
            ->keyBy('id');

        foreach ($optionValueIds as $id) {
            $value = $values->get($id);

            if ($value === null) {
                throw new PricingException("Selected option value #{$id} does not exist.");
            }

            // Values must belong to options of THIS product
            if (! ($item instanceof Product) || $value->option?->product_id !== $item->id) {
                throw new PricingException("Selected option value #{$id} does not belong to this product.");
            }

            if (! $value->is_available) {
                throw new PricingException("Option '{$value->name}' is currently unavailable.");
            }
        }

        $total = round((float) $values->sum(fn (ProductOptionValue $v) => (float) $v->price_modifier), 2);
        $lines = $values
            ->map(fn (ProductOptionValue $v) => sprintf(
                '%s (%s) — %s',
                $v->option->name,
                $v->name,
                (float) $v->price_modifier > 0 ? '+ ' . $this->money((float) $v->price_modifier) : 'included'
            ))
            ->all();

        return [$total, $lines];
    }

    protected function money(float $amount): string
    {
        return 'KSh ' . number_format($amount, 2);
    }
}
