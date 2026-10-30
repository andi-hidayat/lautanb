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
                'director',
                'staff',
                'senior',
                'Non-Staff',
                'supervisor',
                'manager',
                'senior_manager',
                'general_manager',
            ])->nullable();
            $table->enum('division', [
                'finance',
                'accounting',
                'hr',
                'ga',
                'hrga',
                'it',
                'legal',
                'marketing',
                'sales',
                'secretary',
                'logistic',
                'hse',
                'operational',
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
