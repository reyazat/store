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
        Schema::create('option_product', static function (Blueprint $table) {
            $table->id();

            $table->foreignId('option_id')
                ->constrained('options')
                ->onDelete('cascade'); 

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();
                
            $table->tinyInteger('required')->default(0);
            $table->timestamps();

            $table->unique(['option_id', 'product_id']);
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('option_product');
    }
};
