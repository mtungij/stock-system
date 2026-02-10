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
        Schema::table('users', function (Blueprint $table) {
              $table->foreignId('role_id')->default(1)->after('id')->constrained('roles')->cascadeOnDelete();

    
            $table->foreignId('branch_id')->after('role_id')->nullable()->constrained('branches')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
               // Drop foreign keys first
            $table->dropForeign(['role_id']);
            $table->dropForeign(['branch_id']);

            // Drop columns
            $table->dropColumn(['role_id', 'branch_id']);
        });
    }
};
