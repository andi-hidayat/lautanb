<?php

namespace App\Filament\Resources\PurchaseRequests\Actions;

use App\Models\PurchaseRequest;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class PurchaseRequestApprovalActions
{
    public static function makeRejectAction(): Action
    {
        return Action::make('reject')
            ->label('Reject')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn (PurchaseRequest $record): bool => $record->canBeRejectedBy(Auth::user()))
            ->modalHeading(fn (PurchaseRequest $record): string => 'Tolak Permintaan - '.$record->purchase_request_number)
            ->modalDescription('Berikan catatan atau alasan penolakan yang wajib diperbaiki oleh pemohon sebelum permohonan dapat diajukan kembali.')
            ->modalSubmitActionLabel('Tolak & Kembalikan ke Pemohon')
            ->form([
                Textarea::make('rejection_reason')
                    ->label('Alasan Penolakan / Catatan Perbaikan')
                    ->placeholder('Contoh: Rincian harga belum sesuai SOP, mohon lampirkan penawaran tandingan...')
                    ->rows(4)
                    ->required(),
            ])
            ->action(function (array $data, PurchaseRequest $record): void {
                $user = Auth::user();

                if (! $user) {
                    return;
                }

                $record->reject($user, $data['rejection_reason']);

                Notification::make()
                    ->title('Permintaan Ditolak')
                    ->body('Dokumen '.$record->purchase_request_number.' telah ditolak dan dikembalikan ke pemohon.')
                    ->warning()
                    ->send();
            });
    }

    public static function makeResubmitAction(): Action
    {
        return Action::make('resubmit')
            ->label('Ajukan Ulang')
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->visible(fn (PurchaseRequest $record): bool => $record->canBeResubmittedBy(Auth::user()))
            ->requiresConfirmation()
            ->modalHeading(fn (PurchaseRequest $record): string => 'Ajukan Ulang Permintaan - '.$record->purchase_request_number)
            ->modalDescription('Pastikan Anda telah memperbaiki semua catatan revisi yang diminta. Permintaan akan kembali diajukan ke Senior Finance.')
            ->modalSubmitActionLabel('Ya, Ajukan Ulang')
            ->action(function (PurchaseRequest $record): void {
                $user = Auth::user();

                if (! $user) {
                    return;
                }

                $record->resubmit($user);

                Notification::make()
                    ->title('Permintaan Diajukan Ulang')
                    ->body('Dokumen '.$record->purchase_request_number.' berhasil diajukan ulang ke Senior Finance.')
                    ->success()
                    ->send();
            });
    }
}
