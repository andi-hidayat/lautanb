<?php

namespace App\Filament\Resources\PurchaseRequests\Pages;

use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseRequest extends EditRecord
{
    protected static string $resource = PurchaseRequestResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        foreach ([
            'requested_by',
            'division',
            'document_number',
            'revision_number',
            'issue_date',
            'effective_date',
            'purchase_request_number',
        ] as $attribute) {
            $data[$attribute] = $this->getRecord()->getAttribute($attribute);
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
