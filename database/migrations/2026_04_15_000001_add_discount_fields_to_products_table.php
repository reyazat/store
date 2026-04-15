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
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('discount_percent')->nullable()->after('status');
            $table->timestamp('discount_start_at')->nullable()->after('discount_percent');
            $table->timestamp('discount_end_at')->nullable()->after('discount_start_at');
            
            $table->index('discount_percent');
            $table->index('discount_end_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['discount_percent']);
            $table->dropIndex(['discount_end_at']);
            $table->dropColumn(['discount_percent', 'discount_start_at', 'discount_end_at']);
        });
    }
};
