<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->optional()->sentence(),
            'image_path' => null,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
