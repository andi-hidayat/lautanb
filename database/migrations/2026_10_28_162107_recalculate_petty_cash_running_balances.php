<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $balanceInCents = 0;

        foreach (DB::table('petty_cashes')->orderBy('number')->orderBy('id')->cursor() as $record) {
            $items = is_array($record->items)
                ? $record->items
                : (json_decode($record->items ?? '[]', true) ?: []);
            $debitInCents = 0;
            $creditInCents = 0;

            if ($items !== []) {
                foreach ($items as $index => $item) {
                    if (! is_array($item)) {
                        continue;
                    }

                    $itemDebitInCents = (int) round((float) ($item['debit'] ?? 0) * 100);
                    $itemCreditInCents = (int) round((float) ($item['credit'] ?? 0) * 100);
                    $debitInCents += $itemDebitInCents;
                    $creditInCents += $itemCreditInCents;
                    $balanceInCents += $itemDebitInCents - $itemCreditInCents;
                    $items[$index]['balance'] = $balanceInCents / 100;
                }
            } else {
                $debitInCents = (int) round((float) $record->debit * 100);
                $creditInCents = (int) round((float) $record->credit * 100);
                $balanceInCents += $debitInCents - $creditInCents;
            }

            DB::table('petty_cashes')
                ->where('id', $record->id)
                ->update([
                    'items' => $items !== [] ? json_encode($items, JSON_THROW_ON_ERROR) : $record->items,
                    'balance' => $balanceInCents / 100,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Corrected running balances cannot be reversed to the previous incorrect values.
    }
};
