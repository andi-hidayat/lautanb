<?php

namespace App\Filament\Resources\PurchaseRequests\Pages;

use App\Actions\PurchaseRequests\IssuePurchaseRequestDocument;
use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreatePurchaseRequest extends CreateRecord
{
    protected static string $resource = PurchaseRequestResource::class;

    public function getHeading(): string
    {
        return 'Create Request';
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): Model {
            $creator = Auth::user();

            abort_unless($creator instanceof User, 403);

            $data = [
                ...$data,
                ...app(IssuePurchaseRequestDocument::class)->forCreator($creator),
            ];

            return parent::handleRecordCreation($data);
        }, attempts: 3);
    }
}
