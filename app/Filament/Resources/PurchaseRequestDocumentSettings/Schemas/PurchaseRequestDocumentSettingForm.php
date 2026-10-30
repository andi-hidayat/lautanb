<?php

namespace App\Filament\Resources\PurchaseRequestDocumentSettings\Schemas;

use App\Models\PurchaseRequestDocumentSetting;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PurchaseRequestDocumentSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('division')
                    ->options(PurchaseRequestDocumentSetting::DIVISIONS)
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('document_number')
                    ->label('Doc. Number')
                    ->required()
                    ->maxLength(255),
                TextInput::make('revision_number')
                    ->label('Revision Number')
                    ->required()
                    ->maxLength(255),
                DatePicker::make('effective_date')
                    ->required(),
                TextInput::make('number_prefix')
                    ->label('Purchase Request Number Prefix')
                    ->helperText('Number Format PREFIX/TAHUN/URUTAN, ex PR/IT/2026/0001.')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(64),
            ]);
    }
}
