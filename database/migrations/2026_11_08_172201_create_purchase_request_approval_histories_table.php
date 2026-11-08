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
        if (! Schema::hasTable('purchase_request_approval_histories')) {
            Schema::create('purchase_request_approval_histories', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('purchase_request_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('round');
                $table->string('stage');
                $table->string('action');
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->longText('signature')->nullable();
                $table->text('comments')->nullable();
                $table->timestamp('acted_at');
                $table->timestamps();
            });
        }

        if (! Schema::hasIndex('purchase_request_approval_histories', 'pr_approval_histories_request_round_idx')) {
            Schema::table('purchase_request_approval_histories', function (Blueprint $table): void {
                $table->index(['purchase_request_id', 'round'], 'pr_approval_histories_request_round_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_request_approval_histories');
    }
};
