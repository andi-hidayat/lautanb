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
    case PendingFinanceLba = 'pending_finance_lba';
    case PendingFinanceSeniorLba = 'pending_finance_senior_lba';
    case PendingAccountingTax = 'pending_accounting_tax';
    case PendingOpsFleetManager = 'pending_ops_fleet_manager';
    case PendingDirectorOne = 'pending_director_one';
    case PendingDirectorTwo = 'pending_director_two';
    case PendingDirectorMain = 'pending_director_main';

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
            self::PendingFinanceLba => 'Pending Finance LBA',
            self::PendingFinanceSeniorLba => 'Pending Senior Finance',
            self::PendingAccountingTax => 'Pending Accounting & Tax',
            self::PendingOpsFleetManager => 'Pending OPS & Fleet Manager',
            self::PendingDirectorOne => 'Pending Direktur 1',
            self::PendingDirectorTwo => 'Pending Direktur 2',
            self::PendingDirectorMain => 'Pending Direktur Utama',
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
            self::PendingFinanceLba => 'info',
            self::PendingFinanceSeniorLba => 'info',
            self::PendingAccountingTax => 'warning',
            self::PendingOpsFleetManager => 'success',
            self::PendingDirectorOne => 'primary',
            self::PendingDirectorTwo => 'primary',
            self::PendingDirectorMain => 'primary',
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
            self::PendingReviewer => SignaturePosition::Reviewer,
            self::PendingFinanceLba => SignaturePosition::FinanceLba,
            self::PendingFinanceSeniorLba => SignaturePosition::FinanceSeniorLba,
            self::PendingAccountingTax => SignaturePosition::AccountingTax,
            self::PendingOpsFleetManager => SignaturePosition::OpsFleetManager,
            self::PendingDirectorOne => SignaturePosition::DirectorOne,
            self::PendingDirectorTwo => SignaturePosition::DirectorTwo,
            self::PendingDirectorMain => SignaturePosition::DirectorMain,
            default => null,
        };
    }

    /**
     * Get the pending status for a signature position.
     */
    public static function forSignaturePosition(SignaturePosition $position): ?self
    {
        return match ($position) {
            SignaturePosition::Finance => self::PendingFinance,
            SignaturePosition::Accounting => self::PendingAccounting,
            SignaturePosition::AccountingSpv => self::PendingAccountingSpv,
            SignaturePosition::Director => self::PendingDirector,
            SignaturePosition::Reviewer => self::PendingReviewer,
            SignaturePosition::FinanceLba => self::PendingFinanceLba,
            SignaturePosition::FinanceSeniorLba => self::PendingFinanceSeniorLba,
            SignaturePosition::AccountingTax => self::PendingAccountingTax,
            SignaturePosition::OpsFleetManager => self::PendingOpsFleetManager,
            SignaturePosition::DirectorOne => self::PendingDirectorOne,
            SignaturePosition::DirectorTwo => self::PendingDirectorTwo,
            SignaturePosition::DirectorMain => self::PendingDirectorMain,
            default => null,
        };
    }

    /**
     * Get every status that requires an approval signature.
     *
     * @return array<int, self>
     */
    public static function approvalStatuses(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $status): bool => $status->expectedSignaturePosition() !== null,
        ));
    }
}
