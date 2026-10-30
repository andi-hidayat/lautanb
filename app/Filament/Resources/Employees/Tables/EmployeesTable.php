<?php

namespace App\Filament\Resources\Employees\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('role')
                    ->badge(),
                TextColumn::make('company')
                    ->badge(),
                TextColumn::make('position')
                    ->label('Position')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): ?string => filled($state) ? str($state)->replace('_', ' ')->title()->value() : null),
                TextColumn::make('division')
                    ->badge(),
                TextColumn::make('user.id')
                    ->label('Akun')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state ? 'Exist' : 'Not Exist')
                    ->color(fn ($state): string => $state ? 'success' : 'gray'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
