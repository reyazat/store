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
        Schema::create('products', static function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('base_sku')->unique();
            $table->text('short_description')->nullable();
            $table->text('description')->nullable();
            $table->string('main_image');
            $table->foreignId('manufacturer_id')->nullable();
            $table->foreignId('tax_class_id');
            $table->decimal('weight', 10, 3);
            $table->decimal('length', 10, 3);
            $table->decimal('width', 10, 3);
            $table->decimal('height', 10, 3);
            $table->date('date_available')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->unsignedBigInteger('viewed')->default(0);
            $table->integer('sorted')->default(0);
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('status'); 
            $table->index('manufacturer_id');
            $table->index('tax_class_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
