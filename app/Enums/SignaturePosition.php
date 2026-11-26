<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * The available approval stages for purchase requests.
 */
enum SignaturePosition: string implements HasColor, HasLabel
{
    case User = 'user';
    case Finance = 'finance';
    case Accounting = 'accounting';
    case AccountingSpv = 'accounting_spv';
    case Director = 'director';
    case Reviewer = 'reviewer';
    case FinanceLba = 'finance_lba';
    case FinanceSeniorLba = 'finance_senior_lba';
    case AccountingTax = 'accounting_tax';
    case OpsFleetManager = 'ops_fleet_manager';
    case DirectorOne = 'director1';
    case DirectorTwo = 'director2';
    case DirectorMain = 'director_main';

    /**
     * Get all available approval stages.
     *
     * @return array<int, self>
     */
    public static function allSigningCases(): array
    {
        return self::cases();
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::User => 'Submitted by',
            self::Finance => 'Senior Finance',
            self::Accounting => 'Accounting',
            self::AccountingSpv => 'Accounting SPV',
            self::Director => 'Director',
            self::Reviewer => 'Reviewer',
            self::FinanceLba => 'Finance LBA',
            self::FinanceSeniorLba => 'Senior Finance',
            self::AccountingTax => 'Accounting & Tax',
            self::OpsFleetManager => 'OPS & Fleet Manager',
            self::DirectorOne => 'Direktur 1',
            self::DirectorTwo => 'Direktur 2',
            self::DirectorMain => 'Direktur Utama',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::User => 'gray',
            self::Finance => 'info',
            self::Accounting => 'warning',
            self::AccountingSpv => 'success',
            self::Director => 'primary',
            self::Reviewer => 'gray',
            self::FinanceLba => 'info',
            self::FinanceSeniorLba => 'info',
            self::AccountingTax => 'warning',
            self::OpsFleetManager => 'success',
            self::DirectorOne => 'primary',
            self::DirectorTwo => 'primary',
            self::DirectorMain => 'primary',
        };
    }

    /**
     * Get the purchase request column that stores this position's signature.
     */
    public function signatureField(): string
    {
        return $this->value.'_signature';
    }

    /**
     * Get the position options for a select field.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::allSigningCases() as $position) {
            $options[$position->value] = $position->getLabel();
        }

        return $options;
    }
}
