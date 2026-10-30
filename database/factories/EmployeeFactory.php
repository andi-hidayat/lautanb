<?php

namespace Database\Factories;

use App\Enums\SignaturePosition;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'role' => 'user',
            'position' => null,
            'division' => null,
        ];
    }

    /**
     * Set the employee's signature position.
     */
    public function position(SignaturePosition $position): static
    {
        return $this->state(fn (): array => [
            'position' => $position->value,
        ]);
    }
}
