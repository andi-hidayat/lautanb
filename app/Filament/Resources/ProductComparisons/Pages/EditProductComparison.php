<?php

namespace App\Filament\Resources\ProductComparisons\Pages;

use App\Filament\Resources\ProductComparisons\ProductComparisonResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProductComparison extends EditRecord
{
    protected static string $resource = ProductComparisonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
