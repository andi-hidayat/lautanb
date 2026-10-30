<?php

namespace App\Filament\Resources\PurchaseRequestDocumentSettings\Tables;

use App\Models\PurchaseRequestDocumentSetting;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PurchaseRequestDocumentSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('division')
                    ->formatStateUsing(fn (string $state): string => PurchaseRequestDocumentSetting::DIVISIONS[$state] ?? $state)
                    ->sortable(),
                TextColumn::make('document_number')
                    ->label('Doc. Number'),
                TextColumn::make('revision_number')
                    ->label('Rev. Number'),
                TextColumn::make('effective_date')
                    ->date(),
                TextColumn::make('number_prefix')
                    ->label('Number Prefix'),
                TextColumn::make('next_number')
                    ->label('Next Sequence')
                    ->numeric(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
