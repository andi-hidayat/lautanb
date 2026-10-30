<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $divisions = [
            'finance',
            'accounting',
            'hr',
            'ga',
            'hrga',
            'it',
            'legal',
            'marketing',
            'sales',
            'secretary',
            'logistic',
            'hse',
            'operational',
            'technical',
            'auditor',
        ];

        // 1. Membuat 5 Data Karyawan Statis (Tetap)
        $staticEmployees = [
            [
                'name' => 'Kabal Yudhanegara',
                'email' => 'kabal@gmail.com',
                'role' => 'user',
                'position' => 'director',
                'division' => 'bod',
            ],
            [
                'name' => 'Niken Laksmita Devi',
                'email' => 'niken@gmail.com',
                'role' => 'user',
                'position' => 'manager',
                'division' => 'bod',
            ],
            [
                'name' => 'Selvi Rahmawati',
                'email' => 'selvi@gmail.com',
                'role' => 'user',
                'position' => 'senior',
                'division' => 'finance',
            ],
            [
                'name' => 'Muhammad Ismail Sholeh',
                'email' => 'sholeh@gmail.com',
                'role' => 'user',
                'position' => 'supervisor',
                'division' => 'hrga',
            ],
            [
                'name' => 'Putri Selviani',
                'email' => 'putri@gmail.com',
                'role' => 'user',
                'position' => 'staff',
                'division' => 'secretary',
            ],
            [
                'name' => 'Tubagus Sururi',
                'email' => 'bagus@gmail.com',
                'role' => 'user',
                'position' => 'supervisor',
                'division' => 'accounting',
            ],
            [
                'name' => 'Diana Lexy',
                'email' => 'diana@gmail.com',
                'role' => 'user',
                'position' => 'staff',
                'division' => 'accounting',
            ],
            [
                'name' => 'Andi Hidayat',
                'email' => 'andi@gmail.com',
                'role' => 'user',
                'position' => 'staff',
                'division' => 'it',
            ],
        ];

        foreach ($staticEmployees as $employee) {
            Employee::create($employee);
        }

        // 2. Membuat Data Dummy Otomatis (Opsional, misal 10 data)
        for ($i = 1; $i <= 10; $i++) {
            $division = $divisions[array_rand($divisions)];

            Employee::create([
                'name' => 'Employee '.$i,
                'email' => 'emp_'.$i.'@gmail.com',
                'role' => 'user',
                'position' => null,
                'division' => $division,
            ]);
        }
    }
}
