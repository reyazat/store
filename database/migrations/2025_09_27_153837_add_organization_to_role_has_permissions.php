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
        if (!Schema::hasColumn('role_has_permissions', 'organization_id')) {
            Schema::table('role_has_permissions', static function (Blueprint $t) {
                $t->foreignId('organization_id')->nullable()->after('permission_id')->constrained('organizations')->nullOnDelete();

                $t->index(['role_id','permission_id','organization_id'], 'rhp_role_perm_org_index');
            });
        }
    }

    public function down(): void {
        Schema::table('role_has_permissions', static function (Blueprint $t) {
            $t->dropIndex('rhp_role_perm_org_index');
            $t->dropConstrainedForeignId('organization_id');
        });
    }
};
