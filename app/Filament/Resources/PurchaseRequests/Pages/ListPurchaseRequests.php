<?php

namespace App\Filament\Resources\PurchaseRequests\Pages;

use App\Enums\PurchaseRequestStatus;
use App\Enums\SignaturePosition;
use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Models\PurchaseRequestDocumentSetting;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListPurchaseRequests extends ListRecords
{
    protected static string $resource = PurchaseRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->createAnother(false),
        ];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $user = Auth::user();
        $query = PurchaseRequestResource::getEloquentQuery();

        $tabs = [
            'all' => Tab::make('Semua')
                ->badge((clone $query)->count()),
        ];

        if ($user) {
            $myActionStatuses = $this->myActionStatuses($user);
            $actionCompanies = collect(['lbi', 'lba'])
                ->filter(fn (string $company): bool => $user->canSignPurchaseRequestsFor($company))
                ->values()
                ->all();

            $tabs['my_actions'] = Tab::make('Perlu Tindakan Saya')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->whereIn('company', $actionCompanies)
                    ->whereIn('status', $myActionStatuses))
                ->badge(fn (): int => PurchaseRequestResource::getEloquentQuery()
                    ->whereIn('company', $actionCompanies)
                    ->whereIn('status', $myActionStatuses)
                    ->count())
                ->badgeColor('warning');
        }

        $tabs['rejected'] = Tab::make('Ditolak / Perlu Revisi')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', PurchaseRequestStatus::Rejected))
            ->badge((clone $query)->where('status', PurchaseRequestStatus::Rejected)->count())
            ->badgeColor('danger');

        $tabs['approved'] = Tab::make('Selesai')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', PurchaseRequestStatus::Approved))
            ->badge((clone $query)->where('status', PurchaseRequestStatus::Approved)->count())
            ->badgeColor('success');

        return $tabs;
    }

    /**
     * @return array<int, string>
     */
    private function myActionStatuses(User $user): array
    {
        if ($user->isStaff()) {
            return array_map(
                fn (PurchaseRequestStatus $status): string => $status->value,
                PurchaseRequestStatus::approvalStatuses(),
            );
        }

        return array_values(array_unique(array_filter(array_map(
            static fn (SignaturePosition $position): ?string => PurchaseRequestStatus::forSignaturePosition($position)?->value,
            PurchaseRequestDocumentSetting::approvalPositionsForUser($user),
        ))));
    }
}
