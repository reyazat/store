<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->foreignId('coupon_id')
                ->nullable()
                ->after('session_id')
                ->constrained('coupons')
                ->nullOnDelete();

            $table->decimal('coupon_discount', 15, 4)
                ->default(0)
                ->after('coupon_id')
                ->comment('Calculated discount amount from the applied coupon');
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropForeign(['coupon_id']);
            $table->dropColumn(['coupon_id', 'coupon_discount']);
        });
    }
};
