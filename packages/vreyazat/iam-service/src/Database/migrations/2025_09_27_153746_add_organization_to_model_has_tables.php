<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


// helpers:
if (!function_exists('Illuminate\Database\Schema\Blueprint::dropIndexIfExists')) {
    Blueprint::macro('dropIndexIfExists', function ($index) {
        try { $this->dropIndex($index); } catch (\Throwable $e) {}
    });
}

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        // model_has_roles
        if (!Schema::hasColumn('model_has_roles', 'organization_id')) {
            Schema::table('model_has_roles', function (Blueprint $t) {
                $t->foreignId('organization_id')->nullable()->after('role_id')->constrained('organizations')->nullOnDelete();

                $t->dropIndexIfExists('model_has_roles_role_model_type_model_id_index');
                $t->index(['role_id','model_id','model_type','organization_id'], 'mhr_role_model_org_index');
            });
        }

        // model_has_permissions
        if (!Schema::hasColumn('model_has_permissions', 'organization_id')) {
            Schema::table('model_has_permissions', function (Blueprint $t) {
                $t->foreignId('organization_id')->nullable()->after('permission_id')->constrained('organizations')->nullOnDelete();

                $t->dropIndexIfExists('model_has_permissions_permission_model_type_model_id_index');
                $t->index(['permission_id','model_id','model_type','organization_id'], 'mhp_perm_model_org_index');
            });
        }
    }

    public function down(): void {
        Schema::table('model_has_roles', function (Blueprint $t) {
            $t->dropIndex('mhr_role_model_org_index');
            $t->dropConstrainedForeignId('organization_id');
            $t->index(['role_id','model_type','model_id'], 'model_has_roles_role_model_type_model_id_index');
        });

        Schema::table('model_has_permissions', function (Blueprint $t) {
            $t->dropIndex('mhp_perm_model_org_index');
            $t->dropConstrainedForeignId('organization_id');
            $t->index(['permission_id','model_type','model_id'], 'model_has_permissions_permission_model_type_model_id_index');
        });
    }
};
