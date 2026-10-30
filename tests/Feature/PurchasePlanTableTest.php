<?php

namespace Tests\Feature;

use App\Filament\Resources\PurchasePlans\Pages\ListPurchasePlans;
use App\Models\PurchasePlan;
use App\Models\PurchasePlanItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PurchasePlanTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_list_table_shows_the_summed_total_cost_of_every_item(): void
    {
        $this->actingAs(User::factory()->create());

        $plan = PurchasePlan::factory()->create();

        PurchasePlanItem::factory()->for($plan, 'purchasePlan')->create([
            'unit_price' => 1500000,
            'quantity' => 2,
            'total_cost' => 3000000,
        ]);

        PurchasePlanItem::factory()->for($plan, 'purchasePlan')->create([
            'unit_price' => 500000,
            'quantity' => 2,
            'total_cost' => 1000000,
        ]);

        Livewire::test(ListPurchasePlans::class)
            ->assertCanSeeTableRecords([$plan])
            ->assertSee('Total Cost')
            ->assertSee('Rp 4.000.000')
            ->sortTable('items_sum_total_cost');
    }
}
