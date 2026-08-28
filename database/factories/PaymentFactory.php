<?php

namespace Database\Factories;

use App\Enums\Payment\PaymentMethod;
use App\Enums\Payment\PaymentProvider;
use App\Enums\Payment\PaymentStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'customer_id' => null,
            'reference' => 'PAY-'.now()->year.'-'.str_pad(fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'method' => PaymentMethod::MPESA,
            'provider' => PaymentProvider::PAYNEXUS,
            'status' => PaymentStatus::PENDING,
            'amount' => fake()->randomFloat(2, 100, 100000),
            'currency' => 'KES',
            'provider_payment_id' => null,
            'provider_reference' => null,
            'checkout_request_id' => null,
            'paid_at' => null,
            'metadata' => null,
            'created_by' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::PENDING,
        ]);
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::PROCESSING,
        ]);
    }

    public function success(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::SUCCESS,
            'paid_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::FAILED,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::CANCELLED,
        ]);
    }

    public function reversed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::REVERSED,
        ]);
    }

    public function mpesa(): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => PaymentMethod::MPESA,
            'provider' => PaymentProvider::PAYNEXUS,
        ]);
    }

    public function cash(): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => PaymentMethod::CASH,
            'provider' => PaymentProvider::CASH,
        ]);
    }

    public function forOrder(Order $order): static
    {
        return $this->state(fn (array $attributes) => [
            'order_id' => $order->id,
        ]);
    }

    public function withAmount(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $amount,
        ]);
    }
}
