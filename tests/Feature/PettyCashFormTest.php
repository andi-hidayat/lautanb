<?php

namespace Tests\Feature;

use App\Filament\Resources\PettyCashes\Pages\CreatePettyCash;
use App\Filament\Resources\PettyCashes\Schemas\PettyCashForm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PettyCashFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_form_defaults_missing_transaction_data(): void
    {
        $formData = PettyCashForm::prepareFormData([]);

        $this->assertSame([
            'number' => 1,
            'date' => now()->toDateString(),
            'pr_number' => null,
            'coa' => null,
            'description' => '',
            'debit' => 0,
            'credit' => 0,
            'balance' => 0,
        ], $formData['items'][0]);
    }

    public function test_transaction_changes_recalculate_running_balances(): void
    {
        $this->actingAs(User::factory()->create());

        $component = Livewire::test(CreatePettyCash::class)
            ->fillForm([
                'items' => [
                    [
                        'number' => 1,
                        'date' => '2026-10-28',
                        'description' => 'Kas masuk',
                        'debit' => 1000,
                        'credit' => 0,
                    ],
                    [
                        'number' => 2,
                        'date' => '2026-10-28',
                        'description' => 'Pembelian',
                        'debit' => 0,
                        'credit' => 250,
                    ],
                ],
            ]);

        $itemKeys = array_keys($component->get('data.items'));
        $firstItemKey = $itemKeys[0];
        $secondItemKey = $itemKeys[1];

        $component
            ->set("data.items.{$firstItemKey}.debit", 1500)
            ->assertSet("data.items.{$firstItemKey}.balance", 1500)
            ->assertSet("data.items.{$secondItemKey}.balance", 1250);
    }
}
