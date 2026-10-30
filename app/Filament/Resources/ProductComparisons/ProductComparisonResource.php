<?php

namespace App\Filament\Resources\ProductComparisons;

use App\Filament\Resources\ProductComparisons\Pages\CreateProductComparison;
use App\Filament\Resources\ProductComparisons\Pages\EditProductComparison;
use App\Filament\Resources\ProductComparisons\Pages\ListProductComparisons;
use App\Filament\Resources\ProductComparisons\Pages\ViewProductComparison;
use App\Filament\Resources\ProductComparisons\Schemas\ProductComparisonForm;
use App\Filament\Resources\ProductComparisons\Schemas\ProductComparisonInfolist;
use App\Filament\Resources\ProductComparisons\Tables\ProductComparisonsTable;
use App\Models\ProductComparison;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProductComparisonResource extends Resource
{
    protected static ?string $model = ProductComparison::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return ProductComparisonForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProductComparisonInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductComparisonsTable::configure($table);
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
            'index' => ListProductComparisons::route('/'),
            'create' => CreateProductComparison::route('/create'),
            'view' => ViewProductComparison::route('/{record}'),
            'edit' => EditProductComparison::route('/{record}/edit'),
        ];
    }
}
