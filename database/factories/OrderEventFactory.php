<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'user_id' => null,
            'event_type' => fake()->randomElement([
                'CREATED',
                'PAYMENT_REQUIRED',
                'PAYMENT_RECEIVED',
                'CONFIRMED',
                'PREPARING',
                'READY',
                'OUT_FOR_DELIVERY',
                'DELIVERED',
                'COMPLETED',
                'CANCELLED',
                'CHANGE_REQUESTED',
                'CHANGE_APPROVED',
                'CHANGE_DECLINED',
            ]),
            'data' => null,
            'created_at' => now(),
        ];
    }

    public function forOrder(Order $order): static
    {
        return $this->state(fn (array $attributes) => [
            'order_id' => $order->id,
        ]);
    }

    public function withUser(int $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
        ]);
    }
}
