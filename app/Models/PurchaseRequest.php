<?php

namespace App\Models;

use App\Enums\PurchaseRequestStatus;
use App\Enums\SignaturePosition;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'title',
    'needed_at',
    'requested_by',
    'description',
    'purchase_tipe',
    'pay_to',
    'bank_merchant_name',
    'account_number',
    'total_cost',
    'status',
    'user_signature',
    'finance_signature',
    'accounting_signature',
    'accounting_spv_signature',
    'reviewer_signature',
    'director_signature',
    'rejection_reason',
    'rejected_by',
    'rejected_at',
    'rejected_at_stage',
    'division',
    'document_number',
    'revision_number',
    'issue_date',
    'effective_date',
    'purchase_request_number',
])]
class PurchaseRequest extends Model
{
    /**
     * Get the user who requested the purchase.
     *
     * @return BelongsTo<User, $this>
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the user who rejected the request.
     *
     * @return BelongsTo<User, $this>
     */
    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Get the items in the purchase request.
     *
     * @return HasMany<PurchaseRequestItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequestItem::class);
    }

    /**
     * Sum the total cost of the given raw item states.
     *
     * @param  array<int|string, array<string, mixed>>|null  $items
     */
    public static function calculateItemsTotalCost(?array $items): float
    {
        return (float) collect($items ?? [])
            ->sum(fn (array $item): float => PurchaseRequestItem::calculateTotalCost($item));
    }

    /**
     * Format an amount as Indonesian rupiah without decimals.
     */
    public static function formatRupiah(float $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }

    /**
     * Determine whether the given position has signed this request.
     */
    public function hasSignature(SignaturePosition $position): bool
    {
        return filled($this->getAttribute($position->signatureField()));
    }

    /**
     * Determine if a user can approve the request at its current stage.
     */
    public function canBeApprovedBy(?User $user): bool
    {
        if ($user === null || $this->status === PurchaseRequestStatus::Approved || $this->status === PurchaseRequestStatus::Rejected) {
            return false;
        }

        $position = $this->status->expectedSignaturePosition();

        if ($position === null) {
            return false;
        }

        return $this->userMatchesPosition($user, $position) || (bool) $user->isStaff();
    }

    /**
     * Determine if a user can reject the request at its current stage.
     */
    public function canBeRejectedBy(?User $user): bool
    {
        return $this->canBeApprovedBy($user);
    }

    /**
     * Determine if a user can resubmit this rejected request.
     */
    public function canBeResubmittedBy(?User $user): bool
    {
        if ($user === null || $this->status !== PurchaseRequestStatus::Rejected) {
            return false;
        }

        return (int) $user->id === (int) $this->requested_by || (bool) $user->isStaff();
    }

    /**
     * Check whether a user matches the required signature position.
     */
    public function userMatchesPosition(User $user, SignaturePosition $position): bool
    {
        if ($user->position === $position->value) {
            return true;
        }

        return match ($position) {
            SignaturePosition::Finance => $user->position === 'senior' && $user->division === 'finance',
            SignaturePosition::AccountingSpv => $user->position === 'supervisor' && $user->division === 'accounting',
            SignaturePosition::Reviewer => $user->position === 'manager' && $user->division === 'bod',
            SignaturePosition::Director => $user->position === 'director' && $user->division === 'bod',
            SignaturePosition::Accounting => $user->position === 'staff' && $user->division === 'accounting',
            SignaturePosition::User => (int) $user->id === (int) $this->requested_by,
        };
    }

    /**
     * Approve the request by the current user with the given signature.
     */
    public function approve(User $user, string $signature): void
    {
        $currentPosition = $this->status->expectedSignaturePosition();

        if ($currentPosition === null) {
            return;
        }

        $signatureField = $currentPosition->signatureField();
        $nextStatus = $this->status->nextStatus() ?? PurchaseRequestStatus::Approved;

        $this->update([
            $signatureField => $signature,
            'status' => $nextStatus,
            'rejection_reason' => null,
            'rejected_by' => null,
            'rejected_at' => null,
            'rejected_at_stage' => null,
        ]);
    }

    /**
     * Reject the request with a mandatory reason.
     */
    public function reject(User $user, string $reason): void
    {
        $currentPosition = $this->status->expectedSignaturePosition();

        $this->update([
            'status' => PurchaseRequestStatus::Rejected,
            'rejection_reason' => $reason,
            'rejected_by' => $user->id,
            'rejected_at' => now(),
            'rejected_at_stage' => $currentPosition?->value ?? 'approval',
        ]);
    }

    /**
     * Resubmit the rejected request back to Senior Finance.
     */
    public function resubmit(): void
    {
        $this->update([
            'status' => PurchaseRequestStatus::PendingFinance,
            'finance_signature' => null,
            'accounting_signature' => null,
            'accounting_spv_signature' => null,
            'reviewer_signature' => null,
            'director_signature' => null,
            'rejection_reason' => null,
            'rejected_by' => null,
            'rejected_at' => null,
            'rejected_at_stage' => null,
        ]);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PurchaseRequestStatus::class,
            'needed_at' => 'date',
            'issue_date' => 'date',
            'effective_date' => 'date',
            'total_cost' => 'decimal:2',
            'rejected_at' => 'datetime',
        ];
    }
}
