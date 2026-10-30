<?php

namespace App\Filament\Resources\PettyCashes\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PettyCashInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('number')
                    ->label('No'),
                TextEntry::make('date')
                    ->label('Date')
                    ->date(),
                TextEntry::make('pr_number')
                    ->label('PR Number')
                    ->placeholder('-'),
                TextEntry::make('coa')
                    ->label('COA')
                    ->placeholder('-'),
                TextEntry::make('description')
                    ->label('Description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('debit')
                    ->label('Debit')
                    ->money('IDR'),
                TextEntry::make('credit')
                    ->label('Credit')
                    ->money('IDR'),
                TextEntry::make('balance')
                    ->label('Balance')
                    ->money('IDR'),
                TextEntry::make('created_at')
                    ->label('Created at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->label('Updated at')
                    ->dateTime(),
            ]);
    }
}
