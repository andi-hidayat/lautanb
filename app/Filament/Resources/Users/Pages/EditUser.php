<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\Employee;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected ?string $previousSignaturePath = null;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! auth()->user()?->isStaff()) {
            $data['employee_id'] = $this->getRecord()->employee_id;
        }

        $employeeId = $data['employee_id'] ?? $this->getRecord()->employee_id;
        $data['employee_id'] = $employeeId;
        $data['email'] = Employee::findOrFail($employeeId)->email;

        $this->previousSignaturePath = $this->getRecord()->signature_path;

        return $data;
    }

    protected function afterSave(): void
    {
        $currentSignaturePath = $this->getRecord()->signature_path;

        if (
            filled($this->previousSignaturePath) &&
            $this->previousSignaturePath !== $currentSignaturePath
        ) {
            Storage::disk('local')->delete($this->previousSignaturePath);
        }

        // Re-authenticate if the currently logged-in user edited their own account,
        // to prevent session invalidation (e.g. after password change).
        if ($this->getRecord()->is(auth()->user())) {
            auth()->setUser($this->getRecord()->fresh());
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
