<?php

namespace App\Filament\Resources\PurchaseRequests\Pages;

use App\Actions\PurchaseRequests\IssuePurchaseRequestDocument;
use App\Enums\PurchaseRequestStatus;
use App\Enums\SignaturePosition;
use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatePurchaseRequest extends CreateRecord
{
    protected static string $resource = PurchaseRequestResource::class;

    protected static bool $canCreateAnother = false;

    public function getHeading(): string
    {
        return 'Create Request';
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): Model {
            $creator = Auth::user();

            abort_unless($creator instanceof User, 403);

            $company = $data['company'] ?? $creator->company;

            if (! is_string($company) || ! in_array($company, ['lbi', 'lba'], true)) {
                throw ValidationException::withMessages([
                    'data.company' => 'Perusahaan yang dipilih tidak valid.',
                ]);
            }

            $documentMetadata = app(IssuePurchaseRequestDocument::class)->forCreator($creator, $company);
            $firstApprovalPosition = SignaturePosition::from($documentMetadata['approval_steps'][1]['stage']);
            $firstApprovalStatus = PurchaseRequestStatus::forSignaturePosition($firstApprovalPosition);

            if ($firstApprovalStatus === null) {
                throw ValidationException::withMessages([
                    'data.company' => 'Tahap pertama pada alur persetujuan belum memiliki status yang valid. Hubungi admin.',
                ]);
            }

            $data = [
                ...$data,
                'company' => $company,
                'approval_flow' => $company === 'lba' ? 'lba' : 'lbi',
                'status' => $firstApprovalStatus,
                ...$documentMetadata,
            ];

            return parent::handleRecordCreation($data);
        }, attempts: 3);
    }
}
