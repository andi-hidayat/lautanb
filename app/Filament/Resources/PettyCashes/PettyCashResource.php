<?php

namespace App\Filament\Resources\PettyCashes;

use App\Filament\Resources\PettyCashes\Pages\CreatePettyCash;
use App\Filament\Resources\PettyCashes\Pages\EditPettyCash;
use App\Filament\Resources\PettyCashes\Pages\ListPettyCashes;
use App\Filament\Resources\PettyCashes\Pages\ViewPettyCash;
use App\Filament\Resources\PettyCashes\Schemas\PettyCashForm;
use App\Filament\Resources\PettyCashes\Schemas\PettyCashInfolist;
use App\Filament\Resources\PettyCashes\Tables\PettyCashesTable;
use App\Models\PettyCash;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PettyCashResource extends Resource
{
    protected static ?string $model = PettyCash::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return PettyCashForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PettyCashInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PettyCashesTable::configure($table);
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
            'index' => ListPettyCashes::route('/'),
            'create' => CreatePettyCash::route('/create'),
            'view' => ViewPettyCash::route('/{record}'),
            'edit' => EditPettyCash::route('/{record}/edit'),
        ];
    }
}
