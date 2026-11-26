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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->enum('company', ['lba', 'lbi']);
            $table->enum('role', ['admin', 'superadmin', 'user'])->default('user');
            $table->enum('position', [
                'Non-Staff',
                'staff',
                'senior_staff',
                'supervisor',
                'assistant_manager',
                'manager',
                'senior_manager',
                'general_manager',
                'vice_president',
                'director',
                'president_director',
                'commissioner',
            ])->nullable();
            $table->enum('division', [
                'finance',
                'accounting',
                'hr',
                'ga',
                'it',
                'legal',
                'marketing',
                'sales',
                'secretary',
                'logistic',
                'hse',
                'operational',
                'crewing',
                'procurement',
                'technical',
                'auditor',
                'bod',
            ])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
