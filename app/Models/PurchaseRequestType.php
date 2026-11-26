<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['division', 'name', 'sort_order', 'company'])]
class PurchaseRequestType extends Model
{
    /**
     * Get purchase request type options for a given division, keyed by name.
     *
     * @return array<string, string>
     */
    public static function optionsForDivision(string $division, ?string $company = null): array
    {
        $query = self::query()
            ->where('division', $division)
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($company !== null) {
            $query->where('company', $company);
        }

        return $query->pluck('name', 'name')->toArray();
    }

    /**
     * Scope to filter by division.
     *
     * @param  Builder<PurchaseRequestType>  $query
     */
    public function scopeForDivision(Builder $query, string $division): void
    {
        $query->where('division', $division);
    }
}
