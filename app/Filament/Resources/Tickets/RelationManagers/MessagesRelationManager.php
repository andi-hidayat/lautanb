<?php

namespace App\Filament\Resources\Tickets\RelationManagers;

use App\Enums\TicketStatus;
use App\Services\TicketNotifier;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    protected static ?string $title = 'Conversation';

    protected static bool $isLazy = false;

    protected string $view = 'filament.tickets.messages-relation-manager';

    public ?string $replyBody = null;

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('body')
            ->defaultSort('created_at')
            ->paginated(false)
            ->modifyQueryUsing(function (Builder $query): Builder {
                $query->with('user.employee');

                // Internal notes (legacy data) are only visible to staff.
                if (auth()->user()?->canHandleTickets()) {
                    return $query;
                }

                return $query->where('is_internal', false);
            })
            ->columns([
                TextColumn::make('body'),
            ])
            ->emptyStateIcon('heroicon-o-chat-bubble-left-right')
            ->emptyStateHeading('No messages yet')
            ->emptyStateDescription('Start the conversation by sending a reply.')
            ->content(view('filament.tickets.conversation'));
    }

    public function sendReply(): void
    {
        if ($this->isReadOnly()) {
            return;
        }

        $this->validate([
            'replyBody' => ['required', 'string', 'max:5000'],
        ]);

        $message = $this->getOwnerRecord()->messages()->create([
            'user_id' => auth()->id(),
            'body' => $this->replyBody,
        ]);

        TicketNotifier::newReply($message);

        $this->replyBody = null;

        $this->flushCachedTableRecords();
    }

    /**
     * Regular users can only reply to their own open tickets.
     * Staff can reply at any time.
     */
    public function isReadOnly(): bool
    {
        $ticket = $this->getOwnerRecord();

        if (auth()->user()?->canHandleTickets()) {
            return false;
        }

        return $ticket->user_id !== auth()->id()
            || $ticket->status === TicketStatus::Closed;
    }
}
