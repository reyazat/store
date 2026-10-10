<?php

namespace IamApi\Database\Seeders;

use IamApi\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates the Super Admin user with all IAM permissions.
     * Super Admin is not tied to any organization and has full system access.
     */
    public function run(): void
    {
        $this->command->info('👑 Creating Super Admin...');

        // Use the configured auth User model so the user is created with the correct class
        $userClass = config('auth.providers.users.model', User::class);

        // Check if super admin already exists
        $existingSuperAdmin = $userClass::where('is_super_admin', true)->first();

        if ($existingSuperAdmin) {
            $this->command->warn("⚠️  Super Admin already exists: {$existingSuperAdmin->email}");
            return;
        }

        // Create Super Admin user
        $superAdmin = $userClass::create([
            'name' => 'System Super Administrator',
            'email' => env('SUPER_ADMIN_EMAIL', 'superadmin@iam-system.local'),
            'password' => Hash::make(env('SUPER_ADMIN_PASSWORD', 'SuperAdmin@123!Change')),
            'phone' => '+1-000-0000-0000',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'status' => 'active',
            'must_change_password' => env('APP_ENV') === 'production', // Force change in production
            'mfa_enabled' => env('APP_ENV') === 'production', // Require MFA in production
            'is_super_admin' => true,
            'password_changed_at' => now(),
            'password_expires_at' => now()->addDays(90),
            'last_login_at' => null,
            'last_login_ip' => null,
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);

        // Get all IAM permissions
        $iamPermissions = Permission::where('guard_name', 'api')
            ->whereNull('organization_id')
            ->where('name', 'like', 'iam.%')
            ->get();

        if ($iamPermissions->isEmpty()) {
            $this->command->warn('⚠️  No IAM permissions found. Run IamPermissionsSeeder first!');
        } else {
            // Super Admin doesn't need direct permissions
            // They have system-level access via is_super_admin flag
            // Permissions are only for organization-level authorization
            $this->command->info("   ℹ️  Super Admin has system-level access (no direct permissions needed)");
            $this->command->info("   ✓ Found {$iamPermissions->count()} IAM permissions in system");
        }

        $this->command->info('✅ Super Admin created successfully!');
        $this->command->line('');
        $this->command->line('   📧 Email: ' . $superAdmin->email);
        $this->command->line('   🔑 Password: ' . env('SUPER_ADMIN_PASSWORD', 'SuperAdmin@123!Change'));
        $this->command->line('');
        
        if (env('APP_ENV') === 'production') {
            $this->command->warn('   ⚠️  IMPORTANT: Change password and enable MFA on first login!');
        } else {
            $this->command->info('   ℹ️  Development mode: MFA not required');
        }
        
        $this->command->line('');
    }
}
