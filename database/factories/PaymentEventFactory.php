<?php

namespace Database\Factories;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'user_id' => null,
            'event_type' => fake()->randomElement([
                'INITIATED',
                'PROCESSING',
                'STK_PUSH_SENT',
                'CALLBACK_RECEIVED',
                'VERIFICATION_REQUESTED',
                'SUCCESS',
                'FAILED',
                'CANCELLED',
                'REVERSED',
            ]),
            'data' => null,
            'created_at' => now(),
        ];
    }

    public function forPayment(Payment $payment): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_id' => $payment->id,
        ]);
    }

    public function withUser(int $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
        ]);
    }
}
