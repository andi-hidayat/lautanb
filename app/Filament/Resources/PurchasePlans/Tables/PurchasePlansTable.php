<?php

namespace App\Filament\Resources\PurchasePlans\Tables;

use App\Models\PurchasePlan;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PurchasePlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withSum('items', 'total_cost'))
            ->columns([
                TextColumn::make('purchase_item_name')
                    ->label('Purchase Plan Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('year')
                    ->label('Year')
                    ->badge()
                    ->sortable(),
                TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->badge(),
                TextColumn::make('items_sum_total_cost')
                    ->label('Total Cost')
                    ->state(fn (PurchasePlan $record): string => PurchasePlan::formatRupiah(
                        (float) ($record->items_sum_total_cost ?? 0),
                    ))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
