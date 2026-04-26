<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Drop the organization_user pivot table since we now use organization_id directly on users table
     */
    public function up(): void
    {
        // Drop the pivot table if it exists
        if (Schema::hasTable('organization_user')) {
            Schema::dropIfExists('organization_user');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate the pivot table if needed for rollback
        Schema::create('organization_user', function (Blueprint $table) {
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'user_id']);
        });
    }
};
