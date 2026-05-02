<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('driver');                    // card_to_card | zarinpal
            $table->string('gateway_ref')->nullable();   // authority from Zarinpal / receipt ref
            $table->decimal('amount', 15, 4);
            $table->enum('status', ['pending', 'paid', 'failed', 'cancelled'])->default('pending');
            $table->string('receipt_path')->nullable();  // card-to-card upload path
            $table->string('card_last_four')->nullable();
            $table->json('gateway_response')->nullable(); // raw response from gateway
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index('gateway_ref');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
