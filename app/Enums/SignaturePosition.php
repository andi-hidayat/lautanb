<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * The positions that sign a purchase request, in signing order.
 */
enum SignaturePosition: string implements HasColor, HasLabel
{
    case User = 'user';
    case Finance = 'finance';
    case Accounting = 'accounting';
    case AccountingSpv = 'accounting_spv';
    case Director = 'director';
    case Reviewer = 'reviewer';

    /**
     * Get the active signing sequence cases in execution order.
     *
     * @return array<int, self>
     */
    public static function signingCases(): array
    {
        return [
            self::User,
            self::Finance,
            self::Accounting,
            self::AccountingSpv,
            self::Director,
        ];
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
        };
    }

    /**
     * Get the purchase request column that stores this position's signature.
     */
    public function signatureField(): string
    {
        return match ($this) {
            self::User => 'user_signature',
            self::Finance => 'finance_signature',
            self::Accounting => 'accounting_signature',
            self::AccountingSpv => 'accounting_spv_signature',
            self::Director => 'director_signature',
            self::Reviewer => 'reviewer_signature',
        };
    }

    /**
     * Get the position that must sign before this one.
     */
    public function previous(): ?self
    {
        return match ($this) {
            self::User => null,
            self::Finance => self::User,
            self::Accounting => self::Finance,
            self::AccountingSpv => self::Accounting,
            self::Director => self::AccountingSpv,
            self::Reviewer => self::AccountingSpv,
        };
    }

    /**
     * Get the position that signs after this one.
     */
    public function next(): ?self
    {
        return match ($this) {
            self::User => self::Finance,
            self::Finance => self::Accounting,
            self::Accounting => self::AccountingSpv,
            self::AccountingSpv => self::Director,
            self::Director => null,
            self::Reviewer => self::Director,
        };
    }

    /**
     * Get the position options for a select field.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::signingCases() as $position) {
            $options[$position->value] = $position->getLabel();
        }

        return $options;
    }
}
