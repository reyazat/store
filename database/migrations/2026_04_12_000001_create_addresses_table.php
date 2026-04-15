<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone', 20);
            $table->string('email')->nullable();
            $table->string('country', 100)->default('Iran');
            $table->string('province', 100);
            $table->string('city', 100);
            $table->text('address');
            $table->string('postal_code', 20);
            $table->text('notes')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('is_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
