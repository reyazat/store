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
        Schema::table('products', static function (Blueprint $table) {
            $table->foreign('manufacturer_id')
                ->references('id')
                ->on('manufacturers')
                ->nullOnDelete();
            
            $table->foreign('tax_class_id')
                ->references('id')
                ->on('tax_classes')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', static function (Blueprint $table) {
            $table->dropForeign(['manufacturer_id']);
            $table->dropForeign(['tax_class_id']);
        });
    }
};
