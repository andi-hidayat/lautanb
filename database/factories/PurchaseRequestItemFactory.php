<?php

namespace Database\Factories;

use App\Models\PurchaseRequestItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseRequestItem>
 */
class PurchaseRequestItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unitPrice = fake()->randomFloat(2, 10000, 50000000);
        $quantity = fake()->numberBetween(1, 50);

        return [
            'goods_services_required_name' => fake()->words(3, true),
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'total_cost' => $unitPrice * $quantity,
        ];
    }
}
