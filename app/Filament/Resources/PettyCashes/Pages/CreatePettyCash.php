<?php

namespace App\Filament\Resources\PettyCashes\Pages;

use App\Filament\Resources\PettyCashes\PettyCashResource;
use App\Filament\Resources\PettyCashes\Schemas\PettyCashForm;
use App\Models\PettyCash;
use Filament\Resources\Pages\CreateRecord;

class CreatePettyCash extends CreateRecord
{
    protected static string $resource = PettyCashResource::class;

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return PettyCashForm::prepareRecordData($data, PettyCash::nextTransactionNumber());
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
