<?php

namespace App\Filament\Resources\PettyCashes\Pages;

use App\Filament\Resources\PettyCashes\PettyCashResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPettyCash extends ViewRecord
{
    protected static string $resource = PettyCashResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
