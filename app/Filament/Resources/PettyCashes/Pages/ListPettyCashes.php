<?php

namespace App\Filament\Resources\PettyCashes\Pages;

use App\Filament\Resources\PettyCashes\PettyCashResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPettyCashes extends ListRecords
{
    protected static string $resource = PettyCashResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
