<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();       // machine key: pending, processing, ...
            $table->string('label');               // Persian display label
            $table->string('color')->default('#6b7280'); // Tailwind-compatible hex
            $table->boolean('is_cancellation')->default(false);
            $table->boolean('is_completion')->default(false);
            $table->integer('sort_order')->default(0);
            $table->boolean('notify_customer')->default(false);
            $table->text('notification_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_statuses');
    }
};
