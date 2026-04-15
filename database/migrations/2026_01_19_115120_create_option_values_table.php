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
        Schema::create('option_values', static function (Blueprint $table) {
            $table->id();

            $table->foreignId('option_id')
                ->constrained('options') 
                ->onDelete('cascade');

            $table->string('value');
            $table->integer('sorted')->default(0);

            $table->timestamps();

            $table->index('option_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('option_values');
    }
};
