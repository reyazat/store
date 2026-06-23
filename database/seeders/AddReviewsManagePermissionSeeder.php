<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use IamApi\Models\Organization;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Retroactively adds `reviews.manage` and `coupons.manage` permissions to all
 * existing organizations and assigns them to owner, admin, editor, and user roles.
 *
 * Run once after deploying:
 *   php artisan db:seed --class=AddReviewsManagePermissionSeeder
 *
 * Safe to re-run: uses firstOrCreate + hasPermissionTo guard (idempotent).
 */
class AddReviewsManagePermissionSeeder extends Seeder
{
    /** Permissions to add with their descriptions. */
    private const PERMISSIONS = [
        'reviews.manage' => 'Moderate reviews (approve, reject, reply)',
        'coupons.manage' => 'Manage coupons (create, update, delete)',
    ];

    /** Roles that should receive these permissions (member is intentionally excluded). */
    private const ROLES_WITH_ACCESS = ['owner', 'admin', 'editor', 'user'];

    public function run(): void
    {
        $organizations = Organization::all();
        $total = $organizations->count();

        $permNames = implode(', ', array_keys(self::PERMISSIONS));
        $this->command->info("Adding [{$permNames}] to {$total} organization(s)...");

        foreach ($organizations as $org) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);

            foreach (self::PERMISSIONS as $permName => $description) {
                // 1. Ensure the permission exists for this org
                $permission = Permission::firstOrCreate(
                    [
                        'name'            => $permName,
                        'guard_name'      => 'api',
                        'organization_id' => $org->id,
                    ],
                    ['description' => $description],
                );

                // 2. Assign to each target role within this org
                foreach (self::ROLES_WITH_ACCESS as $roleName) {
                    $role = Role::query()
                        ->where('name', $roleName)
                        ->where('guard_name', 'api')
                        ->where('organization_id', $org->id)
                        ->first();

                    if (!$role) {
                        continue;
                    }

                    if (!$role->hasPermissionTo($permission)) {
                        $role->givePermissionTo($permission);
                    }
                }
            }

            $this->command->line("   ✓ Org #{$org->id} ({$org->name})");
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command->info('Done. Permission cache cleared.');
    }
}
