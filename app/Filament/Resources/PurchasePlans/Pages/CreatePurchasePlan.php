<?php

namespace App\Filament\Resources\PurchasePlans\Pages;

use App\Filament\Resources\PurchasePlans\PurchasePlanResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchasePlan extends CreateRecord
{
    protected static string $resource = PurchasePlanResource::class;

    protected static bool $canCreateAnother = false;
}
