<?php

namespace App\Filament\Resources\PurchasePlans\Pages;

use App\Filament\Resources\PurchasePlans\PurchasePlanResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchasePlan extends ViewRecord
{
    protected static string $resource = PurchasePlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->color('danger'),

            Action::make('back')
                ->label('Back')
                ->url(PurchasePlanResource::getUrl('index')),
        ];
    }
}
