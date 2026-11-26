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
            ['name' => 'Superadmin', 'email' => 'superadmin@gmail.com', 'company' => 'lbi', 'role' => 'superadmin', 'division' => 'it'],
            ['name' => 'Admin', 'email' => 'admin@gmail.com', 'company' => 'lbi', 'role' => 'admin', 'division' => 'it'],
            ['name' => 'User', 'email' => 'user@gmail.com', 'company' => 'lbi', 'role' => 'user', 'division' => 'it'],
        ];

        foreach ($employees as $data) {
            $employee = Employee::create($data);
            $isStaff = in_array($data['role'], ['admin', 'superadmin'], true);

            User::create([
                'employee_id' => $employee->id,
                'email' => $employee->email,
                'password' => 'password',
                'can_create_pr_lbi' => true,
                'can_create_pr_lba' => $isStaff,
                'can_sign_pr_lbi' => true,
                'can_sign_pr_lba' => $isStaff,
            ]);
        }

        $this->call(EmployeeSeeder::class);
        $this->call(PurchaseRequestTypeSeeder::class);
    }
}
