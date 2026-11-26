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
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('needed_at')->nullable();
            $table->string('purchase_tipe')->nullable();
            $table->string('pay_to')->nullable();
            $table->string('bank_account_number')->nullable();

            $table->decimal('unit_price', 10, 2)->nullable();
            $table->integer('quantity')->nullable();
            $table->decimal('total_price', 10, 2)->nullable();
            $table->decimal('total_cost', 10, 2)->nullable();
            $table->string('status')->default('pending_finance')->index();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->string('rejected_at_stage')->nullable();
            $table->unsignedInteger('approval_round')->default(1);
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('division')->nullable()->index();
            $table->string('company')->default('lbi')->index();
            $table->string('approval_flow')->default('lbi');

            $table->string('document_number')->nullable();
            $table->string('revision_number')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('effective_date')->nullable();
            $table->string('purchase_request_number')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('purchase_request_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained()->cascadeOnDelete();
            $table->string('position', 64);
            $table->string('division', 64);
            $table->longText('signature')->nullable();
            $table->timestamps();

            $table->unique(
                ['purchase_request_id', 'position', 'division'],
                'pr_signatures_request_position_division_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_request_signatures');
        Schema::dropIfExists('purchase_requests');
    }
};
