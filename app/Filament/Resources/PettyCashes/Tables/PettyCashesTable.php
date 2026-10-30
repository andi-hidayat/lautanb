<?php

namespace App\Filament\Resources\PettyCashes\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PettyCashesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->label('No')
                    ->sortable(),
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('pr_number')
                    ->label('PR Number')
                    ->searchable(),
                TextColumn::make('description')
                    ->label('Description')
                    ->searchable(),
                TextColumn::make('debit')
                    ->money('IDR'),
                TextColumn::make('credit')
                    ->money('IDR'),
                TextColumn::make('balance')
                    ->money('IDR'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
