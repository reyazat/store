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
        Schema::create('otps', static function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('purpose');  // login|2fa|verify_email|verify_phone|password_reset
            $t->string('channel');  // email|sms
            $t->string('code_hash');
            $t->timestamp('expires_at');
            $t->unsignedTinyInteger('attempts')->default(0);
            $t->unsignedTinyInteger('max_attempts')->default(5);
            $t->boolean('used')->default(false);
            $t->timestamps();

            $t->index(['user_id','purpose','channel','expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otps');
    }
};
