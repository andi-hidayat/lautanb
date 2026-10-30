<?php

namespace Tests\Feature;

use App\Models\PurchasePlan;
use App\Models\PurchasePlanItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PurchasePlanMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_migration_creates_the_final_purchase_plan_schema_and_cascades_items(): void
    {
        $this->assertSame([
            'id',
            'purchase_item_name',
            'year',
            'description',
            'purpose',
            'created_at',
            'updated_at',
        ], Schema::getColumnListing('purchase_plans'));

        $this->assertSame([
            'id',
            'purchase_plan_id',
            'item_name',
            'unit_price',
            'quantity',
            'total_cost',
            'created_at',
            'updated_at',
            'supplier_name',
            'spesification',
            'attachment',
        ], Schema::getColumnListing('purchase_plan_items'));

        $plan = PurchasePlan::factory()->create();
        $item = PurchasePlanItem::factory()->for($plan, 'purchasePlan')->create();

        $plan->delete();

        $this->assertDatabaseMissing('purchase_plan_items', [
            'id' => $item->id,
        ]);
    }
}
