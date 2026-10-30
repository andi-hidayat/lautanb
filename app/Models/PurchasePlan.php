<?php

namespace App\Models;

use Database\Factories\PurchasePlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'purchase_item_name',
    'description',
    'purpose',
    'year',
])]
class PurchasePlan extends Model
{
    /** @use HasFactory<PurchasePlanFactory> */
    use HasFactory;

    /**
     * Get the items required by the purchase plan.
     *
     * @return HasMany<PurchasePlanItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchasePlanItem::class);
    }

    /**
     * Store a description that has no visible content as null.
     *
     * @return Attribute<never, ?string>
     */
    protected function description(): Attribute
    {
        return Attribute::set(fn (?string $value): ?string => static::normalizeRichText($value));
    }

    /**
     * Store a purpose that has no visible content as null.
     *
     * @return Attribute<never, ?string>
     */
    protected function purpose(): Attribute
    {
        return Attribute::set(fn (?string $value): ?string => static::normalizeRichText($value));
    }

    /**
     * Sum the total cost of the given raw item states.
     *
     * @param  array<int|string, array<string, mixed>>|null  $items
     */
    public static function calculateItemsTotalCost(?array $items): float
    {
        return (float) collect($items ?? [])
            ->sum(fn (array $item): float => PurchasePlanItem::calculateTotalCost($item));
    }

    /**
     * Format an amount as Indonesian rupiah without decimals.
     */
    public static function formatRupiah(float $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }

    /**
     * Turn the empty paragraphs left behind by the rich editor into null.
     */
    protected static function normalizeRichText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        // Content that renders a box, an image, or a rule is never considered empty.
        if (preg_match('/<(img|hr|iframe|video|audio|svg|table)\b/i', $value) === 1) {
            return $value;
        }

        $text = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Drop whitespace, non-breaking spaces, and zero-width characters.
        $text = (string) preg_replace('/[\s\x{00A0}\x{200B}\x{FEFF}]+/u', '', $text);

        return $text === '' ? null : $value;
    }
}
