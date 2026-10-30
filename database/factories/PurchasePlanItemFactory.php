<?php

namespace Database\Factories;

use App\Models\PurchasePlan;
use App\Models\PurchasePlanItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchasePlanItem>
 */
class PurchasePlanItemFactory extends Factory
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
            'purchase_plan_id' => PurchasePlan::factory(),
            'item_name' => fake()->words(3, true),
            'supplier_name' => fake()->company(),
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'total_cost' => $unitPrice * $quantity,
        ];
    }
}
