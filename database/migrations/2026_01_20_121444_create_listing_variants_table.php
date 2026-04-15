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
        Schema::create('listing_variants', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')
                ->constrained('listings')
                ->cascadeOnDelete();
            $table->foreignId('variant_id')
                ->constrained('product_variants')
                ->restrictOnDelete();
            $table->decimal('price_override', 15, 4)->nullable();
            $table->unsignedBigInteger('stock_quantity')->default(0);
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();

            $table->unique(['listing_id', 'variant_id']);
            $table->index('listing_id');
            $table->index('variant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listing_variants');
    }
};
