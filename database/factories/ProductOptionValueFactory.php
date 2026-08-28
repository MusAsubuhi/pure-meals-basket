<?php

namespace Database\Factories;

use App\Models\ProductOption;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductOptionValueFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_option_id' => ProductOption::factory(),
            'name' => fake()->word(),
            'value' => fake()->optional()->word(),
            'price_modifier' => 0,
            'is_available' => true,
            'sort_order' => 0,
        ];
    }

    public function withModifier(float $modifier): static
    {
        return $this->state(fn (array $attributes) => [
            'price_modifier' => $modifier,
        ]);
    }
}
