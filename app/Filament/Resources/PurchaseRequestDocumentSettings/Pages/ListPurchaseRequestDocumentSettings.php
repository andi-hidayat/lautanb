<?php

namespace App\Filament\Resources\PurchaseRequestDocumentSettings\Pages;

use App\Filament\Resources\PurchaseRequestDocumentSettings\PurchaseRequestDocumentSettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseRequestDocumentSettings extends ListRecords
{
    protected static string $resource = PurchaseRequestDocumentSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->createAnother(false),
        ];
    }
}
