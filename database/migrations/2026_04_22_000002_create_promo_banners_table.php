<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_banners', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('icon')->nullable(); // lucide icon name
            $table->string('image')->nullable();
            $table->string('link')->nullable();
            $table->string('cta_text')->nullable();
            $table->string('gradient_from')->nullable(); // tailwind color token e.g. primary-500
            $table->string('gradient_to')->nullable();
            $table->string('placement')->default('home'); // home, sidebar, etc.
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'placement', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_banners');
    }
};
