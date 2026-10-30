<?php

namespace App\Filament\Resources\ProductComparisons\Pages;

use App\Filament\Resources\ProductComparisons\ProductComparisonResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProductComparisons extends ListRecords
{
    protected static string $resource = ProductComparisonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
