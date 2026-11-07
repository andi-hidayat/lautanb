<?php

namespace App\Filament\Resources\PurchaseRequests\Pages;

use App\Enums\PurchaseRequestStatus;
use App\Filament\Resources\PurchaseRequests\Actions\PurchaseRequestApprovalActions;
use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Filament\Resources\PurchaseRequests\Schemas\PurchaseRequestForm;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseRequest extends EditRecord
{
    protected static string $resource = PurchaseRequestResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->getRecord();

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
            $record->resubmit();

            Notification::make()
                ->title('Permintaan Diperbarui & Diajukan Ulang')
                ->body('Perubahan Anda telah disimpan dan permintaan otomatis diajukan ulang ke Senior Finance.')
                ->success()
                ->send();

            return;
        }

        $currentPosition = $record->status?->expectedSignaturePosition();

        if ($currentPosition !== null && filled($record->getAttribute($currentPosition->signatureField()))) {
            $nextStatus = $record->status->nextStatus();

            if ($nextStatus) {
                $record->update(['status' => $nextStatus]);
            }
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            PurchaseRequestApprovalActions::makeApproveAction(),
            PurchaseRequestApprovalActions::makeRejectAction(),
            PurchaseRequestApprovalActions::makeResubmitAction(),
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
