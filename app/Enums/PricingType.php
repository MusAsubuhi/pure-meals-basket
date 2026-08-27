<?php

namespace App\Enums;

enum PricingType: string
{
    case FIXED = 'fixed';
    case PER_UNIT = 'per_unit';
    case PER_WEIGHT = 'per_weight';
    case PER_VOLUME = 'per_volume';
    case PER_PERSON = 'per_person';
    case TIERED = 'tiered';
    case CUSTOM = 'custom';

    /**
     * Friendly labels used by the admin UI and the storefront.
     */
    public function label(): string
    {
        return match ($this) {
            self::FIXED => 'Fixed price',
            self::PER_UNIT => 'Per unit',
            self::PER_WEIGHT => 'Per kilogram',
            self::PER_VOLUME => 'Per litre',
            self::PER_PERSON => 'Per person',
            self::TIERED => 'Tiered pricing',
            self::CUSTOM => 'Custom quotation',
        };
    }

    /**
     * The unit label shown next to the base price, e.g. "KSh 1,000 / kg".
     */
    public function unitSuffix(?string $unit): string
    {
        return match ($this) {
            self::FIXED => '',
            self::PER_UNIT, self::PER_WEIGHT, self::PER_VOLUME, self::PER_PERSON => $unit ? '/ ' . $unit : '',
            self::TIERED => 'tiered',
            self::CUSTOM => 'quotation',
        };
    }

    /**
     * Pricing types that multiply the price by a customer-entered quantity.
     */
    public function usesQuantity(): bool
    {
        return match ($this) {
            self::FIXED => false,
            self::CUSTOM => false,
            default => true,
        };
    }

    /**
     * Pricing types that require a quantity to be supplied by the customer.
     * Fixed pricing ignores quantity entirely; custom is a pure quote.
     */
    public function requiresQuantity(): bool
    {
        return $this->usesQuantity();
    }

    /**
     * Whether the item can be priced automatically at all.
     * TIERED items fall back to custom when no bracket matches the quantity.
     */
    public function supportsAutomaticPricing(): bool
    {
        return ! in_array($this, [self::CUSTOM], true);
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
