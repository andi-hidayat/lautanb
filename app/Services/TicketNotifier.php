<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Notifications sent when there is activity on a ticket.
 */
class TicketNotifier
{
    /**
     * Notify all staff members when a ticket is created.
     */
    public static function newTicket(Ticket $ticket): void
    {
        Notification::make()
            ->title('New ticket: '.$ticket->title)
            ->body(Str::limit($ticket->description, 100))
            ->info()
            ->sendToDatabase(User::query()->staff()->get());
    }

    /**
     * Notify the other party when a reply is added.
     */
    public static function newReply(TicketMessage $message): void
    {
        $message->loadMissing('ticket.requester', 'ticket.assignee');

        $ticket = $message->ticket;

        // Requester replies -> notify the assignee (or all staff).
        // Staff replies -> notify the requester.
        $recipients = $message->user_id === $ticket->user_id
            ? ($ticket->assignee ? collect([$ticket->assignee]) : User::query()->staff()->get())
            : collect([$ticket->requester]);

        Notification::make()
            ->title('New reply: '.$ticket->title)
            ->body(Str::limit($message->body, 100))
            ->info()
            ->sendToDatabase($recipients);
    }

    /**
     * Notify the requester when the status changes.
     */
    public static function statusChanged(Ticket $ticket): void
    {
        Notification::make()
            ->title('Ticket status changed: '.$ticket->status->getLabel())
            ->body($ticket->title)
            ->success()
            ->sendToDatabase($ticket->requester);
    }
}
