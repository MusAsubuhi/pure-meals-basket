<?php

namespace Database\Factories;

use App\Enums\Order\FulfillmentMethod;
use App\Enums\Order\FulfillmentStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class FulfillmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'method' => FulfillmentMethod::DELIVERY,
            'status' => FulfillmentStatus::PENDING,
            'scheduled_at' => fake()->optional()->dateTime(),
            'started_at' => null,
            'ready_at' => null,
            'dispatched_at' => null,
            'delivered_at' => null,
            'collected_at' => null,
            'service_started_at' => null,
            'completed_at' => null,
            'delivery_address' => fake()->optional()->address(),
            'delivery_contact_name' => fake()->optional()->name(),
            'delivery_contact_phone' => fake()->optional()->phoneNumber(),
            'collection_notes' => null,
            'service_location' => null,
            'service_notes' => null,
            'recipient_name' => null,
            'failure_reason' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FulfillmentStatus::PENDING,
        ]);
    }

    public function preparing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FulfillmentStatus::PREPARING,
            'started_at' => now(),
        ]);
    }

    public function ready(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FulfillmentStatus::READY,
            'started_at' => now()->subHours(2),
            'ready_at' => now(),
        ]);
    }

    public function outForDelivery(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FulfillmentStatus::OUT_FOR_DELIVERY,
            'started_at' => now()->subHours(3),
            'ready_at' => now()->subHours(2),
            'dispatched_at' => now(),
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FulfillmentStatus::DELIVERED,
            'started_at' => now()->subHours(4),
            'ready_at' => now()->subHours(3),
            'dispatched_at' => now()->subHours(2),
            'delivered_at' => now(),
            'recipient_name' => fake()->name(),
        ]);
    }

    public function collected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FulfillmentStatus::COLLECTED,
            'started_at' => now()->subHours(2),
            'ready_at' => now()->subHours(1),
            'collected_at' => now(),
            'recipient_name' => fake()->name(),
        ]);
    }

    public function serviceInProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FulfillmentStatus::SERVICE_IN_PROGRESS,
            'started_at' => now()->subHours(2),
            'ready_at' => now()->subHours(1),
            'service_started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FulfillmentStatus::COMPLETED,
            'started_at' => now()->subHours(3),
            'ready_at' => now()->subHours(2),
            'completed_at' => now(),
        ]);
    }

    public function deliveryFailed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FulfillmentStatus::DELIVERY_FAILED,
            'started_at' => now()->subHours(3),
            'ready_at' => now()->subHours(2),
            'dispatched_at' => now()->subHours(1),
            'failure_reason' => fake()->sentence(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FulfillmentStatus::CANCELLED,
        ]);
    }

    public function forMethod(FulfillmentMethod $method): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => $method,
        ]);
    }
}
