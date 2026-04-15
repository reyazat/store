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
        Schema::create('tax_classes', static function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->unique(); 
            $table->decimal('rate', 7, 4)->default(0.0);
            $table->enum('type', ['P', 'F'])->default('P'); // Percent / Fixed
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_classes');
    }
};
