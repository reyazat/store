<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use IamApi\Models\Organization;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Retroactively adds the `reviews.manage` permission to all existing organizations
 * and assigns it to owner, admin, editor, and user roles.
 *
 * Run once after deploying the reviews reply feature:
 *   php artisan db:seed --class=AddReviewsManagePermissionSeeder
 *
 * Safe to re-run: uses firstOrCreate / syncPermissions (idempotent).
 */
class AddReviewsManagePermissionSeeder extends Seeder
{
    private const PERMISSION = 'reviews.manage';
    private const DESCRIPTION = 'Moderate reviews (approve, reject, reply)';

    /** Roles that should receive reviews.manage (member is intentionally excluded). */
    private const ROLES_WITH_ACCESS = ['owner', 'admin', 'editor', 'user'];

    public function run(): void
    {
        $organizations = Organization::all();
        $total = $organizations->count();

        $this->command->info("Adding `" . self::PERMISSION . "` permission to {$total} organization(s)...");

        foreach ($organizations as $org) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);

            // 1. Ensure the permission exists for this org
            $permission = Permission::firstOrCreate(
                [
                    'name'            => self::PERMISSION,
                    'guard_name'      => 'api',
                    'organization_id' => $org->id,
                ],
                ['description' => self::DESCRIPTION],
            );

            // 2. Assign to each target role within this org
            foreach (self::ROLES_WITH_ACCESS as $roleName) {
                $role = Role::query()
                    ->where('name', $roleName)
                    ->where('guard_name', 'api')
                    ->where('organization_id', $org->id)
                    ->first();

                if (!$role) {
                    continue; // role doesn't exist in this org — skip
                }

                // givePermissionTo is idempotent (no duplicate pivot rows)
                if (!$role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }

            $this->command->line("   ✓ Org #{$org->id} ({$org->name})");
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command->info('Done. Permission cache cleared.');
    }
}
