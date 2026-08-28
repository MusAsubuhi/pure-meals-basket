<?php

namespace Database\Factories;

use App\Enums\Order\FulfillmentMethod;
use App\Enums\Order\OrderStatus;
use App\Enums\Order\PaymentStatus;
use App\Models\Order;
use App\Models\Request\Request;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'request_id' => Request::factory(),
            'quotation_id' => null,
            'reference' => 'ORD-' . now()->year . '-' . str_pad(fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'status' => OrderStatus::DRAFT,
            'payment_status' => PaymentStatus::UNPAID,
            'fulfillment_method' => null,
            'event_date' => fake()->optional()->date(),
            'event_time' => fake()->optional()->time(),
            'location' => fake()->optional()->city(),
            'delivery_address' => fake()->optional()->address(),
            'delivery_notes' => fake()->optional()->sentence(),
            'customer_name' => fake()->name(),
            'customer_phone' => fake()->phoneNumber(),
            'customer_email' => fake()->optional()->safeEmail(),
            'subtotal' => 0,
            'discount' => 0,
            'total' => 0,
            'payment_required' => 0,
            'amount_paid' => 0,
            'balance_due' => 0,
            'notes' => fake()->optional()->sentence(),
            'created_by' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::DRAFT,
        ]);
    }

    public function pendingPayment(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::PENDING_PAYMENT,
        ]);
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::CONFIRMED,
            'payment_status' => PaymentStatus::PAID,
        ]);
    }

    public function preparing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::PREPARING,
            'payment_status' => PaymentStatus::PAID,
        ]);
    }

    public function ready(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::READY,
            'payment_status' => PaymentStatus::PAID,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::COMPLETED,
            'payment_status' => PaymentStatus::PAID,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::CANCELLED,
        ]);
    }

    public function forRequest(Request $request): static
    {
        return $this->state(fn (array $attributes) => [
            'request_id' => $request->id,
        ]);
    }

    public function withTotals(float $subtotal, float $discount = 0, float $paymentRequired = 0): static
    {
        return $this->state(fn (array $attributes) => [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => max(0, $subtotal - $discount),
            'payment_required' => $paymentRequired,
            'balance_due' => max(0, $subtotal - $discount - $paymentRequired),
        ]);
    }

    public function withPaymentStatus(PaymentStatus $status, float $amountPaid = 0): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => $status,
            'amount_paid' => $amountPaid,
        ]);
    }
}
