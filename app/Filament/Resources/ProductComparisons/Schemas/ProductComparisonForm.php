<?php

namespace App\Filament\Resources\ProductComparisons\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductComparisonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Comparison Title')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->label('Description')
                    ->rows(3)
                    ->columnSpanFull(),
                Repeater::make('products')
                    ->label('Products to Compare')
                    ->addActionLabel('Add Product')
                    ->minItems(2)
                    ->defaultItems(2)
                    ->reorderable(false)
                    ->grid([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        TextInput::make('product_name')
                            ->label('Product Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('supplier_name')
                            ->label('Supplier / Brand')
                            ->maxLength(255),

                        Repeater::make('specifications')
                            ->label('Technical Specifications')
                            ->addActionLabel('Add Specification')
                            ->minItems(1)
                            ->defaultItems(1)
                            ->reorderable(false)
                            ->columns(2)
                            ->schema([
                                Textarea::make('name')
                                    ->label('Specification')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: false)
                                    ->afterStateUpdated(function (Get $get, Set $set, Textarea $component): void {
                                        self::synchronizeSpecificationName($get, $set, $component);
                                    }),
                                Textarea::make('value')
                                    ->label('Details')
                                    ->required()
                                    // ->rows(2),
                                    ->maxLength(255),
                            ])
                            ->columnSpanFull(),
                        FileUpload::make('attachment')
                            ->label('Attachment')
                            ->multiple()
                            ->disk('public')
                            ->directory('product-comparisons')
                            ->acceptedFileTypes([
                                'image/jpeg',
                                'image/png',
                                'application/pdf',
                            ])
                            ->maxSize(10240),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    private static function synchronizeSpecificationName(Get $get, Set $set, Textarea $component): void
    {
        $statePath = $component->getStatePath();

        if (blank($statePath)) {
            return;
        }

        $statePathSegments = explode('.', $statePath);
        $productsSegmentIndex = array_search('products', $statePathSegments, true);
        $specificationsSegmentIndex = array_search('specifications', $statePathSegments, true);

        if (
            $productsSegmentIndex === false
            || $specificationsSegmentIndex === false
            || ! isset($statePathSegments[$productsSegmentIndex + 1], $statePathSegments[$specificationsSegmentIndex + 1])
        ) {
            return;
        }

        $productsPath = implode('.', array_slice($statePathSegments, 0, $productsSegmentIndex + 1));
        $products = $get($productsPath, true);

        if (! is_array($products)) {
            return;
        }

        $productKey = $statePathSegments[$productsSegmentIndex + 1];
        $specificationKey = $statePathSegments[$specificationsSegmentIndex + 1];
        $specifications = $products[$productKey]['specifications'] ?? null;

        if (! is_array($specifications)) {
            return;
        }

        $specificationIndex = array_search($specificationKey, array_map('strval', array_keys($specifications)), true);

        if ($specificationIndex === false) {
            return;
        }

        $name = $component->getState();

        foreach ($products as $currentProductKey => &$product) {
            if ($currentProductKey === $productKey || ! is_array($product)) {
                continue;
            }

            $productSpecifications = $product['specifications'] ?? [];

            if (! is_array($productSpecifications)) {
                continue;
            }

            $targetSpecificationKey = array_keys($productSpecifications)[$specificationIndex] ?? null;

            if ($targetSpecificationKey === null) {
                if (blank($name)) {
                    continue;
                }

                $targetSpecificationKey = (string) Str::uuid();
                $productSpecifications[$targetSpecificationKey] = [
                    'name' => $name,
                    'value' => '',
                ];
            } else {
                $productSpecifications[$targetSpecificationKey]['name'] = $name;
            }

            $product['specifications'] = $productSpecifications;
        }

        unset($product);

        $set($productsPath, $products, true);
    }
}
