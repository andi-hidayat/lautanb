<?php

namespace App\Filament\Resources\PurchaseRequests\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PurchaseRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('title'),
                TextEntry::make('purchase_request_number')
                    ->label('Number')
                    ->placeholder('-'),
                TextEntry::make('division')
                    ->label('Division')
                    ->placeholder('-'),
                TextEntry::make('document_number')
                    ->label('Doc. Number')
                    ->placeholder('-'),
                TextEntry::make('revision_number')
                    ->label('Rev. Number')
                    ->placeholder('-'),
                TextEntry::make('issue_date')
                    ->label('Issue Date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('effective_date')
                    ->label('Effective Date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('total_cost')
                    ->label('Total Cost')
                    ->prefix('Rp ')
                    ->numeric(),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('requestedBy.email')
                    ->label('Requested by')
                    ->placeholder('-'),
                TextEntry::make('needed_at')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
