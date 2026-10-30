<?php

namespace App\Models;

use Database\Factories\PurchasePlanItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'purchase_plan_id',
    'item_name',
    'supplier_name',
    'unit_price',
    'quantity',
    'total_cost',
    'spesification',
    'attachment',
])]
class PurchasePlanItem extends Model
{
    /** @use HasFactory<PurchasePlanItemFactory> */
    use HasFactory;

    /**
     * Get the purchase plan that owns the item.
     *
     * @return BelongsTo<PurchasePlan, $this>
     */
    public function purchasePlan(): BelongsTo
    {
        return $this->belongsTo(PurchasePlan::class);
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
