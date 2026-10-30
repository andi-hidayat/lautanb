<?php

namespace App\Policies;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    /**
     * Every authenticated user may open the ticket list.
     * The records themselves are scoped in TicketResource.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Staff may view every ticket, regular users only their own.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        return $user->isStaff() || $ticket->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Closed tickets can no longer be edited by regular users.
     */
    public function update(User $user, Ticket $ticket): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        return $ticket->user_id === $user->id
            && $ticket->status !== TicketStatus::Closed;
    }

    /**
     * Only a superadmin may delete a ticket.
     */
    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->isSuperadmin();
    }
}
