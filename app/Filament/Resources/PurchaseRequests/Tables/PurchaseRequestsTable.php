<?php

namespace App\Filament\Resources\PurchaseRequests\Tables;

use App\Enums\PurchaseRequestStatus;
use App\Filament\Resources\PurchaseRequests\Actions\PurchaseRequestApprovalActions;
use App\Models\PurchaseRequest;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PurchaseRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('purchase_request_number')
                    ->label('Number')
                    ->searchable(),
                TextColumn::make('division')
                    ->badge()
                    ->searchable(),
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('total_cost')
                    ->label('Total Cost')
                    ->numeric()
                    ->prefix('Rp ')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable()
                    ->tooltip(fn (PurchaseRequest $record): ?string => $record->status === PurchaseRequestStatus::Rejected ? 'Alasan: '.$record->rejection_reason : null),
                TextColumn::make('requestedBy.email')
                    ->label('Requested by')
                    ->searchable(),
                TextColumn::make('needed_at')
                    ->date()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                PurchaseRequestApprovalActions::makeApproveAction(),
                PurchaseRequestApprovalActions::makeRejectAction(),
                PurchaseRequestApprovalActions::makeResubmitAction(),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
