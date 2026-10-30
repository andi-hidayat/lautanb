<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\SignaturePosition;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('employee.name')
                    ->label('Name'),
                TextEntry::make('email')
                    ->label('Email address'),
                TextEntry::make('employee.role')
                    ->label('Role')
                    ->badge(),
                TextEntry::make('employee.position')
                    ->label('Position')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): ?string => filled($state) ? SignaturePosition::tryFrom($state)?->getLabel() : null)
                    ->color(fn (?string $state): string => filled($state) ? (SignaturePosition::tryFrom($state)?->getColor() ?? 'gray') : 'gray'),
                TextEntry::make('employee.division')
                    ->label('Division')
                    ->badge(),
                ImageEntry::make('signature_path')
                    ->label('Signature')
                    ->disk('local')
                    ->placeholder('No signature uploaded')
                    ->imageHeight(80),
                TextEntry::make('created_at')
                    ->label('Created at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->label('Updated at')
                    ->dateTime(),
            ]);
    }
}
