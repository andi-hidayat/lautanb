<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('purchase_plans', function (Blueprint $table): void {
            $table->id();
            $table->string('purchase_item_name');
            $table->string('year');
            $table->text('description')->nullable();
            $table->text('purpose')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_plan_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_plan_id')->constrained()->cascadeOnDelete();
            $table->string('item_name');
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->integer('quantity')->nullable();
            $table->decimal('total_cost', 15, 2)->nullable();
            $table->timestamps();
            $table->string('supplier_name')->nullable();
            $table->longText('spesification')->nullable();
            $table->string('attachment')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_plan_items');
        Schema::dropIfExists('purchase_plans');
    }
};
