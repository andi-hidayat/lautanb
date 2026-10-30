<?php

namespace App\Filament\Resources\Tickets\Schemas;

use App\Models\Ticket;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TicketInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make([
                    'default' => 1,
                    'lg' => 2,
                ])
                    ->schema([
                        Section::make('Ticket Details')
                            ->columns(2)
                            ->schema([
                                TextEntry::make('title')
                                    ->label('Title')
                                    ->weight('bold')
                                    ->columnSpanFull(),

                                TextEntry::make('requester.employee.name')
                                    ->label('Requester')
                                    ->placeholder('-'),

                                TextEntry::make('assignee.employee.name')
                                    ->label('Assigned to')
                                    ->placeholder('Unassigned'),

                                TextEntry::make('priority')
                                    ->label('Priority')
                                    ->badge(),

                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge(),

                                TextEntry::make('created_at')
                                    ->label('Created')
                                    ->dateTime(),

                                TextEntry::make('resolved_at')
                                    ->label('Resolved')
                                    ->dateTime()
                                    ->placeholder('-'),
                            ])
                            ->extraAttributes(['class' => 'ticket-main-panel']),

                        Group::make([
                            Section::make('Description')
                                ->schema([
                                    TextEntry::make('description')
                                        ->hiddenLabel()
                                        ->prose(),
                                ]),

                            Section::make('Attachments')
                                ->schema([
                                    TextEntry::make('attachments')
                                        ->label('Attachments')
                                        ->placeholder('No attachments')
                                        ->listWithLineBreaks()
                                        ->formatStateUsing(fn (string $state): string => basename($state))
                                        ->url(fn (Ticket $ticket, string $state): string => $ticket->attachmentUrl($state))
                                        ->openUrlInNewTab(),
                                ]),
                        ])
                            ->extraAttributes(['class' => 'ticket-side-panels']),
                    ]),
            ]);
    }
}
