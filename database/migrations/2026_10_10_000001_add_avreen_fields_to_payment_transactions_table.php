<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->string('client_txn_id', 50)->nullable()->unique()->after('driver');
            $table->string('gateway_token')->nullable()->index()->after('client_txn_id');
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropUnique(['client_txn_id']);
            $table->dropIndex(['gateway_token']);
            $table->dropColumn(['client_txn_id', 'gateway_token']);
        });
    }
};
