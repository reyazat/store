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
        Schema::create('vendor_policies', static function (Blueprint $table) {
            $table->foreignId('vendor_id')
                ->primary()
                ->constrained('vendors')
                ->onDelete('cascade');

           
            $table->foreignId('tax_class_id')
                ->nullable()
                ->constrained('tax_classes')
                ->nullOnDelete();

            $table->text('return_policy'); 
            $table->text('shipping_policy'); 
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_policies');
    }
};
