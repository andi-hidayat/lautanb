<?php

namespace App\Filament\Resources\Tickets\Pages;

use App\Enums\TicketStatus;
use App\Filament\Resources\Tickets\TicketResource;
use App\Services\TicketNotifier;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * Keep resolved_at in sync with the selected status.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['status'] ?? null) === TicketStatus::Resolved->value) {
            $data['resolved_at'] = $this->record->resolved_at ?? now();
        }

        if (($data['status'] ?? null) === TicketStatus::Open->value) {
            $data['resolved_at'] = null;
        }

        return $data;
    }

    /**
     * Notify the requester when the status changes.
     */
    protected function afterSave(): void
    {
        if ($this->record->wasChanged('status')) {
            TicketNotifier::statusChanged($this->record);
        }
    }
}
