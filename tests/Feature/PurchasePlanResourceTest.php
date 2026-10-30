<?php

namespace Tests\Feature;

use App\Filament\Resources\PurchasePlans\Pages\CreatePurchasePlan;
use App\Filament\Resources\PurchasePlans\Pages\ListPurchasePlans;
use App\Filament\Resources\PurchasePlans\Pages\ViewPurchasePlan;
use App\Models\PurchasePlan;
use App\Models\PurchasePlanItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PurchasePlanResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_list_page_renders_the_plan_columns_and_records(): void
    {
        $plan = PurchasePlan::factory()
            ->has(PurchasePlanItem::factory(), 'items')
            ->create(['purchase_item_name' => 'Pengadaan Alat Tulis']);

        $this->actingAs(User::factory()->create());

        Livewire::test(ListPurchasePlans::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$plan])
            ->assertCanRenderTableColumn('purchase_item_name')
            ->assertCanRenderTableColumn('year')
            ->assertCanRenderTableColumn('items_count');
    }

    public function test_the_form_shows_the_total_required_cost_of_all_items(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreatePurchasePlan::class)
            ->fillForm([
                'items' => [
                    [
                        'item_name' => 'Pulpen',
                        'supplier_name' => 'PT Contoh',
                        'unit_price' => 5000,
                        'quantity' => 10,
                    ],
                    [
                        'item_name' => 'Kertas A4',
                        'supplier_name' => 'PT Kertas',
                        'unit_price' => 45000,
                        'quantity' => 4,
                    ],
                ],
            ])
            ->assertOk()
            ->assertSee('Rp 230.000');
    }

    public function test_it_creates_a_plan_together_with_its_items(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreatePurchasePlan::class)
            ->fillForm([
                'purchase_item_name' => 'Pengadaan Alat Tulis',
                'year' => '2026',
                'description' => 'Kebutuhan alat tulis kantor.',
                'purpose' => 'Menunjang operasional harian.',
                'items' => [
                    [
                        'item_name' => 'Pulpen',
                        'supplier_name' => 'PT Contoh',
                        'unit_price' => 5000,
                        'quantity' => 10,
                        'total_cost' => 50000,
                        'spesification' => 'Warna hitam, ukuran 0.5 mm',
                    ],
                    [
                        'item_name' => 'Kertas A4',
                        'supplier_name' => 'PT Kertas',
                        'unit_price' => 45000,
                        'quantity' => 4,
                        'total_cost' => 180000,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $plan = PurchasePlan::query()
            ->with('items')
            ->where('purchase_item_name', 'Pengadaan Alat Tulis')
            ->firstOrFail();

        $this->assertSame('2026', $plan->year);
        $this->assertCount(2, $plan->items);
        $this->assertEqualsCanonicalizing(
            ['Pulpen', 'Kertas A4'],
            $plan->items->pluck('item_name')->all(),
        );

        $this->assertDatabaseHas('purchase_plan_items', [
            'purchase_plan_id' => $plan->id,
            'item_name' => 'Pulpen',
            'supplier_name' => 'PT Contoh',
            'unit_price' => 5000,
            'quantity' => 10,
            'total_cost' => 50000,
            'spesification' => 'Warna hitam, ukuran 0.5 mm',
        ]);
    }

    public function test_the_view_page_renders_all_item_details_in_the_items_table(): void
    {
        $plan = PurchasePlan::factory()
            ->has(PurchasePlanItem::factory()->state([
                'item_name' => 'Pulpen',
                'supplier_name' => 'PT Contoh',
                'unit_price' => 5000,
                'quantity' => 10,
                'total_cost' => 50000,
                'spesification' => 'Warna hitam, ukuran 0.5 mm',
                'attachment' => 'items/pulpen.jpg',
            ]), 'items')
            ->create();

        $this->actingAs(User::factory()->create());

        Livewire::test(ViewPurchasePlan::class, ['record' => $plan->getRouteKey()])
            ->assertSee('Pulpen')
            ->assertSee('PT Contoh')
            ->assertSee('Warna hitam, ukuran 0.5 mm');
    }

    public function test_the_view_page_renders_the_rich_text_as_html_instead_of_escaped_tags(): void
    {
        $plan = PurchasePlan::factory()->create([
            'description' => '<p>Pemasangan CCTV di Balikpapan</p>',
            'purpose' => '<p>untuk kegiatan controlling</p>',
        ]);

        $this->actingAs(User::factory()->create());

        Livewire::test(ViewPurchasePlan::class, ['record' => $plan->getRouteKey()])
            ->assertOk()
            ->assertSeeHtml('<p>Pemasangan CCTV di Balikpapan</p>')
            ->assertDontSee('&lt;p&gt;', escape: false);
    }

    public function test_the_view_page_shows_the_total_required_cost_of_all_items(): void
    {
        $plan = PurchasePlan::factory()
            ->has(PurchasePlanItem::factory()->state([
                'item_name' => 'Pulpen',
                'unit_price' => 5000,
                'quantity' => 10,
                'total_cost' => 50000,
            ]), 'items')
            ->has(PurchasePlanItem::factory()->state([
                'item_name' => 'Kertas A4',
                'unit_price' => 45000,
                'quantity' => 4,
                'total_cost' => 180000,
            ]), 'items')
            ->create();

        $this->actingAs(User::factory()->create());

        Livewire::test(ViewPurchasePlan::class, ['record' => $plan->getRouteKey()])
            ->assertOk()
            ->assertSee('Total Required Cost')
            ->assertSee('Rp 230.000');
    }
}
