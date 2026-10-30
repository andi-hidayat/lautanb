<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'division',
    'document_number',
    'revision_number',
    'effective_date',
    'number_prefix',
    'next_number',
    'sequence_year',
])]
class PurchaseRequestDocumentSetting extends Model
{
    public const array DIVISIONS = [
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
        'technical' => 'Technical',
        'auditor' => 'Auditor',
        'bod' => 'BOD',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'next_number' => 'integer',
            'sequence_year' => 'integer',
        ];
    }
}
