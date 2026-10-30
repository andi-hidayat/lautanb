<?php

namespace App\Models;

use Database\Factories\PurchaseRequestItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'purchase_request_id',
    'item_required_name',
    'unit_price',
    'quantity',
    'reference',
    'total_cost',
])]
class PurchaseRequestItem extends Model
{
    /** @use HasFactory<PurchaseRequestItemFactory> */
    use HasFactory;

    /**
     * Get the purchase request that owns the item.
     *
     * @return BelongsTo<PurchaseRequest, $this>
     */
    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saved(function (self $item): void {
            $item->syncPurchaseRequestTotalCost();
        });

        static::deleted(function (self $item): void {
            $item->syncPurchaseRequestTotalCost();
        });
    }

    /**
     * Synchronize the parent purchase request's total cost.
     */
    public function syncPurchaseRequestTotalCost(): void
    {
        if ($this->purchase_request_id) {
            $totalCost = (float) static::query()
                ->where('purchase_request_id', $this->purchase_request_id)
                ->sum('total_cost');

            PurchaseRequest::query()
                ->where('id', $this->purchase_request_id)
                ->update(['total_cost' => $totalCost]);
        }
    }

    /**
     * Calculate the total cost of a single raw item state.
     *
     * @param  array<string, mixed>  $item
     */
    public static function calculateTotalCost(array $item): float
    {
        return (float) ($item['unit_price'] ?? 0) * (float) ($item['quantity'] ?? 0);
    }
}
