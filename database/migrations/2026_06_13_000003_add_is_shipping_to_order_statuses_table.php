<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_statuses', function (Blueprint $table) {
            $table->boolean('is_shipping')->default(false)->after('is_completion');
        });

        // Mark 'shipped' status as is_shipping if it exists
        DB::table('order_statuses')
            ->where('key', 'shipped')
            ->update(['is_shipping' => true]);
    }

    public function down(): void
    {
        Schema::table('order_statuses', function (Blueprint $table) {
            $table->dropColumn('is_shipping');
        });
    }
};
