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
        Schema::create('listing_customer_group_prices', static function (Blueprint $table) {

            
            $table->foreignId('listing_id')
                ->constrained('listings')
                ->cascadeOnDelete();
            $table->foreignId('customer_group_id')
                ->constrained('customer_groups')
                ->restrictOnDelete();

            $table->decimal('price', 15, 4);
            $table->unsignedBigInteger('min_qty')->default(1); 

            $table->timestamps();

            $table->primary(['listing_id', 'customer_group_id']);

            $table->index('customer_group_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listing_customer_group_prices');
    }
};
