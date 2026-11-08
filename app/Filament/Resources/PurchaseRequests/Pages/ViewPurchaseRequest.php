<?php

namespace App\Filament\Resources\PurchaseRequests\Pages;

use App\Enums\SignaturePosition;
use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Filament\Resources\PurchaseRequests\Schemas\PurchaseRequestForm;
use App\Models\PurchaseRequest;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ViewPurchaseRequest extends ViewRecord
{
    protected static string $resource = PurchaseRequestResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->fillForm();
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->model($this->getRecord())
            ->operation('view')
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        $record = $this->getRecord();

        return $schema->components([
            Form::make([
                PurchaseRequestForm::signatureSection(),
            ])
                ->visible(fn (): bool => $record->canBeApprovedBy(Auth::user()))
                ->livewireSubmitHandler('approve')
                ->footer([
                    Actions::make([
                        Action::make('approve')
                            ->label('Tanda Tangan & Setujui')
                            ->icon('heroicon-o-check-circle')
                            ->color('success')
                            ->visible(fn (): bool => $record->canBeApprovedBy(Auth::user()))
                            ->action(fn () => $this->approve()),
                    ])
                        ->alignment('end'),
                ]),
        ]);
    }

    public function approve(): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            throw new AuthorizationException('Pengguna harus masuk untuk menyetujui permintaan.');
        }

        $record = $this->getRecord();

        if (! $record->canBeApprovedBy($user)) {
            throw new AuthorizationException('Anda tidak dapat menyetujui permintaan pada tahap ini.');
        }

        $position = $record->status?->expectedSignaturePosition();
        if ($position === null) {
            throw new AuthorizationException('Permintaan ini tidak memiliki tahap persetujuan aktif.');
        }

        $signature = $this->data[$position->signatureField()] ?? null;

        if (! is_string($signature) || blank($signature)) {
            throw ValidationException::withMessages([
                'data.'.$position->signatureField() => 'Tanda tangan wajib diisi sebelum menyetujui permintaan.',
            ]);
        }

        $record->approve($user, $signature);

        Notification::make()
            ->title('Permintaan Berhasil Disetujui')
            ->body('Dokumen '.$record->purchase_request_number.' telah disetujui.')
            ->success()
            ->send();

        $this->refreshFormData(array_map(
            static fn (SignaturePosition $signaturePosition): string => $signaturePosition->signatureField(),
            SignaturePosition::signingCases(),
        ));
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (PurchaseRequest $record): bool => ! $record->isFullyApprovedAndSigned()),
        ];
    }
}
