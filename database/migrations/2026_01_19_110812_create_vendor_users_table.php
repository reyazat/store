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
        Schema::create('vendor_users', static function (Blueprint $table) {
            $table->foreignId('vendor_id')
                ->constrained('vendors')
                ->onDelete('cascade');

            $table->foreignId('user_id')
                ->constrained('users') 
                ->onDelete('cascade');

            $table->enum('role', ['owner', 'manager', 'staff'])->default('staff');

            $table->primary(['vendor_id', 'user_id']);
            $table->timestamps();

            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_users');
    }
};
