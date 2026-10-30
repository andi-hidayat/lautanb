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
        Schema::table('tickets', function (Blueprint $table): void {
            $table->foreignId('user_id')->after('id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable()->after('status');
            $table->dropColumn(['user', 'department']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->string('user')->nullable()->after('title');
            $table->string('department')->nullable()->after('user');
            $table->dropConstrainedForeignId('user_id');
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropColumn('resolved_at');
        });
    }
};
