<?php

namespace Database\Factories;

use App\Enums\Quotation\QuotationStatus;
use App\Models\Request\Request;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuotationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'request_id' => Request::factory(),
            'reference' => 'QUO-'.now()->year.'-'.str_pad(fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'status' => QuotationStatus::DRAFT,
            'subtotal' => 0,
            'discount' => 0,
            'total' => 0,
            'notes' => null,
            'created_by' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => QuotationStatus::DRAFT,
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => QuotationStatus::SENT,
            'sent_at' => now(),
            'valid_until' => now()->addDays(7),
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => QuotationStatus::ACCEPTED,
            'sent_at' => now()->subDays(2),
            'valid_until' => now()->addDays(5),
            'accepted_at' => now(),
        ]);
    }

    public function declined(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => QuotationStatus::DECLINED,
            'sent_at' => now()->subDays(2),
            'valid_until' => now()->addDays(5),
            'declined_at' => now(),
        ]);
    }

    public function withdrawn(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => QuotationStatus::WITHDRAWN,
            'sent_at' => now()->subDays(2),
            'valid_until' => now()->addDays(5),
            'withdrawn_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => QuotationStatus::EXPIRED,
            'sent_at' => now()->subDays(10),
            'valid_until' => now()->subDays(3),
            'expired_at' => now()->subDays(3),
        ]);
    }

    public function forRequest(Request $request): static
    {
        return $this->state(fn (array $attributes) => [
            'request_id' => $request->id,
        ]);
    }

    public function withTotals(float $subtotal, float $discount = 0): static
    {
        return $this->state(fn (array $attributes) => [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => max(0, $subtotal - $discount),
        ]);
    }
}
