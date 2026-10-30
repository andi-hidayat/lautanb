<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $employees = [
            ['name' => 'Superadmin', 'email' => 'superadmin@gmail.com', 'role' => 'superadmin'],
            ['name' => 'Admin', 'email' => 'admin@gmail.com', 'role' => 'admin'],
            ['name' => 'User', 'email' => 'user@gmail.com', 'role' => 'user'],
        ];

        foreach ($employees as $data) {
            $employee = Employee::create($data);

            User::create([
                'employee_id' => $employee->id,
                'email' => $employee->email,
                'password' => 'password',
            ]);
        }

        $this->call(EmployeeSeeder::class);
        $this->call(PurchaseRequestTypeSeeder::class);
    }
}
