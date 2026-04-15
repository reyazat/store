<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        if (Schema::hasTable('activity_log') && !Schema::hasColumn('activity_log','organization_id')) {
            Schema::table('activity_log', static function (Blueprint $t) {
                $t->foreignId('organization_id')->nullable()->after('causer_type')->constrained('organizations')->nullOnDelete();
                $t->index(['organization_id','created_at']);
            });
        }
    }

    public function down(): void {
        if (Schema::hasTable('activity_log') && Schema::hasColumn('activity_log','organization_id')) {
            Schema::table('activity_log', static function (Blueprint $t) {
                $t->dropIndex(['organization_id','created_at']);
                $t->dropConstrainedForeignId('organization_id');
            });
        }
    }
};
