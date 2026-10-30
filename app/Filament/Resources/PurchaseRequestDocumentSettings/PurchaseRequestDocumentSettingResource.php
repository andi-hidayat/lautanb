<?php

namespace App\Filament\Resources\PurchaseRequestDocumentSettings;

use App\Filament\Resources\PurchaseRequestDocumentSettings\Pages\CreatePurchaseRequestDocumentSetting;
use App\Filament\Resources\PurchaseRequestDocumentSettings\Pages\EditPurchaseRequestDocumentSetting;
use App\Filament\Resources\PurchaseRequestDocumentSettings\Pages\ListPurchaseRequestDocumentSettings;
use App\Filament\Resources\PurchaseRequestDocumentSettings\Schemas\PurchaseRequestDocumentSettingForm;
use App\Filament\Resources\PurchaseRequestDocumentSettings\Tables\PurchaseRequestDocumentSettingsTable;
use App\Models\PurchaseRequestDocumentSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequestDocumentSettingResource extends Resource
{
    protected static ?string $model = PurchaseRequestDocumentSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Pengaturan Dokumen PR';

    protected static ?string $modelLabel = 'Pengaturan Dokumen PR';

    protected static ?string $pluralModelLabel = 'Pengaturan Dokumen PR';

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->isStaff();
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return PurchaseRequestDocumentSettingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PurchaseRequestDocumentSettingsTable::configure($table);
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
            'index' => ListPurchaseRequestDocumentSettings::route('/'),
            'create' => CreatePurchaseRequestDocumentSetting::route('/create'),
            'edit' => EditPurchaseRequestDocumentSetting::route('/{record}/edit'),
        ];
    }
}
