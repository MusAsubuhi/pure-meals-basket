<?php

namespace Database\Factories;

use App\Models\Fulfillment\Fulfillment;
use Illuminate\Database\Eloquent\Factories\Factory;

class FulfillmentEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'fulfillment_id' => Fulfillment::factory(),
            'user_id' => null,
            'event_type' => 'FULFILLMENT_CREATED',
            'description' => fake()->optional()->sentence(),
            'metadata' => [],
            'created_at' => now(),
        ];
    }
}
