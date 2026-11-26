<?php

namespace App\Filament\Resources\PurchaseRequests;

use App\Filament\Resources\PurchaseRequests\Pages\CreatePurchaseRequest;
use App\Filament\Resources\PurchaseRequests\Pages\EditPurchaseRequest;
use App\Filament\Resources\PurchaseRequests\Pages\ListPurchaseRequests;
use App\Filament\Resources\PurchaseRequests\Pages\ViewPurchaseRequest;
use App\Filament\Resources\PurchaseRequests\Schemas\PurchaseRequestForm;
use App\Filament\Resources\PurchaseRequests\Schemas\PurchaseRequestInfolist;
use App\Filament\Resources\PurchaseRequests\Tables\PurchaseRequestsTable;
use App\Models\PurchaseRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class PurchaseRequestResource extends Resource
{
    protected static ?string $model = PurchaseRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return PurchaseRequestForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PurchaseRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PurchaseRequestsTable::configure($table);
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return auth()->user()?->company === 'lba' ? 'LBA' : 'LBI';
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return $user !== null && collect(['lbi', 'lba'])
            ->contains(fn (string $company): bool => $user->canCreatePurchaseRequestsFor($company));
    }

    /**
     * Staff can review both companies; regular users see only their company's requests.
     *
     * @return Builder<PurchaseRequest>
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user?->isStaff()) {
            return $query;
        }

        $accessibleCompanies = collect(['lbi', 'lba'])
            ->filter(fn (string $company): bool => $user?->canAccessPurchaseRequestsFor($company) ?? false)
            ->values()
            ->all();

        return $accessibleCompanies === []
            ? $query->whereRaw('1 = 0')
            : $query->whereIn('company', $accessibleCompanies);
    }

    public static function canEdit(Model $record): bool
    {
        return parent::canEdit($record)
            && ! ($record instanceof PurchaseRequest && $record->isFullyApprovedAndSigned());
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPurchaseRequests::route('/'),
            'create' => CreatePurchaseRequest::route('/create'),
            'view' => ViewPurchaseRequest::route('/{record}'),
            'edit' => EditPurchaseRequest::route('/{record}/edit'),
        ];
    }
}
