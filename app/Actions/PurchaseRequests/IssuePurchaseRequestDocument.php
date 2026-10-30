<?php

namespace App\Actions\PurchaseRequests;

use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestDocumentSetting;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class IssuePurchaseRequestDocument
{
    /**
     * Reserve a division-specific number and return the metadata snapshot.
     *
     * @return array<string, int|string>
     *
     * @throws ValidationException
     */
    public function forCreator(User $creator): array
    {
        $division = $creator->division;
        $issueDate = today();
        $year = $issueDate->year;

        $setting = $division
            ? PurchaseRequestDocumentSetting::query()
                ->where('division', $division)
                ->lockForUpdate()
                ->first()
            : null;

        if ($setting) {
            $sequence = $setting->sequence_year === $year
                ? $setting->next_number
                : 1;
            $purchaseRequestNumber = $setting->number_prefix
                .'/'.$year
                .'/'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);

            $setting->update([
                'sequence_year' => $year,
                'next_number' => $sequence + 1,
            ]);

            return [
                'requested_by' => $creator->getKey(),
                'division' => $division,
                'document_number' => $setting->document_number,
                'revision_number' => $setting->revision_number,
                'issue_date' => $issueDate->toDateString(),
                'effective_date' => $setting->effective_date?->toDateString() ?? $issueDate->toDateString(),
                'purchase_request_number' => $purchaseRequestNumber,
            ];
        }

        // Fallback jika pengaturan dokumen untuk divisi belum tersedia
        $prefix = 'PR/'.strtoupper($division ?: 'GEN');
        $count = PurchaseRequest::query()
            ->whereYear('created_at', $year)
            ->count();
        $purchaseRequestNumber = $prefix.'/'.$year.'/'.str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);

        return [
            'requested_by' => $creator->getKey(),
            'division' => $division,
            'document_number' => null,
            'revision_number' => null,
            'issue_date' => $issueDate->toDateString(),
            'effective_date' => $issueDate->toDateString(),
            'purchase_request_number' => $purchaseRequestNumber,
        ];
    }
}
