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
        Schema::create('variant_option_values', static function (Blueprint $table) {

            $table->foreignId('variant_id')
                ->constrained('product_variants')
                ->onDelete('cascade');

            $table->foreignId('option_id')
                ->constrained('options')
                ->onDelete('restrict'); 

            
            $table->foreignId('option_value_id')
                ->constrained('option_values')    
                ->onDelete('restrict');


            $table->timestamps();

            $table->unique(['variant_id', 'option_id']);
            $table->unique(['variant_id', 'option_value_id']);
            $table->index('option_value_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variant_option_values');
    }
};
