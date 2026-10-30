<?php

namespace Tests\Feature;

use App\Filament\Resources\PurchasePlans\Pages\CreatePurchasePlan;
use App\Models\PurchasePlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CreatePurchasePlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_create_page_renders_with_the_item_repeater(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreatePurchasePlan::class)
            ->assertOk()
            ->assertSee('Add Item')
            ->assertSee('Total Required Cost')
            ->assertSeeHtml('fi-align-end');
    }

    public function test_a_plan_can_be_created_with_its_costed_items(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreatePurchasePlan::class)
            ->fillForm([
                'purchase_item_name' => 'Pengadaan CCTV Balikpapan',
                'year' => '2026',
                'items' => [
                    [
                        'item_name' => 'Kamera CCTV',
                        'supplier_name' => 'PT Kamera',
                        'unit_price' => 1500000,
                        'quantity' => 2,
                    ],
                    [
                        'item_name' => 'Kabel UTP',
                        'supplier_name' => 'PT Kabel',
                        'unit_price' => 500000,
                        'quantity' => 2,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $plan = PurchasePlan::query()->sole();

        $this->assertSame('Pengadaan CCTV Balikpapan', $plan->purchase_item_name);
        $this->assertSame(4000000.0, (float) $plan->items()->sum('total_cost'));
    }
}
