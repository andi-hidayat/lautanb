<?php

namespace App\Filament\Resources\PurchasePlans\Pages;

use App\Filament\Resources\PurchasePlans\PurchasePlanResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPurchasePlan extends EditRecord
{
    protected static string $resource = PurchasePlanResource::class;

    // protected function getHeaderActions(): array
    // {
    //     return [
    //         DeleteAction::make(),
    //     ];
    // }
}
