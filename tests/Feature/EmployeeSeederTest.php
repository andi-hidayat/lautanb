<?php

namespace Tests\Feature;

use Database\Seeders\EmployeeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_seed_can_be_repeated_without_duplicate_employees(): void
    {
        $seeder = new EmployeeSeeder;

        $seeder->run();
        $seeder->run();

        $this->assertDatabaseCount('employees', 19);
        $this->assertDatabaseHas('employees', [
            'email' => 'selvi@gmail.com',
            'company' => 'lbi',
            'position' => 'senior_staff',
            'division' => 'finance',
        ]);
        $this->assertDatabaseHas('employees', [
            'email' => 'niken@gmail.com',
            'position' => 'commissioner',
            'division' => 'bod',
        ]);
        $this->assertDatabaseHas('employees', [
            'email' => 'sholeh@gmail.com',
            'division' => 'hrga',
        ]);
        $this->assertDatabaseHas('employees', [
            'email' => 'akmal@gmail.com',
            'division' => 'ops_crw',
        ]);
    }
}
