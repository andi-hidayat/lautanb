<?php

namespace App\Filament\Resources\PurchasePlans\Schemas;

use App\Models\PurchasePlan;
use App\Models\PurchasePlanItem;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class PurchasePlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('purchase_item_name')
                            ->label('Purchase Item Name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),
                        Select::make('year')
                            ->label('Year')
                            ->options(array_combine(
                                range(date('Y'), date('Y') + 20),
                                range(date('Y'), date('Y') + 20),
                            ))
                            ->default((string) date('Y'))
                            ->required()
                            ->columnSpan(1),
                    ])
                    ->columnSpanFull(),
                Grid::make(2)
                    ->schema([
                        Textarea::make('description')
                            ->label('Description')
                            ->rows(10)
                            ->columnSpan(1),
                        Textarea::make('purpose')
                            ->label('Purpose')
                            ->rows(10)
                            ->columnSpan(1),
                    ])
                    ->columnSpanFull(),

                Repeater::make('items')
                    ->relationship()
                    ->label('Items')
                    ->addActionLabel('Add Item')
                    ->addAction(
                        fn (Action $action): Action => $action
                            ->button()
                            ->color('primary')
                    )
                    ->reorderable(false)
                    ->minItems(1)
                    ->defaultItems(1)
                    ->deleteAction(
                        fn (Action $action): Action => $action
                            ->visible(fn (Repeater $component): bool => count($component->getRawState() ?? []) > 1)
                    )
                    ->live()
                    ->columns(2)
                    ->schema([
                        TextInput::make('item_name')
                            ->label('Item Name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),
                        TextInput::make('supplier_name')
                            ->label('Supplier Name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),
                        Grid::make(3)
                            ->schema([
                                TextInput::make('unit_price')
                                    ->label('Unit Price')
                                    ->required()
                                    ->numeric(0, ',', '.')
                                    ->prefix('Rp')
                                    ->live(onBlur: false)
                                    ->afterStateUpdated(function (Get $get, Set $set): void {
                                        self::updateItemTotal($get, $set);
                                    }),

                                TextInput::make('quantity')
                                    ->label('Quantity')
                                    ->required()
                                    ->numeric(0, ',', '.')
                                    ->minValue(1)
                                    ->default(1)
                                    ->live(onBlur: false)
                                    ->afterStateUpdated(function (Get $get, Set $set): void {
                                        self::updateItemTotal($get, $set);
                                    }),

                                TextInput::make('total_cost')
                                    ->label('Total Price')
                                    ->required()
                                    ->numeric(0, ',', '.')
                                    ->prefix('Rp')
                                    ->disabled()
                                    ->dehydrated(),
                            ])
                            ->columnSpanFull(),
                        FileUpload::make('attachment')
                            ->label('Picture')
                            ->openable()
                            ->columnSpanFull(),
                        Textarea::make('spesification')
                            ->label('Spesification')
                            ->rows(8)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Grid::make(1)
                    ->schema([
                        TextEntry::make('total_required_cost_label')
                            ->hiddenLabel()
                            ->weight('bold')
                            ->size('lg')
                            ->state('Total Required Cost')
                            ->alignEnd(),
                        TextEntry::make('items_total')
                            ->hiddenLabel()
                            ->weight('bold')
                            ->size('lg')
                            ->state(fn (Get $get): string => PurchasePlan::formatRupiah(
                                PurchasePlan::calculateItemsTotalCost($get('items')),
                            ))
                            ->alignEnd(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Update the item total based on the unit price and quantity.
     */
    public static function updateItemTotal(Get $get, Set $set): void
    {
        $set('total_cost', PurchasePlanItem::calculateTotalCost([
            'unit_price' => $get('unit_price'),
            'quantity' => $get('quantity'),
        ]));
    }
}
