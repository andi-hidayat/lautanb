<?php

namespace App\Filament\Resources\ProductComparisons\Pages;

use App\Filament\Resources\ProductComparisons\ProductComparisonResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewProductComparison extends ViewRecord
{
    protected static string $resource = ProductComparisonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
