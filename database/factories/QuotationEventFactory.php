<?php

namespace Database\Factories;

use App\Models\Quotation;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuotationEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'quotation_id' => Quotation::factory(),
            'user_id' => null,
            'event_type' => fake()->randomElement([
                'CREATED',
                'ITEM_ADDED',
                'ITEM_UPDATED',
                'ITEM_REMOVED',
                'DISCOUNT_APPLIED',
                'CHARGE_ADDED',
                'SENT',
                'VIEWED',
                'ACCEPTED',
                'DECLINED',
                'WITHDRAWN',
                'EXPIRED',
                'REPLACEMENT_CREATED',
            ]),
            'data' => null,
            'created_at' => now(),
        ];
    }

    public function forQuotation(Quotation $quotation): static
    {
        return $this->state(fn (array $attributes) => [
            'quotation_id' => $quotation->id,
        ]);
    }

    public function withUser(int $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
        ]);
    }
}
