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
        Schema::table('purchase_requests', function (Blueprint $table): void {
            // Drop redundant item columns moved to purchase_request_items
            $table->dropColumn(['unit_price', 'quantity', 'total_price']);

            // Separate bank_account_number into bank_merchant_name and account_number as used by forms
            $table->string('bank_merchant_name')->nullable()->after('pay_to');
            $table->string('account_number')->nullable()->after('bank_merchant_name');
            $table->dropColumn('bank_account_number');
        });

        Schema::table('tickets', function (Blueprint $table): void {
            // Add indexes for frequent status/priority queries
            $table->index('status');
            $table->index('priority');
        });

        Schema::table('petty_cashes', function (Blueprint $table): void {
            // Add indexes for ledger ordering and balance recalculations
            $table->index('number');
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('petty_cashes', function (Blueprint $table): void {
            $table->dropIndex(['number']);
            $table->dropIndex(['date']);
        });

        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropIndex(['status']);
            $table->dropIndex(['priority']);
        });

        Schema::table('purchase_requests', function (Blueprint $table): void {
            $table->string('bank_account_number')->nullable()->after('pay_to');
            $table->dropColumn(['bank_merchant_name', 'account_number']);
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->integer('quantity')->nullable();
            $table->decimal('total_price', 10, 2)->nullable();
        });
    }
};
