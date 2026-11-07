<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PurchaseRequestStatus: string implements HasColor, HasLabel
{
    case PendingFinance = 'pending_finance';
    case PendingAccounting = 'pending_accounting';
    case PendingAccountingSpv = 'pending_accounting_spv';
    case PendingDirector = 'pending_director';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case PendingReviewer = 'pending_reviewer';

    public function getLabel(): string
    {
        return match ($this) {
            self::PendingFinance => 'Pending Senior Finance',
            self::PendingAccounting => 'Pending Accounting (Staff)',
            self::PendingAccountingSpv => 'Pending Accounting SPV',
            self::PendingDirector => 'Pending Direktur',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak / Perlu Revisi',
            self::PendingReviewer => 'Pending Reviewer',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PendingFinance => 'info',
            self::PendingAccounting => 'warning',
            self::PendingAccountingSpv => 'success',
            self::PendingDirector => 'primary',
            self::Approved => 'success',
            self::Rejected => 'danger',
            self::PendingReviewer => 'gray',
        };
    }

    /**
     * Get the signature position expected to act on this status.
     */
    public function expectedSignaturePosition(): ?SignaturePosition
    {
        return match ($this) {
            self::PendingFinance => SignaturePosition::Finance,
            self::PendingAccounting => SignaturePosition::Accounting,
            self::PendingAccountingSpv => SignaturePosition::AccountingSpv,
            self::PendingDirector => SignaturePosition::Director,
            self::PendingReviewer => SignaturePosition::AccountingSpv,
            default => null,
        };
    }

    /**
     * Get the next pending status after approval.
     */
    public function nextStatus(): ?self
    {
        return match ($this) {
            self::PendingFinance => self::PendingAccounting,
            self::PendingAccounting => self::PendingAccountingSpv,
            self::PendingAccountingSpv => self::PendingDirector,
            self::PendingDirector => self::Approved,
            self::PendingReviewer => self::PendingDirector,
            default => null,
        };
    }
}
