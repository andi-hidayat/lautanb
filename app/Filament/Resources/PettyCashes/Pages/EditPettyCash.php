<?php

namespace App\Filament\Resources\PettyCashes\Pages;

use App\Filament\Resources\PettyCashes\PettyCashResource;
use App\Filament\Resources\PettyCashes\Schemas\PettyCashForm;
use App\Models\PettyCash;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPettyCash extends EditRecord
{
    protected static string $resource = PettyCashResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();
        $data['opening_balance'] = PettyCash::balanceBefore((int) $record->number);

        return PettyCashForm::prepareFormData($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return PettyCashForm::prepareRecordData($data);
    }

    protected function afterSave(): void
    {
        PettyCash::recalculateRunningBalances();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
