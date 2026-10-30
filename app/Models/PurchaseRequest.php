<?php

namespace App\Models;

use App\Enums\SignaturePosition;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'title',
    'needed_at',
    'requested_by',
    'description',
    'purchase_tipe',
    'pay_to',
    'bank_account_number',
    'unit_price',
    'quantity',
    'total_price',
    'total_cost',
    'user_signature',
    'finance_signature',
    'accounting_signature',
    'accounting_spv_signature',
    'director_signature',
    'division',
    'document_number',
    'revision_number',
    'issue_date',
    'effective_date',
    'purchase_request_number',
])]
class PurchaseRequest extends Model
{
    /**
     * Get the user who requested the purchase.
     *
     * @return BelongsTo<User, $this>
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the items in the purchase request.
     *
     * @return HasMany<PurchaseRequestItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequestItem::class);
    }

    /**
     * Sum the total cost of the given raw item states.
     *
     * @param  array<int|string, array<string, mixed>>|null  $items
     */
    public static function calculateItemsTotalCost(?array $items): float
    {
        return (float) collect($items ?? [])
            ->sum(fn (array $item): float => PurchaseRequestItem::calculateTotalCost($item));
    }

    /**
     * Format an amount as Indonesian rupiah without decimals.
     */
    public static function formatRupiah(float $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }

    /**
     * Determine whether the given position has signed this request.
     */
    public function hasSignature(SignaturePosition $position): bool
    {
        return filled($this->getAttribute($position->signatureField()));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'needed_at' => 'date',
            'issue_date' => 'date',
            'effective_date' => 'date',
            'unit_price' => 'decimal:2',
            'quantity' => 'integer',
            'total_price' => 'decimal:2',
            'total_cost' => 'decimal:2',
        ];
    }
}
