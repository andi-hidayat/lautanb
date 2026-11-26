<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
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
            'crewing',
            'ops_crw',
            'procurement',
            'auditor',
            'technical',
            'bod',
        ];

        // 1. Membuat 5 Data Karyawan Statis (Tetap)
        $staticEmployees = [
            [
                'name' => 'Kabal Yudhanegara',
                'email' => 'kabal@gmail.com',
                'company' => 'lbi',
                'role' => 'user',
                'position' => 'president_director',
                'division' => 'bod',
            ],
            [
                'name' => 'Niken Laksmita Devi',
                'email' => 'niken@gmail.com',
                'company' => 'lbi',
                'role' => 'user',
                'position' => 'commissioner',
                'division' => 'bod',
            ],
            [
                'name' => 'Selvi Rahmawati',
                'email' => 'selvi@gmail.com',
                'company' => 'lbi',
                'role' => 'user',
                'position' => 'senior_staff',
                'division' => 'finance',
            ],
            [
                'name' => 'Muhammad Ismail Sholeh',
                'email' => 'sholeh@gmail.com',
                'company' => 'lbi',
                'role' => 'user',
                'position' => 'supervisor',
                'division' => 'hr',
            ],
            [
                'name' => 'Putri Selviani',
                'email' => 'putri@gmail.com',
                'company' => 'lbi',
                'role' => 'user',
                'position' => 'staff',
                'division' => 'secretary',
            ],
            [
                'name' => 'Tubagus Sururi',
                'email' => 'bagus@gmail.com',
                'company' => 'lbi',
                'role' => 'user',
                'position' => 'supervisor',
                'division' => 'accounting',
            ],
            [
                'name' => 'Diana Lexy',
                'email' => 'diana@gmail.com',
                'company' => 'lbi',
                'role' => 'user',
                'position' => 'staff',
                'division' => 'accounting',
            ],
            [
                'name' => 'Andi Hidayat',
                'email' => 'andi@gmail.com',
                'company' => 'lbi',
                'role' => 'user',
                'position' => 'staff',
                'division' => 'it',
            ],
            [
                'name' => 'Akmaludin',
                'email' => 'akmal@gmail.com',
                'company' => 'lba',
                'role' => 'user',
                'position' => 'staff',
                'division' => 'operational',
            ],
        ];

        foreach ($staticEmployees as $employeeData) {
            $employee = Employee::updateOrCreate(
                ['email' => $employeeData['email']],
                $employeeData,
            );

            User::firstOrCreate(
                ['email' => $employeeData['email']],
                [
                    'employee_id' => $employee->id,
                    'password' => 'password',
                    'can_create_pr_lbi' => true,
                    'can_sign_pr_lbi' => true,
                ],
            );
        }

        // 2. Membuat Data Dummy Otomatis (Opsional, misal 10 data)
        for ($i = 1; $i <= 10; $i++) {
            $division = $divisions[array_rand($divisions)];

            Employee::firstOrCreate(
                ['email' => 'emp_'.$i.'@gmail.com'],
                [
                    'name' => 'Employee '.$i,
                    'company' => 'lbi',
                    'role' => 'user',
                    'position' => null,
                    'division' => $division,
                ],
            );
        }
    }
}
