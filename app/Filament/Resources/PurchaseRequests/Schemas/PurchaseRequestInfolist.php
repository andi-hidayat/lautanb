<?php

namespace App\Filament\Resources\PurchaseRequests\Schemas;

use App\Enums\PurchaseRequestStatus;
use App\Filament\Resources\PurchaseRequests\Actions\PurchaseRequestApprovalActions;
use App\Models\PurchaseRequest;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class PurchaseRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Tindakan Persetujuan Dokumen')
                    ->visible(fn (?PurchaseRequest $record): bool => $record !== null && ($record->canBeApprovedBy(Auth::user()) || $record->canBeResubmittedBy(Auth::user())))
                    ->schema([
                        TextEntry::make('approval_action_banner')
                            ->hiddenLabel()
                            ->state(function (?PurchaseRequest $record): HtmlString {
                                if ($record === null) {
                                    return new HtmlString('');
                                }

                                $positionName = match ($record->status) {
                                    PurchaseRequestStatus::PendingFinance => 'Senior Finance (Selvi Rahmawati)',
                                    PurchaseRequestStatus::PendingAccounting => 'Accounting Staff (Diana Lexy)',
                                    PurchaseRequestStatus::PendingAccountingSpv => 'Accounting SPV (Tubagus Sururi)',
                                    PurchaseRequestStatus::PendingDirector => 'Direktur (Kabal Yudhanegara)',
                                    PurchaseRequestStatus::Rejected => 'Pemohon (Perlu Revisi)',
                                    default => 'Approver',
                                };

                                return new HtmlString('
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white shadow-md">
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-base font-bold text-gray-900 dark:text-white">Saat ini giliran persetujuan Anda: <span class="text-blue-600 dark:text-blue-400">'.$positionName.'</span></div>
                                            <div class="text-xs text-gray-600 dark:text-gray-300 mt-0.5">Silakan periksa detail pengeluaran dana di bawah ini, kemudian klik tombol hijau di bawah untuk menyetujui (approve), atau tombol merah untuk menolak (reject).</div>
                                        </div>
                                    </div>
                                ');
                            })
                            ->columnSpanFull(),

                        Actions::make([
                            PurchaseRequestApprovalActions::makeApproveAction()
                                ->size('lg')
                                ->button(),
                            PurchaseRequestApprovalActions::makeRejectAction()
                                ->size('lg')
                                ->button(),
                            PurchaseRequestApprovalActions::makeResubmitAction()
                                ->size('lg')
                                ->button(),
                        ])
                            ->columnSpanFull(),
                    ])
                    ->extraAttributes([
                        'class' => 'bg-blue-50/70 border-2 border-blue-300 dark:bg-blue-950/30 dark:border-blue-700 shadow-sm rounded-xl',
                    ])
                    ->columnSpanFull(),

                Section::make('Catatan Revisi / Penolakan')
                    ->visible(fn (?PurchaseRequest $record): bool => $record !== null && $record->status === PurchaseRequestStatus::Rejected)
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('rejector.name')
                                    ->label('Ditolak Oleh')
                                    ->color('danger')
                                    ->weight('bold'),
                                TextEntry::make('rejected_at_stage')
                                    ->label('Tahapan Penolakan')
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'finance' => 'Senior Finance',
                                        'accounting' => 'Accounting Staff',
                                        'accounting_spv' => 'Accounting SPV',
                                        'director' => 'Direktur',
                                        default => (string) $state,
                                    })
                                    ->badge()
                                    ->color('danger'),
                                TextEntry::make('rejected_at')
                                    ->label('Waktu Penolakan')
                                    ->dateTime()
                                    ->color('danger'),
                            ]),
                        TextEntry::make('rejection_reason')
                            ->label('Alasan / Catatan Perbaikan')
                            ->color('danger')
                            ->columnSpanFull(),
                    ])
                    ->extraAttributes([
                        'class' => 'border-l-4 border-l-red-500 bg-red-50/30 dark:bg-red-950/20',
                    ])
                    ->columnSpanFull(),

                Section::make('Informasi Permintaan')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('title'),
                                TextEntry::make('purchase_request_number')
                                    ->label('Number')
                                    ->placeholder('-'),
                                TextEntry::make('status')
                                    ->badge(),
                                TextEntry::make('division')
                                    ->label('Division')
                                    ->badge()
                                    ->placeholder('-'),
                                TextEntry::make('document_number')
                                    ->label('Doc. Number')
                                    ->placeholder('-'),
                                TextEntry::make('revision_number')
                                    ->label('Rev. Number')
                                    ->placeholder('-'),
                                TextEntry::make('issue_date')
                                    ->label('Issue Date')
                                    ->date()
                                    ->placeholder('-'),
                                TextEntry::make('effective_date')
                                    ->label('Effective Date')
                                    ->date()
                                    ->placeholder('-'),
                                TextEntry::make('needed_at')
                                    ->date()
                                    ->placeholder('-'),
                                TextEntry::make('requestedBy.email')
                                    ->label('Requested by')
                                    ->placeholder('-'),
                                TextEntry::make('total_cost')
                                    ->label('Total Cost')
                                    ->prefix('Rp ')
                                    ->numeric(),
                            ]),
                        TextEntry::make('description')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Section::make('Tanda Tangan & Persetujuan')
                    ->schema([
                        Grid::make(5)
                            ->schema([
                                ImageEntry::make('user_signature')
                                    ->label('Submitted by')
                                    ->placeholder('Belum ditandatangani')
                                    ->height(80),
                                ImageEntry::make('finance_signature')
                                    ->label('Senior Finance')
                                    ->placeholder('Menunggu Persetujuan')
                                    ->height(80),
                                ImageEntry::make('accounting_signature')
                                    ->label('Accounting (Staff)')
                                    ->placeholder('Menunggu Persetujuan')
                                    ->height(80),
                                ImageEntry::make('accounting_spv_signature')
                                    ->label('Accounting SPV')
                                    ->placeholder('Menunggu Persetujuan')
                                    ->height(80),
                                ImageEntry::make('director_signature')
                                    ->label('Director')
                                    ->placeholder('Menunggu Persetujuan')
                                    ->height(80),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
