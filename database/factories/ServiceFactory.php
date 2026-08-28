<?php

namespace Database\Factories;

use App\Enums\CatalogStatus;
use App\Enums\PricingType;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name' => fake()->words(3, true),
            'slug' => fake()->slug(),
            'description' => fake()->paragraph(),
            'short_description' => fake()->sentence(),
            'pricing_type' => PricingType::PER_PERSON,
            'base_price' => fake()->randomFloat(2, 100, 10000),
            'unit' => 'person',
            'minimum_quantity' => 10,
            'maximum_quantity' => 500,
            'is_available' => true,
            'requires_review' => false,
            'status' => CatalogStatus::ACTIVE,
            'sort_order' => 0,
        ];
    }

    public function perPerson(): static
    {
        return $this->state(fn (array $attributes) => [
            'pricing_type' => PricingType::PER_PERSON,
            'base_price' => 650,
            'unit' => 'person',
            'minimum_quantity' => 30,
            'maximum_quantity' => 1000,
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
