<?php

namespace App\Filament\Resources\PurchasePlans\Schemas;

use App\Models\PurchasePlan;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PurchasePlanInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('purchase_item_name')
                    ->label('Plan Name')
                    ->size('lg')
                    ->weight('bold'),
                TextEntry::make('year')
                    ->label('Year')
                    ->badge(),
                TextEntry::make('description')
                    ->label('Description')
                    ->html()
                    ->prose(),
                TextEntry::make('purpose')
                    ->label('Purpose')
                    ->html()
                    ->prose(),
                RepeatableEntry::make('items')
                    ->label('Items')
                    ->columnSpanFull()
                    ->table([
                        TableColumn::make('Purchase Name'),
                        TableColumn::make('Supplier Name'),
                        TableColumn::make('Unit Price'),
                        TableColumn::make('Quantity'),
                        TableColumn::make('Total Price'),
                        TableColumn::make('Spesification'),
                        TableColumn::make('Picture'),
                    ])
                    ->schema([
                        TextEntry::make('item_name'),
                        TextEntry::make('supplier_name'),
                        TextEntry::make('unit_price')
                            ->money('IDR'),
                        TextEntry::make('quantity')
                            ->numeric(),
                        TextEntry::make('total_cost')
                            ->money('IDR'),
                        TextEntry::make('spesification')
                            ->placeholder('-'),
                        ImageEntry::make('attachment')
                            ->imageHeight(80)
                            ->placeholder('No picture'),
                    ]),
                // TextEntry::make('items_total')
                //     ->label('Total Required Cost')
                //     ->weight('bold')
                //     ->size('lg')
                //     ->state(fn (PurchasePlan $record): string => PurchasePlan::formatRupiah(
                //         (float) $record->items->sum('total_cost'),
                //     ))
                //     ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->label('Created at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->label('Updated at')
                    ->dateTime(),
            ]);
    }
}
