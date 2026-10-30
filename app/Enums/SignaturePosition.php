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

    public function getLabel(): string
    {
        return match ($this) {
            self::User => 'Submitted by',
            self::Finance => 'Senior Finance',
            self::Accounting => 'Accounting',
            self::AccountingSpv => 'Accounting SPV',
            self::Director => 'Director',
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
        };
    }

    /**
     * Get the position that must sign before this one.
     */
    public function previous(): ?self
    {
        $index = array_search($this, self::cases(), strict: true);

        return $index > 0 ? self::cases()[$index - 1] : null;
    }

    /**
     * Get the position options for a select field.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $position) {
            $options[$position->value] = $position->getLabel();
        }

        return $options;
    }
}
