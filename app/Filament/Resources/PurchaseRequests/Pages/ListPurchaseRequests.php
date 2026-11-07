<?php

namespace App\Filament\Resources\PurchaseRequests\Pages;

use App\Enums\PurchaseRequestStatus;
use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Models\PurchaseRequest;
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

        $tabs = [
            'all' => Tab::make('Semua')
                ->badge(PurchaseRequest::count()),
        ];

        if ($user) {
            $tabs['my_actions'] = Tab::make('Perlu Tindakan Saya')
                ->modifyQueryUsing(function (Builder $query) use ($user): Builder {
                    return $query->where(function (Builder $q) use ($user): void {
                        if ($user->position === 'senior' && $user->division === 'finance') {
                            $q->orWhere('status', PurchaseRequestStatus::PendingFinance);
                        }
                        if ($user->position === 'staff' && $user->division === 'accounting') {
                            $q->orWhere('status', PurchaseRequestStatus::PendingAccounting);
                        }
                        if ($user->position === 'supervisor' && $user->division === 'accounting') {
                            $q->orWhere('status', PurchaseRequestStatus::PendingAccountingSpv);
                        }
                        if ($user->position === 'director' && $user->division === 'bod') {
                            $q->orWhere('status', PurchaseRequestStatus::PendingDirector);
                        }
                        if ($user->isStaff()) {
                            $q->orWhereIn('status', [
                                PurchaseRequestStatus::PendingFinance,
                                PurchaseRequestStatus::PendingAccounting,
                                PurchaseRequestStatus::PendingAccountingSpv,
                                PurchaseRequestStatus::PendingDirector,
                            ]);
                        }
                    });
                })
                ->badge(function () use ($user): int {
                    $query = PurchaseRequest::query();

                    if ($user->position === 'senior' && $user->division === 'finance') {
                        return $query->where('status', PurchaseRequestStatus::PendingFinance)->count();
                    }
                    if ($user->position === 'staff' && $user->division === 'accounting') {
                        return $query->where('status', PurchaseRequestStatus::PendingAccounting)->count();
                    }
                    if ($user->position === 'supervisor' && $user->division === 'accounting') {
                        return $query->where('status', PurchaseRequestStatus::PendingAccountingSpv)->count();
                    }
                    if ($user->position === 'director' && $user->division === 'bod') {
                        return $query->where('status', PurchaseRequestStatus::PendingDirector)->count();
                    }
                    if ($user->isStaff()) {
                        return $query->whereIn('status', [
                            PurchaseRequestStatus::PendingFinance,
                            PurchaseRequestStatus::PendingAccounting,
                            PurchaseRequestStatus::PendingAccountingSpv,
                            PurchaseRequestStatus::PendingDirector,
                        ])->count();
                    }

                    return 0;
                })
                ->badgeColor('warning');
        }

        $tabs['rejected'] = Tab::make('Ditolak / Perlu Revisi')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', PurchaseRequestStatus::Rejected))
            ->badge(PurchaseRequest::query()->where('status', PurchaseRequestStatus::Rejected)->count())
            ->badgeColor('danger');

        $tabs['approved'] = Tab::make('Selesai')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', PurchaseRequestStatus::Approved))
            ->badge(PurchaseRequest::query()->where('status', PurchaseRequestStatus::Approved)->count())
            ->badgeColor('success');

        return $tabs;
    }
}
