<?php

namespace App\Filament\Resources\PurchasePlans\Pages;

use App\Filament\Resources\PurchasePlans\PurchasePlanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPurchasePlans extends ListRecords
{
    protected static string $resource = PurchasePlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
