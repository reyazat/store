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
        Schema::create('categories', static function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name', 255)->unique();
            $table->string('slug', 255)->unique();

            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('meta_title', 255)->nullable();
            $table->text('meta_description')->nullable();
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('parent_id')->references('id')->on('categories')->nullOnDelete();

            $table->index('parent_id');
            $table->index('status');
            $table->index('slug');


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
