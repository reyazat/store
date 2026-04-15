<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

if (!Blueprint::hasMacro('dropUniqueIfExists')) {
    Blueprint::macro('dropUniqueIfExists', function ($index) {
        try {
            $this->dropUnique($index);
        } catch (Throwable $e) {
        }
    });
}
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        // roles
        if (!Schema::hasColumn('roles','organization_id')) {
            Schema::table('roles', static function (Blueprint $t) {
                $t->foreignId('organization_id')->nullable()->after('guard_name')->constrained('organizations')->nullOnDelete();
                $t->dropUniqueIfExists('roles_name_guard_name_unique');
                $t->unique(['organization_id','name','guard_name'], 'roles_org_name_guard_unique');
            });
        }

        // permissions
        if (!Schema::hasColumn('permissions','organization_id')) {
            Schema::table('permissions', static function (Blueprint $t) {
                $t->foreignId('organization_id')->nullable()->after('guard_name')->constrained('organizations')->nullOnDelete();
                $t->dropUniqueIfExists('permissions_name_guard_name_unique');
                $t->unique(['organization_id','name','guard_name'], 'perms_org_name_guard_unique');
            });
        }
    }

    public function down(): void {
        Schema::table('roles', static function (Blueprint $t) {
            $t->dropUnique('roles_org_name_guard_unique');
            $t->dropConstrainedForeignId('organization_id');
            $t->unique(['name','guard_name']);
        });
        Schema::table('permissions', static function (Blueprint $t) {
            $t->dropUnique('perms_org_name_guard_unique');
            $t->dropConstrainedForeignId('organization_id');
            $t->unique(['name','guard_name']);
        });
    }
};
