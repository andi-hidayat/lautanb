<?php

namespace Database\Factories;

use App\Models\ProductComparison;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductComparison>
 */
class ProductComparisonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'products' => [
                [
                    'product_name' => 'Product A',
                    'supplier_name' => 'Supplier A',
                    'specifications' => [
                        ['name' => 'Dimensions', 'value' => '100 x 50 mm'],
                    ],
                ],
                [
                    'product_name' => 'Product B',
                    'supplier_name' => 'Supplier B',
                    'specifications' => [
                        ['name' => 'Dimensions', 'value' => '120 x 60 mm'],
                    ],
                ],
            ],
        ];
    }
}
