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
                    ]),
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
                        'director' => 'Director',
                        'staff' => 'Staff',
                        'senior' => 'Senior',
                        'Non-Staff' => 'Non-Staff',
                        'supervisor' => 'Supervisor',
                        'manager' => 'Manager',
                        'senior_manager' => 'Senior Manager',
                        'general_manager' => 'General Manager',
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
                        'auditor' => 'Auditor',
                        'technical' => 'Technical',
                        'bod' => 'BOD',
                    ]),
            ]);
    }
}
