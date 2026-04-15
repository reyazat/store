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
        Schema::create('verification_tokens', static function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('type'); // email_verification|phone_verification|password_reset
            $t->string('token')->unique();
            $t->boolean('used')->default(false);
            $t->timestamp('expires_at')->nullable();
            $t->timestamps();

            $t->index(['user_id', 'type', 'used', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verification_tokens');
    }
};
