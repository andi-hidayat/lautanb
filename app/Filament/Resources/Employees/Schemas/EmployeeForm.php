<?php

namespace App\Filament\Resources\Employees\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),
                Select::make('company')
                    ->options([
                        'lba' => 'PT Lautan Berkah Amanah',
                        'lbi' => 'PT Lautan Berkah Internasional',
                    ])
                    ->default('lbi')
                    ->required(),
                Select::make('role')
                    ->options([
                        'user' => 'User',
                        'admin' => 'Admin',
                        'superadmin' => 'Superadmin',
                    ])
                    ->default('user')
                    ->required(),
                Select::make('position')
                    ->label('Position')
                    ->options([
                        'Non-Staff' => 'Non-Staff',
                        'staff' => 'Staff',
                        'senior_staff' => 'Senior Staff',
                        'supervisor' => 'Supervisor',
                        'assistant_manager' => 'Assistant Manager',
                        'manager' => 'Manager',
                        'senior_manager' => 'Senior Manager',
                        'general_manager' => 'General Manager',
                        'vice_president' => 'Vice President',
                        'director' => 'Director',
                        'president_director' => 'President Director',
                        'commissioner' => 'Commissioner',
                    ])
                    ->default(null),
                Select::make('division')
                    ->options([
                        'finance' => 'Finance',
                        'accounting' => 'Accounting',
                        'hr' => 'HR',
                        'ga' => 'GA',
                        'hrga' => 'HR and GA',
                        'it' => 'IT',
                        'legal' => 'Legal',
                        'marketing' => 'Marketing',
                        'sales' => 'Sales',
                        'secretary' => 'Secretary',
                        'logistic' => 'Logistic',
                        'hse' => 'HSE',
                        'operational' => 'Operational',
                        'crewing' => 'Crewing',
                        'ops_crw' => 'Operations and Crewing',
                        'procurement' => 'Procurement',
                        'auditor' => 'Auditor',
                        'technical' => 'Technical',
                        'bod' => 'BOD',
                    ]),
            ]);
    }
}
