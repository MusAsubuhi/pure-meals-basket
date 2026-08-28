<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'quotation_item_id' => null,
            'item_type' => fake()->randomElement(['product', 'service']),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'quantity' => fake()->randomFloat(3, 1, 100),
            'unit' => fake()->randomElement(['piece', 'kg', 'litre', 'person', 'service']),
            'unit_price' => fake()->randomFloat(2, 100, 10000),
            'subtotal' => 0,
            'options' => null,
            'metadata' => null,
        ];
    }

    public function forOrder(Order $order): static
    {
        return $this->state(fn (array $attributes) => [
            'order_id' => $order->id,
        ]);
    }

    public function withPrice(float $unitPrice, float $quantity = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'subtotal' => $unitPrice * $quantity,
        ]);
    }
}
