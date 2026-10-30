<?php

namespace App\Actions\PurchaseRequests;

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

        if (blank($division)) {
            throw ValidationException::withMessages([
                'data.title' => 'Akun Anda belum memiliki divisi. Hubungi admin untuk memperbarui data karyawan.',
            ]);
        }

        $setting = PurchaseRequestDocumentSetting::query()
            ->where('division', $division)
            ->lockForUpdate()
            ->first();

        if (! $setting) {
            throw ValidationException::withMessages([
                'data.title' => 'Pengaturan dokumen untuk divisi Anda belum tersedia. Hubungi admin.',
            ]);
        }

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
}
