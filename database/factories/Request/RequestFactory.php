<?php

namespace Database\Factories\Request;

use App\Models\Customer;
use App\Enums\Request\RequestStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class RequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'reference' => 'REQ-' . now()->year . '-' . str_pad(fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'status' => RequestStatus::DRAFT,
            'event_date' => fake()->optional()->date(),
            'event_time' => fake()->optional()->time(),
            'location' => fake()->optional()->city(),
            'notes' => fake()->optional()->sentence(),
            'submitted_at' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RequestStatus::DRAFT,
        ]);
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RequestStatus::SUBMITTED,
            'submitted_at' => now(),
        ]);
    }

    public function quotationRequired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RequestStatus::QUOTATION_REQUIRED,
        ]);
    }

    public function underReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RequestStatus::UNDER_REVIEW,
            'submitted_at' => now()->subDays(1),
        ]);
    }

    public function readyForCheckout(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RequestStatus::READY_FOR_CHECKOUT,
            'submitted_at' => now()->subDays(2),
        ]);
    }
}
