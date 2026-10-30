<?php

namespace Database\Factories;

use App\Models\PurchasePlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchasePlan>
 */
class PurchasePlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purchase_item_name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'purpose' => fake()->sentence(),
            'year' => (string) fake()->numberBetween(
                (int) date('Y'),
                (int) date('Y') + 5,
            ),
        ];
    }
}
