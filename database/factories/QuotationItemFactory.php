<?php

namespace Database\Factories;

use App\Models\Quotation;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuotationItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'quotation_id' => Quotation::factory(),
            'request_item_id' => null,
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'quantity' => fake()->randomFloat(3, 1, 100),
            'unit' => fake()->randomElement(['piece', 'kg', 'litre', 'person', 'service']),
            'unit_price' => fake()->randomFloat(2, 100, 10000),
            'subtotal' => 0,
            'metadata' => null,
        ];
    }

    public function forQuotation(Quotation $quotation): static
    {
        return $this->state(fn (array $attributes) => [
            'quotation_id' => $quotation->id,
        ]);
    }

    public function withPrice(float $unitPrice, float $quantity = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'subtotal' => $unitPrice * $quantity,
        ]);
    }
}
