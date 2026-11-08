<?php

namespace App\Models;

use App\Enums\PurchaseRequestStatus;
use App\Enums\SignaturePosition;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
    'approval_round',
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
     * Get the approval and rejection history for this request.
     *
     * @return HasMany<PurchaseRequestApprovalHistory, $this>
     */
    public function approvalHistories(): HasMany
    {
        return $this->hasMany(PurchaseRequestApprovalHistory::class)
            ->with('actor')
            ->orderBy('id');
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
     * Determine whether the request has completed approval and every required signature.
     */
    public function isFullyApprovedAndSigned(): bool
    {
        return $this->status === PurchaseRequestStatus::Approved
            && collect(SignaturePosition::signingCases())
                ->every(fn (SignaturePosition $position): bool => $this->hasSignature($position));
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

        $previousPosition = $position->previous();

        if ($previousPosition !== null && ! $this->hasSignature($previousPosition)) {
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

        return (int) $user->id === (int) $this->requested_by;
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
        if (blank($signature)) {
            throw ValidationException::withMessages([
                'signature' => 'Tanda tangan wajib diisi.',
            ]);
        }

        DB::transaction(function () use ($user, $signature): void {
            $request = self::query()->lockForUpdate()->findOrFail($this->getKey());

            if (! $request->canBeApprovedBy($user)) {
                throw new AuthorizationException('Anda tidak berwenang menyetujui permintaan ini pada tahap saat ini.');
            }

            $position = $request->status?->expectedSignaturePosition();

            if ($position === null) {
                throw new AuthorizationException('Permintaan ini tidak memiliki tahap persetujuan aktif.');
            }

            $nextStatus = $request->status->nextStatus() ?? PurchaseRequestStatus::Approved;
            $request->approvalHistories()->create([
                'round' => $request->approval_round,
                'stage' => $position->value,
                'action' => 'approved',
                'actor_id' => $user->id,
                'signature' => $signature,
                'acted_at' => now(),
            ]);

            $request->update([
                $position->signatureField() => $signature,
                'status' => $nextStatus,
                'rejection_reason' => null,
                'rejected_by' => null,
                'rejected_at' => null,
                'rejected_at_stage' => null,
            ]);
        });

        $this->refresh();
    }

    /**
     * Reject the request with a mandatory reason.
     */
    public function reject(User $user, string $reason): void
    {
        if (blank($reason)) {
            throw ValidationException::withMessages([
                'rejection_reason' => 'Alasan penolakan wajib diisi.',
            ]);
        }

        DB::transaction(function () use ($user, $reason): void {
            $request = self::query()->lockForUpdate()->findOrFail($this->getKey());

            if (! $request->canBeRejectedBy($user)) {
                throw new AuthorizationException('Anda tidak berwenang menolak permintaan ini pada tahap saat ini.');
            }

            $position = $request->status?->expectedSignaturePosition();

            if ($position === null) {
                throw new AuthorizationException('Permintaan ini tidak memiliki tahap persetujuan aktif.');
            }

            $request->archiveCurrentApprovals();
            $rejectedAt = now();
            $request->approvalHistories()->create([
                'round' => $request->approval_round,
                'stage' => $position->value,
                'action' => 'rejected',
                'actor_id' => $user->id,
                'comments' => $reason,
                'acted_at' => $rejectedAt,
            ]);

            $request->update([
                'status' => PurchaseRequestStatus::Rejected,
                'rejection_reason' => $reason,
                'rejected_by' => $user->id,
                'rejected_at' => $rejectedAt,
                'rejected_at_stage' => $position->value,
            ]);
        });

        $this->refresh();
    }

    /**
     * Resubmit the rejected request back to Senior Finance.
     */
    public function resubmit(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $request = self::query()->lockForUpdate()->findOrFail($this->getKey());

            if (! $request->canBeResubmittedBy($user)) {
                throw new AuthorizationException('Anda tidak berwenang mengajukan ulang permintaan ini.');
            }

            $request->archiveCurrentApprovals();
            $request->archiveCurrentRejection();
            $request->approvalHistories()->create([
                'round' => $request->approval_round,
                'stage' => SignaturePosition::User->value,
                'action' => 'resubmitted',
                'actor_id' => $user->id,
                'acted_at' => now(),
            ]);

            $request->update([
                'status' => PurchaseRequestStatus::PendingFinance,
                'approval_round' => $request->approval_round + 1,
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
        });

        $this->refresh();
    }

    private function archiveCurrentApprovals(): void
    {
        foreach (SignaturePosition::signingCases() as $position) {
            if ($position === SignaturePosition::User) {
                continue;
            }

            $signature = $this->getAttribute($position->signatureField());

            if (blank($signature) || $this->approvalHistories()
                ->where('round', $this->approval_round)
                ->where('stage', $position->value)
                ->where('action', 'approved')
                ->exists()) {
                continue;
            }

            $this->approvalHistories()->create([
                'round' => $this->approval_round,
                'stage' => $position->value,
                'action' => 'approved',
                'signature' => $signature,
                'acted_at' => $this->updated_at ?? now(),
            ]);
        }
    }

    private function archiveCurrentRejection(): void
    {
        if (blank($this->rejection_reason) || $this->approvalHistories()
            ->where('round', $this->approval_round)
            ->where('action', 'rejected')
            ->exists()) {
            return;
        }

        $this->approvalHistories()->create([
            'round' => $this->approval_round,
            'stage' => $this->rejected_at_stage ?? 'approval',
            'action' => 'rejected',
            'actor_id' => $this->rejected_by,
            'comments' => $this->rejection_reason,
            'acted_at' => $this->rejected_at ?? now(),
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
            'approval_round' => 'integer',
            'needed_at' => 'date',
            'issue_date' => 'date',
            'effective_date' => 'date',
            'total_cost' => 'decimal:2',
            'rejected_at' => 'datetime',
        ];
    }
}
