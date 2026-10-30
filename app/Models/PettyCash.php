<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PettyCash extends Model
{
    use HasFactory;

    protected $table = 'petty_cashes';

    protected $fillable = [
        'number',
        'date',
        'pr_number',
        'coa',
        'description',
        'debit',
        'credit',
        'balance',
        'items',
    ];

    public static function nextTransactionNumber(): int
    {
        $highestNumber = 0;

        static::query()
            ->select(['id', 'number', 'items'])
            ->chunkById(100, function ($pettyCashes) use (&$highestNumber): void {
                foreach ($pettyCashes as $pettyCash) {
                    $highestNumber = max($highestNumber, (int) $pettyCash->number);

                    foreach ($pettyCash->items ?? [] as $item) {
                        if (is_numeric($item['number'] ?? null)) {
                            $highestNumber = max($highestNumber, (int) $item['number']);
                        }
                    }
                }
            });

        return $highestNumber + 1;
    }

    public static function balanceBefore(?int $number = null): float
    {
        return (float) (static::query()
            ->when($number !== null, fn ($query) => $query->where('number', '<', $number))
            ->orderByDesc('number')
            ->value('balance') ?? 0);
    }

    public static function recalculateRunningBalances(): void
    {
        DB::transaction(function (): void {
            $balanceInCents = 0;

            foreach (static::query()->orderBy('number')->orderBy('id')->get() as $pettyCash) {
                $items = $pettyCash->items ?? [];

                if ($items === []) {
                    $debitInCents = (int) round((float) $pettyCash->debit * 100);
                    $creditInCents = (int) round((float) $pettyCash->credit * 100);
                    $balanceInCents += $debitInCents - $creditInCents;
                } else {
                    foreach ($items as $index => $item) {
                        if (! is_array($item)) {
                            continue;
                        }

                        $debitInCents = (int) round((float) ($item['debit'] ?? 0) * 100);
                        $creditInCents = (int) round((float) ($item['credit'] ?? 0) * 100);
                        $balanceInCents += $debitInCents - $creditInCents;
                        $items[$index]['balance'] = $balanceInCents / 100;
                    }

                    $pettyCash->items = $items;
                }

                $pettyCash->balance = $balanceInCents / 100;
                $pettyCash->saveQuietly();
            }
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function transactionItems(): array
    {
        if (is_array($this->items) && $this->items !== []) {
            return $this->items;
        }

        return [[
            'number' => $this->number,
            'date' => $this->date,
            'pr_number' => $this->pr_number,
            'coa' => $this->coa,
            'description' => $this->description,
            'debit' => $this->debit,
            'credit' => $this->credit,
            'balance' => $this->balance,
        ]];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'items' => 'array',
        ];
    }
}
