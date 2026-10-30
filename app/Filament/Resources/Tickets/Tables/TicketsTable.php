<?php

namespace App\Filament\Resources\Tickets\Tables;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class TicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('No.')
                    ->prefix('#')
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Title')
                    ->description(fn (Ticket $ticket): string => Str::limit($ticket->description, 60))
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('requester.employee.name')
                    ->label('Requester')
                    ->placeholder('-'),

                TextColumn::make('priority')
                    ->label('Priority')
                    ->badge()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(TicketStatus::options()),

                SelectFilter::make('priority')
                    ->label('Priority')
                    ->options(TicketPriority::options()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
