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
        Schema::create('listings', static function (Blueprint $table) {
            $table->id(); 

            $table->foreignId('product_id')
                ->constrained('products')
                ->onDelete('cascade'); 

            $table->foreignId('vendor_id')
                ->constrained('vendors')
                ->onDelete('restrict'); 

            $table->enum('status', ['draft', 'active', 'paused', 'blocked'])
                ->default('draft');
            $table->decimal('base_price', 15, 4);
            $table->bigInteger('min_order_qty')->default(1);
            $table->timestamps();

            $table->unique(['product_id', 'vendor_id']);
            $table->index('vendor_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
