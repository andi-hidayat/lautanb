<?php

namespace App\Filament\Resources\PurchaseRequestDocumentSettings\Pages;

use App\Filament\Resources\PurchaseRequestDocumentSettings\PurchaseRequestDocumentSettingResource;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseRequestDocumentSetting extends EditRecord
{
    protected static string $resource = PurchaseRequestDocumentSettingResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
