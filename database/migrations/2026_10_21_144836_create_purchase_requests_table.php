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
            $table->date('needed_at')->nullable();
            $table->text('description')->nullable();
            $table->string('purchase_tipe')->nullable();
            $table->string('pay_to')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->longText('user_signature')->nullable();
            $table->longText('finance_signature')->nullable();
            $table->longText('accounting_signature')->nullable();
            $table->longText('accounting_spv_signature')->nullable();
            $table->longText('director_signature')->nullable();
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->integer('quantity')->nullable();
            $table->decimal('total_price', 10, 2)->nullable();
            $table->decimal('total_cost', 10, 2)->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('division')->nullable()->index();
            $table->string('document_number')->nullable();
            $table->string('revision_number')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('effective_date')->nullable();
            $table->string('purchase_request_number')->nullable()->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_requests');
    }
};
