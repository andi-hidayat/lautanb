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
            $table->string('status')->default('pending_finance')->index()->after('total_cost');
            $table->longText('reviewer_signature')->nullable()->after('accounting_spv_signature');
            $table->text('rejection_reason')->nullable()->after('status');
            $table->foreignId('rejected_by')->nullable()->after('rejection_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            $table->string('rejected_at_stage')->nullable()->after('rejected_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table): void {
            $table->dropForeign(['rejected_by']);
            $table->dropColumn([
                'status',
                'reviewer_signature',
                'rejection_reason',
                'rejected_by',
                'rejected_at',
                'rejected_at_stage',
            ]);
        });
    }
};
