<?php

namespace Database\Factories;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentAttemptFactory extends Factory
{
    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'attempt_reference' => 'ATT-'.now()->year.'-'.str_pad(fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'provider' => fake()->randomElement(['PAYNEXUS', 'CASH']),
            'status' => fake()->randomElement(['pending', 'processing', 'completed', 'failed']),
            'request_payload' => null,
            'response_payload' => null,
            'provider_reference' => null,
            'checkout_request_id' => null,
            'initiated_at' => now(),
            'completed_at' => null,
        ];
    }

    public function forPayment(Payment $payment): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_id' => $payment->id,
        ]);
    }

    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'completed_at' => now(),
        ]);
    }
}
