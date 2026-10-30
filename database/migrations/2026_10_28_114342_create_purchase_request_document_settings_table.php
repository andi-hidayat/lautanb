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
        Schema::create('purchase_request_document_settings', function (Blueprint $table) {
            $table->id();
            $table->string('division')->unique();
            $table->string('document_number');
            $table->string('revision_number');
            $table->date('effective_date');
            $table->string('number_prefix', 64)->unique();
            $table->unsignedInteger('next_number')->default(1);
            $table->unsignedSmallInteger('sequence_year')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_request_document_settings');
    }
};
