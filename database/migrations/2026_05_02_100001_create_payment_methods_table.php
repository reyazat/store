<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('driver')->unique();            // card_to_card | zarinpal | ...
            $table->string('label');                       // display name
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(false);
            $table->integer('sort_order')->default(0);
            $table->json('config')->nullable();            // driver-specific config (encrypted)
            $table->string('success_order_status')->default('pending_confirmation'); // status to set on success
            $table->string('failure_order_status')->default('payment_failed');
            $table->string('cancel_order_status')->default('cancelled');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
