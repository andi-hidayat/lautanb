<?php

namespace Tests\Feature;

use App\Filament\Resources\PettyCashes\Pages\CreatePettyCash;
use App\Filament\Resources\PettyCashes\Pages\EditPettyCash;
use App\Filament\Resources\PettyCashes\Pages\ListPettyCashes;
use App\Filament\Resources\PettyCashes\Pages\ViewPettyCash;
use App\Models\PettyCash;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PettyCashResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_transactions_continue_numbering_after_existing_items(): void
    {
        $this->actingAs(User::factory()->create());

        PettyCash::query()->create([
            'number' => 4,
            'date' => '2026-10-28',
            'description' => 'Existing transactions',
            'debit' => 100,
            'credit' => 25,
            'balance' => 75,
            'items' => [
                [
                    'number' => 4,
                    'date' => '2026-10-28',
                    'description' => 'Existing debit',
                    'debit' => 100,
                    'credit' => 0,
                    'balance' => 100,
                ],
                [
                    'number' => 5,
                    'date' => '2026-10-28',
                    'description' => 'Existing credit',
                    'debit' => 0,
                    'credit' => 25,
                    'balance' => 75,
                ],
            ],
        ]);

        $component = Livewire::test(CreatePettyCash::class);
        $firstItem = array_values($component->get('data.items'))[0];

        $this->assertSame('6', (string) $firstItem['number']);
        $this->assertSame(75.0, (float) $firstItem['balance']);

        $component
            ->fillForm([
                'items' => [
                    [
                        'number' => 6,
                        'date' => '2026-10-28',
                        'description' => 'New debit',
                        'debit' => 200,
                        'credit' => 0,
                        'balance' => 200,
                    ],
                    [
                        'number' => 7,
                        'date' => '2026-10-28',
                        'description' => 'New credit',
                        'debit' => 0,
                        'credit' => 50,
                        'balance' => 150,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $pettyCash = PettyCash::query()->orderByDesc('id')->firstOrFail();

        $this->assertSame([6, 7], array_column($pettyCash->items, 'number'));
        $this->assertSame([275.0, 225.0], array_map(
            fn (array $item): float => (float) $item['balance'],
            $pettyCash->items,
        ));
        $this->assertSame(225.0, (float) $pettyCash->balance);
    }

    public function test_edit_transaction_balance_continues_from_previous_record_balance(): void
    {
        $this->actingAs(User::factory()->create());

        PettyCash::query()->create([
            'number' => 1,
            'date' => '2026-10-28',
            'description' => 'Opening debit',
            'debit' => 2000000,
            'credit' => 0,
            'balance' => 2000000,
            'items' => [[
                'number' => 1,
                'date' => '2026-10-28',
                'description' => 'Opening debit',
                'debit' => 2000000,
                'credit' => 0,
                'balance' => 2000000,
            ]],
        ]);

        $pettyCash = PettyCash::query()->create([
            'number' => 2,
            'date' => '2026-10-28',
            'description' => 'Fuel expense',
            'debit' => 25000,
            'credit' => 100000,
            'balance' => 1925000,
            'items' => [
                [
                    'number' => 2,
                    'date' => '2026-10-28',
                    'description' => 'Fuel expense',
                    'debit' => 0,
                    'credit' => 100000,
                    'balance' => 1900000,
                ],
                [
                    'number' => 3,
                    'date' => '2026-10-28',
                    'description' => 'Meal allowance refund',
                    'debit' => 25000,
                    'credit' => 0,
                    'balance' => 1925000,
                ],
            ],
        ]);

        $nextPettyCash = PettyCash::query()->create([
            'number' => 4,
            'date' => '2026-10-28',
            'description' => 'Office supplies',
            'debit' => 0,
            'credit' => 50000,
            'balance' => 1850000,
            'items' => [[
                'number' => 4,
                'date' => '2026-10-28',
                'description' => 'Office supplies',
                'debit' => 0,
                'credit' => 50000,
                'balance' => 1850000,
            ]],
        ]);

        $lastPettyCash = PettyCash::query()->create([
            'number' => 5,
            'date' => '2026-10-28',
            'description' => 'Stationery refund',
            'debit' => 25000,
            'credit' => 0,
            'balance' => 1875000,
            'items' => [[
                'number' => 5,
                'date' => '2026-10-28',
                'description' => 'Stationery refund',
                'debit' => 25000,
                'credit' => 0,
                'balance' => 1875000,
            ]],
        ]);

        $component = Livewire::test(EditPettyCash::class, ['record' => $pettyCash->getRouteKey()]);
        $itemKeys = array_keys($component->get('data.items'));
        $itemKey = $itemKeys[0];
        $nextItemKey = $itemKeys[1];

        $component
            ->assertSet('data.opening_balance', 2000000.0)
            ->assertSet("data.items.{$itemKey}.balance", 1900000.0)
            ->assertSet("data.items.{$nextItemKey}.balance", 1925000.0)
            ->set("data.items.{$itemKey}.credit", 150000)
            ->assertSet("data.items.{$itemKey}.balance", 1850000.0)
            ->assertSet("data.items.{$nextItemKey}.balance", 1875000.0)
            ->call('save')
            ->assertHasNoFormErrors();

        $pettyCash->refresh();
        $nextPettyCash->refresh();
        $lastPettyCash->refresh();

        $this->assertSame(1850000.0, (float) $pettyCash->items[0]['balance']);
        $this->assertSame(1875000.0, (float) $pettyCash->items[1]['balance']);
        $this->assertSame(1875000.0, (float) $pettyCash->balance);
        $this->assertSame(1825000.0, (float) $nextPettyCash->items[0]['balance']);
        $this->assertSame(1825000.0, (float) $nextPettyCash->balance);
        $this->assertSame(1850000.0, (float) $lastPettyCash->items[0]['balance']);
        $this->assertSame(1850000.0, (float) $lastPettyCash->balance);
    }

    public function test_a_petty_cash_can_be_created_with_transaction_rows(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreatePettyCash::class)
            ->assertFormFieldExists('items')
            ->assertDontSee('Add Transcation Column')
            ->fillForm([
                'items' => [
                    [
                        'number' => 8,
                        'date' => '2026-10-28',
                        'pr_number' => 'PR-2026-001',
                        'coa' => '5101',
                        'description' => 'Kas operasional',
                        'debit' => 10000,
                        'credit' => 0,
                        'balance' => 10000,
                    ],
                    [
                        'number' => 13,
                        'date' => '2026-10-28',
                        'pr_number' => 'PR-2026-002',
                        'coa' => '5102',
                        'description' => 'Pembelian ATK',
                        'debit' => 0,
                        'credit' => 2500,
                        'balance' => 7500,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('petty_cashes', [
            'number' => 1,
            'date' => '2026-10-28',
            'description' => 'Kas operasional',
            'debit' => 10000,
            'balance' => 7500,
        ]);

        $pettyCash = PettyCash::query()->sole();

        $this->assertSame('Pembelian ATK', $pettyCash->items[1]['description']);
        $this->assertSame(10000.0, (float) $pettyCash->items[0]['debit']);
        $this->assertSame([1, 2], array_column($pettyCash->items, 'number'));
        $this->assertSame('PR-2026-001', $pettyCash->items[0]['pr_number']);
        $this->assertNull($pettyCash->items[1]['pr_number']);
        $this->assertSame('5102', $pettyCash->items[1]['coa']);
        $this->assertSame(2500.0, (float) $pettyCash->items[1]['credit']);
        $this->assertSame(7500.0, (float) $pettyCash->items[1]['balance']);
        $this->assertSame(7500.0, (float) $pettyCash->balance);

        Livewire::test(ListPettyCashes::class)
            ->assertOk()
            ->assertSee('Kas operasional')
            ->assertSee('PR-2026-001');

        Livewire::test(ViewPettyCash::class, ['record' => $pettyCash->getRouteKey()])
            ->assertOk()
            ->assertDontSee('Pembelian ATK')
            ->assertDontSee('Transactions');

        Livewire::test(EditPettyCash::class, ['record' => $pettyCash->getRouteKey()])
            ->assertFormFieldExists('items')
            ->assertDontSee('Add Transcation Column');
    }

    public function test_a_petty_cash_record_can_be_viewed_with_its_transaction_details(): void
    {
        $this->actingAs(User::factory()->create());

        $pettyCash = PettyCash::query()->create([
            'number' => 1,
            'date' => '2026-10-28',
            'pr_number' => 'PR-2026-001',
            'coa' => '5101',
            'description' => 'Kas operasional',
            'debit' => 10000,
            'credit' => 2500,
            'balance' => 7500,
        ]);

        Livewire::test(ViewPettyCash::class, ['record' => $pettyCash->getRouteKey()])
            ->assertOk()
            ->assertSee('PR-2026-001')
            ->assertSee('5101')
            ->assertSee('Kas operasional')
            ->assertSee('Debit')
            ->assertSee('Credit')
            ->assertSee('Balance');

        Livewire::test(ListPettyCashes::class)
            ->assertOk()
            ->assertSee('Kas operasional')
            ->assertSee('PR-2026-001')
            ->assertSee('View');
    }
}
