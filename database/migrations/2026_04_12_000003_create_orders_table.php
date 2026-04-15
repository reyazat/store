<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('address_id')->nullable()->constrained()->nullOnDelete();
            
            $table->enum('status', ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'])->default('pending');
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->string('payment_method')->nullable();
            
            $table->decimal('subtotal', 15, 4);
            $table->decimal('tax', 15, 4)->default(0);
            $table->decimal('shipping', 15, 4)->default(0);
            $table->decimal('discount', 15, 4)->default(0);
            $table->decimal('total', 15, 4);
            
            $table->text('notes')->nullable();
            $table->text('admin_notes')->nullable();
            
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('order_number');
            $table->index('status');
            $table->index('payment_status');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            
            $table->string('product_name');
            $table->string('vendor_name');
            $table->string('sku')->nullable();
            $table->integer('quantity');
            $table->decimal('price', 15, 4);
            $table->decimal('tax', 15, 4)->default(0);
            $table->decimal('subtotal', 15, 4);
            
            $table->timestamps();

            $table->index('order_id');
            $table->index('product_id');
            $table->index('vendor_id');
        });

        Schema::create('order_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone', 20);
            $table->string('email')->nullable();
            $table->string('country', 100);
            $table->string('province', 100);
            $table->string('city', 100);
            $table->text('address');
            $table->string('postal_code', 20);
            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_addresses');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
