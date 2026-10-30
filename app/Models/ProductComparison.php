<?php

namespace App\Models;

use Database\Factories\ProductComparisonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable([
    'title',
    'description',
    'products',
])]
class ProductComparison extends Model
{
    /** @use HasFactory<ProductComparisonFactory> */
    use HasFactory;

    /**
     * @return array<int, array{name: string, values: array<int, string>}>
     */
    public function comparisonMatrix(): array
    {
        $products = array_values($this->products ?? []);
        $criteria = [];

        foreach ($products as $productIndex => $product) {
            if (! is_array($product)) {
                continue;
            }

            $specifications = $product['specifications'] ?? [];

            if (! is_array($specifications)) {
                continue;
            }

            foreach ($specifications as $specification) {
                if (! is_array($specification)) {
                    continue;
                }

                $name = $specification['name'] ?? null;

                if (! is_string($name)) {
                    continue;
                }

                $name = trim($name);

                if ($name === '') {
                    continue;
                }

                $key = Str::lower($name);
                $criteria[$key] ??= [
                    'name' => $name,
                    'values' => array_fill(0, count($products), '-'),
                ];
                $value = $specification['value'] ?? null;
                $criteria[$key]['values'][$productIndex] = is_scalar($value) && filled($value)
                    ? (string) $value
                    : '-';
            }
        }

        return array_values($criteria);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'products' => 'array',
        ];
    }
}
