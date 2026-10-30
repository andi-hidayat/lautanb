<?php

namespace App\Filament\Resources\ProductComparisons\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Schema;

class ProductComparisonInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('title')
                    ->label('Comparison')
                    ->size('lg')
                    ->weight('bold'),
                TextEntry::make('description')
                    ->label('Description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                ViewEntry::make('comparison_matrix')
                    ->hiddenLabel()
                    ->view('filament.infolists.entries.product-comparison-matrix')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->label('Created at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->label('Updated at')
                    ->dateTime(),
            ]);
    }
}
