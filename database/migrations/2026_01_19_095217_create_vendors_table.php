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
        Schema::create('vendors', static function (Blueprint $table) {
            $table->id(); 
            $table->string('name'); 
            $table->string('slug')->unique(); 
            $table->text('description')->nullable(); 
            $table->tinyInteger('is_platform')->default(0); 
            $table->decimal('commission_rate', 5)->default(0.00);
            $table->tinyInteger('status')->default(1); 
            $table->enum('approval_status', ['pending', 'approved', 'suspended'])->default('pending');
            $table->softDeletes(); 
            $table->timestamps(); 

            $table->index('status');
            $table->index('approval_status');
            $table->index('is_platform');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
