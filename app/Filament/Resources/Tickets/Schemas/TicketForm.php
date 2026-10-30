<?php

namespace App\Filament\Resources\Tickets\Schemas;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class TicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Title')
                    ->required()
                    ->maxLength(255),

                Select::make('priority')
                    ->label('Priority')
                    ->options(TicketPriority::options())
                    ->default(TicketPriority::Medium->value)
                    ->required(),

                // Status and assignee can only be set by staff.
                Select::make('status')
                    ->label('Status')
                    ->options(TicketStatus::options())
                    ->default(TicketStatus::Open->value)
                    ->required()
                    ->visible(fn (): bool => Auth::user()?->canHandleTickets() ?? false),

                Select::make('assigned_to')
                    ->label('Assigned to')
                    ->options(fn (): array => User::query()
                        ->staff()
                        ->whereHas('employee', fn ($q) => $q->where('division', 'it'))
                        ->with('employee')
                        ->get()
                        ->mapWithKeys(fn (User $user): array => [
                            $user->id => $user->employee?->name ?? $user->email,
                        ])
                        ->all())
                    ->searchable()
                    ->preload()
                    ->visible(fn (): bool => Auth::user()?->canHandleTickets() ?? false),

                Textarea::make('description')
                    ->label('Description')
                    ->required()
                    ->rows(6)
                    ->columnSpanFull(),

                FileUpload::make('attachments')
                    ->label('Attachments')
                    ->multiple()
                    ->directory('tickets')
                    ->maxSize(10240)
                    ->columnSpanFull(),
            ]);
    }
}
