<?php

namespace Database\Seeders;

use App\Models\PurchaseRequestType;
use Illuminate\Database\Seeder;

class PurchaseRequestTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Defines purchase request type options per division. Divisions that are not
     * explicitly listed fall back to the shared 'default' set.
     */
    public function run(): void
    {
        /** @var array<string, array<array{name: string, sort_order: int}>> $typesByDivision */
        $default = [
            ['name' => 'Barang', 'sort_order' => 1],
            ['name' => 'Jasa', 'sort_order' => 2],
            ['name' => 'Pengembalian Dana', 'sort_order' => 3],
        ];

        $typesByDivision = [
            'default' => $default,

            'finance' => [
                ['name' => 'Pengembalian Dana', 'sort_order' => 1],
                ['name' => 'Pembayaran Pinjaman', 'sort_order' => 2],
                ['name' => 'Pemberian Pinjaman', 'sort_order' => 3],
                ['name' => 'Pembayaran Petty Cash', 'sort_order' => 4],
            ],

            'accounting' => [
                ['name' => 'Pembayaran Pajak Badan', 'sort_order' => 1],
                ['name' => 'Pembayaran Pajak', 'sort_order' => 2],
                ['name' => 'Pembayaran Jasa', 'sort_order' => 3],
                ['name' => 'Pembayaran Bunga Pajak', 'sort_order' => 4],
                ['name' => 'Pembayaran Tunggakan Pajak', 'sort_order' => 5],
                ['name' => 'Pengembalian Dana', 'sort_order' => 6],
            ],

            'legal' => [
                ['name' => 'Pajak', 'sort_order' => 1],
                ['name' => 'Jasa', 'sort_order' => 2],
                ['name' => 'Credit Note', 'sort_order' => 3],
                ['name' => 'Pembelian Aset Besar', 'sort_order' => 4],
                ['name' => 'Pengembalian Dana', 'sort_order' => 5],
            ],

            'it' => [
                ['name' => 'Barang', 'sort_order' => 1],
                ['name' => 'Jasa', 'sort_order' => 2],
                ['name' => 'Pengembalian Dana', 'sort_order' => 3],
            ],

            'secretary' => [
                ['name' => 'Barang', 'sort_order' => 1],
                ['name' => 'Jasa', 'sort_order' => 2],
                ['name' => 'Entertainment', 'sort_order' => 3],
                ['name' => 'Pengembalian Dana', 'sort_order' => 4],
            ],

            'hr' => $default,
            'ga' => $default,
            'hrga' => $default,
            'marketing' => $default,
            'sales' => $default,
            'logistic' => $default,
            'hse' => $default,
            'operational' => $default,
            'technical' => $default,
            'auditor' => $default,
            'bod' => $default,
        ];

        PurchaseRequestType::truncate();

        foreach ($typesByDivision as $division => $types) {
            foreach ($types as $type) {
                PurchaseRequestType::create([
                    'division' => $division,
                    'name' => $type['name'],
                    'sort_order' => $type['sort_order'],
                ]);
            }
        }
    }
}
