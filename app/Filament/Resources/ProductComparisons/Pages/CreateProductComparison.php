<?php

namespace App\Filament\Resources\ProductComparisons\Pages;

use App\Filament\Resources\ProductComparisons\ProductComparisonResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductComparison extends CreateRecord
{
    protected static string $resource = ProductComparisonResource::class;

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! auth()->user()?->isStaff()) {
            $data['company'] = auth()->user()?->company;
        }

        return $data;
    }
}
