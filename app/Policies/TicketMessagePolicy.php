<?php

namespace App\Policies;

use App\Models\TicketMessage;
use App\Models\User;

class TicketMessagePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Messages are only reachable through a ticket the user may view.
     */
    public function view(User $user, TicketMessage $message): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Only staff may update a message.
     */
    public function update(User $user, TicketMessage $message): bool
    {
        return $user->isStaff();
    }

    /**
     * Only staff may delete a message.
     */
    public function delete(User $user, TicketMessage $message): bool
    {
        return $user->isStaff();
    }
}
