<?php

namespace App\Actions\PurchaseRequests;

use App\Models\PurchaseRequestDocumentSetting;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class IssuePurchaseRequestDocument
{
    /**
     * Reserve a division-specific number and return the metadata snapshot.
     *
     * @return array{
     *     requested_by: int|string,
     *     company: string,
     *     division: string,
     *     document_number: string,
     *     revision_number: string,
     *     issue_date: string,
     *     effective_date: string,
     *     purchase_request_number: string,
     *     approval_steps: array<int, array{stage: string, approver_type: string, position: ?string, division: ?string, signer_name: ?string}>
     * }
     *
     * @throws ValidationException
     */
    public function forCreator(User $creator, string $company): array
    {
        $division = $creator->division;
        $issueDate = today();
        $year = $issueDate->year;

        if (blank($company) || ! in_array($company, ['lbi', 'lba'], true)) {
            throw ValidationException::withMessages([
                'data.company' => 'Perusahaan yang dipilih tidak valid.',
            ]);
        }

        if (! $creator->canCreatePurchaseRequestsFor($company)) {
            throw new AuthorizationException('Anda tidak diizinkan membuat PR untuk perusahaan ini.');
        }

        if (blank($division)) {
            throw ValidationException::withMessages([
                'data.title' => 'Akun Anda belum memiliki divisi. Hubungi admin untuk memperbarui data karyawan.',
            ]);
        }

        $setting = PurchaseRequestDocumentSetting::query()
            ->where('company', $company)
            ->where('division', $division)
            ->lockForUpdate()
            ->first();

        if (! $setting) {
            throw ValidationException::withMessages([
                'data.title' => 'Pengaturan dokumen untuk divisi Anda belum tersedia. Hubungi admin.',
            ]);
        }

        $approvalSteps = array_values($setting->approvalSteps());
        $approvalStepsError = PurchaseRequestDocumentSetting::approvalStepsValidationMessage($approvalSteps);

        if ($approvalStepsError !== null) {
            throw ValidationException::withMessages([
                'data.title' => $approvalStepsError,
            ]);
        }

        $sequence = $setting->sequence_year === $year
            ? $setting->next_number
            : 1;
        $purchaseRequestNumber = $setting->formatPurchaseRequestNumber($sequence, $year);

        $setting->update([
            'sequence_year' => $year,
            'next_number' => $sequence + 1,
        ]);

        return [
            'requested_by' => $creator->getKey(),
            'company' => $company,
            'division' => $division,
            'document_number' => $setting->document_number,
            'revision_number' => $setting->revision_number,
            'issue_date' => $issueDate->toDateString(),
            'effective_date' => $setting->effective_date?->toDateString() ?? $issueDate->toDateString(),
            'purchase_request_number' => $purchaseRequestNumber,
            'approval_steps' => $approvalSteps,
        ];
    }
}
