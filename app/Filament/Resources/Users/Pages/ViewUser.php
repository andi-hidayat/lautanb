<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('uploadSignature')
                ->label('Upload signature')
                ->icon(Heroicon::OutlinedPencilSquare)
                ->color('gray')
                ->visible(fn (): bool => UserResource::canEdit($this->getRecord()))
                ->fillForm(fn (User $record): array => [
                    'signature_path' => $record->signature_path,
                ])
                ->schema([
                    FileUpload::make('signature_path')
                        ->label('Signature')
                        ->helperText('Upload a PNG or JPG image (max 2 MB). A transparent PNG works best.')
                        ->image()
                        ->acceptedFileTypes(['image/png', 'image/jpeg'])
                        ->maxSize(2048)
                        ->disk('local')
                        ->visibility('private')
                        ->directory('signatures')
                        ->preventFilePathTampering(
                            allowFilePathUsing: fn (string $file): bool => str_starts_with($file, 'signatures/'),
                        )
                        ->required(),
                ])
                ->action(function (User $record, array $data): void {
                    $previousSignaturePath = $record->signature_path;

                    $record->update([
                        'signature_path' => $data['signature_path'],
                    ]);

                    if (filled($previousSignaturePath) && $previousSignaturePath !== $record->signature_path) {
                        Storage::disk('local')->delete($previousSignaturePath);
                    }
                })
                ->successNotificationTitle('Signature uploaded'),
            Action::make('removeSignature')
                ->label('Remove signature')
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (User $record): bool => filled($record->signature_path) && UserResource::canEdit($record))
                ->action(function (User $record): void {
                    Storage::disk('local')->delete($record->signature_path);

                    $record->update([
                        'signature_path' => null,
                    ]);
                })
                ->successNotificationTitle('Signature removed'),
            EditAction::make(),
            Action::make('cancel')
                ->label('Cancel')
                ->url(UserResource::getUrl('index')),
        ];
    }
}
