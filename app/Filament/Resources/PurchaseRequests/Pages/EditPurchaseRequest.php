<?php

namespace App\Filament\Resources\PurchaseRequests\Pages;

use App\Enums\PurchaseRequestStatus;
use App\Enums\SignaturePosition;
use App\Filament\Resources\PurchaseRequests\Actions\PurchaseRequestApprovalActions;
use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Filament\Resources\PurchaseRequests\Schemas\PurchaseRequestForm;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;

class EditPurchaseRequest extends EditRecord
{
    protected static string $resource = PurchaseRequestResource::class;

    private ?string $submittedApprovalSignature = null;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->getRecord();
        $user = Auth::user();

        foreach (SignaturePosition::signingCases() as $position) {
            $field = $position->signatureField();

            if (! array_key_exists($field, $data)) {
                continue;
            }

            $existingSignature = $record->getAttribute($field);
            $submittedSignature = $data[$field];

            if ($submittedSignature === $existingSignature || blank($submittedSignature)) {
                continue;
            }

            if (filled($existingSignature)) {
                throw new AuthorizationException('Tanda tangan yang sudah tersimpan tidak dapat diubah.');
            }

            if ($position === SignaturePosition::User) {
                if (! $user instanceof User || (! $record->userMatchesPosition($user, $position) && ! $user->isStaff())) {
                    throw new AuthorizationException('Hanya pemohon yang dapat menambahkan tanda tangan pemohon.');
                }

                continue;
            }

            if ($position !== $record->status?->expectedSignaturePosition() || ! $record->canBeApprovedBy($user)) {
                throw new AuthorizationException('Tanda tangan hanya dapat diberikan pada tahap persetujuan yang sedang aktif.');
            }

            $this->submittedApprovalSignature = (string) $submittedSignature;
        }

        foreach ([
            'requested_by',
            'division',
            'document_number',
            'revision_number',
            'issue_date',
            'effective_date',
            'purchase_request_number',
        ] as $attribute) {
            $data[$attribute] = $record->getAttribute($attribute);
        }

        if (! PurchaseRequestForm::canEditRequesterFields($record)) {
            foreach ([
                'title',
                'description',
                'purchase_tipe',
                'pay_to',
                'bank_merchant_name',
                'account_number',
            ] as $field) {
                $data[$field] = $record->getAttribute($field);
            }
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();

        if ($record->status === PurchaseRequestStatus::Rejected) {
            $user = Auth::user();

            if (! $user instanceof User) {
                throw new AuthorizationException('Pengguna harus masuk untuk mengajukan ulang permintaan.');
            }

            $record->resubmit($user);

            Notification::make()
                ->title('Permintaan Diperbarui & Diajukan Ulang')
                ->body('Perubahan Anda telah disimpan dan permintaan otomatis diajukan ulang ke Senior Finance.')
                ->success()
                ->send();

            return;
        }

        if ($this->submittedApprovalSignature !== null) {
            $user = Auth::user();

            if (! $user instanceof User) {
                throw new AuthorizationException('Pengguna harus masuk untuk menyetujui permintaan.');
            }

            $record->approve($user, $this->submittedApprovalSignature);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            PurchaseRequestApprovalActions::makeRejectAction(),
            PurchaseRequestApprovalActions::makeResubmitAction(),
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
