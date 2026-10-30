<?php

namespace App\Filament\Resources\Tickets\Pages;

use App\Enums\TicketStatus;
use App\Filament\Resources\Tickets\TicketResource;
use App\Services\TicketNotifier;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    /**
     * Fill in the ticket data before it is saved.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        // Regular users always create an Open ticket.
        if (! Auth::user()?->canHandleTickets()) {
            $data['status'] = TicketStatus::Open->value;
        }

        return $data;
    }

    /**
     * Notify staff that a new ticket was submitted.
     */
    protected function afterCreate(): void
    {
        TicketNotifier::newTicket($this->record);
    }
}
