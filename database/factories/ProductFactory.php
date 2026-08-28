<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Enums\PricingType;
use App\Enums\CatalogStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name' => fake()->words(3, true),
            'slug' => fake()->slug(),
            'description' => fake()->paragraph(),
            'short_description' => fake()->sentence(),
            'image_path' => null,
            'pricing_type' => PricingType::FIXED,
            'base_price' => fake()->randomFloat(2, 100, 10000),
            'unit' => 'piece',
            'minimum_quantity' => 1,
            'maximum_quantity' => 100,
            'is_available' => true,
            'requires_review' => false,
            'status' => CatalogStatus::ACTIVE,
            'sort_order' => 0,
        ];
    }

    public function perWeight(): static
    {
        return $this->state(fn (array $attributes) => [
            'pricing_type' => PricingType::PER_WEIGHT,
            'base_price' => 1000,
            'unit' => 'kg',
            'minimum_quantity' => 1,
            'maximum_quantity' => 20,
        ]);
    }

    public function custom(): static
    {
        return $this->state(fn (array $attributes) => [
            'pricing_type' => PricingType::CUSTOM,
            'base_price' => null,
            'unit' => null,
            'minimum_quantity' => null,
            'maximum_quantity' => null,
            'requires_review' => true,
        ]);
    }
}
