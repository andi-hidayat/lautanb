<?php

namespace App\Filament\Resources\PettyCashes\Schemas;

use App\Models\PettyCash;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class PettyCashForm
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function prepareRecordData(array $data, ?int $startingNumber = null): array
    {
        $items = array_values($data['items'] ?? []);

        if ($items === []) {
            unset($data['opening_balance']);

            return $data;
        }

        $startingNumber ??= (int) ($items[0]['number'] ?? 1);
        $balanceInCents = (int) round((float) ($data['opening_balance'] ?? 0) * 100);
        $debitInCents = 0;
        $creditInCents = 0;

        foreach ($items as $index => $item) {
            $itemDebitInCents = (int) round((float) ($item['debit'] ?? 0) * 100);
            $itemCreditInCents = (int) round((float) ($item['credit'] ?? 0) * 100);
            $debitInCents += $itemDebitInCents;
            $creditInCents += $itemCreditInCents;
            $balanceInCents += $itemDebitInCents - $itemCreditInCents;

            $items[$index] = [
                ...$item,
                'number' => $startingNumber + $index,
                'date' => $item['date'] ?? $data['date'] ?? now()->toDateString(),
                'pr_number' => $itemDebitInCents > 0 ? ($item['pr_number'] ?? null) : null,
                'debit' => $itemDebitInCents / 100,
                'credit' => $itemCreditInCents / 100,
                'balance' => $balanceInCents / 100,
            ];
        }

        $firstItem = $items[0];

        $preparedData = [
            ...$data,
            'items' => $items,
            'number' => $firstItem['number'],
            'date' => $firstItem['date'],
            'pr_number' => $firstItem['pr_number'] ?? null,
            'coa' => $firstItem['coa'] ?? null,
            'description' => $firstItem['description'] ?? null,
            'debit' => $debitInCents / 100,
            'credit' => $creditInCents / 100,
            'balance' => $balanceInCents / 100,
        ];

        unset($preparedData['opening_balance']);

        return $preparedData;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function prepareFormData(array $data): array
    {
        $openingBalance = (float) ($data['opening_balance'] ?? 0);

        if (filled($data['items'] ?? null)) {
            $data = self::prepareRecordData($data);
            $data['opening_balance'] = $openingBalance;

            return $data;
        }

        $data['items'] = [[
            'number' => $data['number'] ?? 1,
            'date' => $data['date'] ?? now()->toDateString(),
            'pr_number' => $data['pr_number'] ?? null,
            'coa' => $data['coa'] ?? null,
            'description' => $data['description'] ?? '',
            'debit' => $data['debit'] ?? 0,
            'credit' => $data['credit'] ?? 0,
            'balance' => $data['balance'] ?? 0,
        ]];

        $data = self::prepareRecordData($data);
        $data['opening_balance'] = $openingBalance;

        return $data;
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Hidden::make('opening_balance')
                    ->default(fn (): float => PettyCash::balanceBefore()),
                Repeater::make('items') // Nama relasi atau array data
                    ->label('List of Petty Cash Transaction')
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set): void {
                        self::updateBalances($get, $set);
                    })
                    ->schema([
                        TextInput::make('number')
                            ->label('No')
                            ->disabled()
                            ->dehydrated()
                            ->default(fn (): int => PettyCash::nextTransactionNumber()),

                        DatePicker::make('date')
                            ->label('Tanggal')
                            ->required()
                            ->default(now()),

                        TextInput::make('pr_number')
                            ->label('PR Number')
                            ->visible(fn (Get $get): bool => (float) ($get('debit') ?? 0) > 0)
                            ->maxLength(255),

                        TextInput::make('coa')
                            ->label('COA')
                            ->maxLength(50),

                        TextInput::make('description')
                            ->label('Description')
                            ->required(),

                        TextInput::make('debit')
                            ->label('Debit')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->live(onBlur: false)
                            ->required(),

                        TextInput::make('credit')
                            ->label('Kredit')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->live(onBlur: false)
                            ->required(),

                        TextInput::make('balance')
                            ->label('Balance')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(fn (Get $get): float => (float) ($get('../../opening_balance') ?? 0))
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                    ])
                    ->columns(4)
                    ->defaultItems(1)
                    ->addable(false)
                    ->reorderable(false)
                    ->columnSpanFull(),
            ]);
    }

    public static function updateBalances(Get $get, Set $set): void
    {
        $balanceInCents = (int) round((float) ($get('opening_balance') ?? 0) * 100);
        $items = $get('items') ?? [];
        $firstItem = reset($items);
        $number = (int) ($firstItem['number'] ?? 1);

        foreach ($items as $itemKey => $item) {
            $debitInCents = (int) round((float) ($item['debit'] ?? 0) * 100);
            $creditInCents = (int) round((float) ($item['credit'] ?? 0) * 100);
            $balanceInCents += $debitInCents - $creditInCents;

            $set("items.{$itemKey}.number", $number);
            $set("items.{$itemKey}.balance", $balanceInCents / 100);
            $number++;
        }
    }
}
