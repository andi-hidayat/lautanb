<?php

namespace App\Filament\Resources\PurchasePlans;

use App\Filament\Resources\PurchasePlans\Pages\CreatePurchasePlan;
use App\Filament\Resources\PurchasePlans\Pages\EditPurchasePlan;
use App\Filament\Resources\PurchasePlans\Pages\ListPurchasePlans;
use App\Filament\Resources\PurchasePlans\Pages\ViewPurchasePlan;
use App\Filament\Resources\PurchasePlans\Schemas\PurchasePlanForm;
use App\Filament\Resources\PurchasePlans\Schemas\PurchasePlanInfolist;
use App\Filament\Resources\PurchasePlans\Tables\PurchasePlansTable;
use App\Models\PurchasePlan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PurchasePlanResource extends Resource
{
    protected static ?string $model = PurchasePlan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    public static function form(Schema $schema): Schema
    {
        return PurchasePlanForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PurchasePlanInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PurchasePlansTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPurchasePlans::route('/'),
            'create' => CreatePurchasePlan::route('/create'),
            'view' => ViewPurchasePlan::route('/{record}'),
            'edit' => EditPurchasePlan::route('/{record}/edit'),
        ];
    }
}
