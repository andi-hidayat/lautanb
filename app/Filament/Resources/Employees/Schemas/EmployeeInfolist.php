<?php

namespace App\Filament\Resources\Employees\Schemas;

use App\Enums\SignaturePosition;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class EmployeeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('Name'),
                TextEntry::make('email')
                    ->label('Email address'),
                TextEntry::make('role')
                    ->label('Role')
                    ->badge(),
                TextEntry::make('position')
                    ->label('Position')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): ?string => filled($state) ? SignaturePosition::tryFrom($state)?->getLabel() : null)
                    ->color(fn (?string $state): string => filled($state) ? (SignaturePosition::tryFrom($state)?->getColor() ?? 'gray') : 'gray'),
                TextEntry::make('division')
                    ->label('Division')
                    ->badge(),
                TextEntry::make('created_at')
                    ->label('Created at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->label('Updated at')
                    ->dateTime(),
            ]);
    }
}
