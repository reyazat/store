<?php

namespace Database\Seeders;

use IamApi\Repositories\Contracts\UserRepositoryInterface;
use IamApi\Repositories\Contracts\OrganizationRepositoryInterface;
use IamApi\Services\PasswordPolicyService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    protected UserRepositoryInterface $userRepository;
    protected OrganizationRepositoryInterface $organizationRepository;
    protected PasswordPolicyService $passwordPolicyService;

    public function __construct(
        UserRepositoryInterface $userRepository,
        OrganizationRepositoryInterface $organizationRepository,
        PasswordPolicyService $passwordPolicyService
    ) {
        $this->userRepository = $userRepository;
        $this->organizationRepository = $organizationRepository;
        $this->passwordPolicyService = $passwordPolicyService;
    }

    /**
     * Run the database seeds.
     * 
     * Note: This seeder is for PRODUCTION environment.
     * Super Admin is created by SuperAdminSeeder (called before this).
     * This seeder only creates a sample organization with basic users.
     */
    public function run(): void
    {
        $this->command->info('👥 Seeding production users...');

        // Create Default Organization
        $defaultOrg = $this->organizationRepository->create([
            'name' => 'Default Organization'
        ]);

        $this->command->line("   ✓ Created organization: {$defaultOrg->name}");

        // Create Organization Owner
        $owner = $this->userRepository->create([
            'name' => 'Organization Owner',
            'email' => env('ORG_OWNER_EMAIL', 'owner@example.com'),
            'phone' => '+1234567890',
            'password' => env('ORG_OWNER_PASSWORD', 'Owner123!'),
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'status' => 'active',
            'must_change_password' => env('APP_ENV') === 'production',
            'password_changed_at' => now(),
            'password_expires_at' => now()->addDays(config('iam.password_expiry_days', 90)),
        ]);

        // Add owner to organization
        $this->organizationRepository->addMember($defaultOrg, $owner, 'owner');
        $this->command->line("   ✓ Created owner: {$owner->email}");

        // Create sample users for the organization
        $sampleUsers = [
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'phone' => '+1234567891',
                'role' => 'admin'
            ],
            [
                'name' => 'Regular User',
                'email' => 'user@example.com',
                'phone' => '+1234567892',
                'role' => 'user'
            ],
            [
                'name' => 'Member User',
                'email' => 'member@example.com',
                'phone' => '+1234567893',
                'role' => 'member'
            ]
        ];

        foreach ($sampleUsers as $userData) {
            $user = $this->userRepository->create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'phone' => $userData['phone'],
                'password' => 'ChangeMe123!',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
                'status' => 'active',
                'must_change_password' => true, // Force password change on first login
                'password_changed_at' => now(),
                'password_expires_at' => now()->addDays(config('iam.password_expiry_days', 90)),
            ]);

            // Add to organization with role (Organization Role - Layer 1)
            $this->organizationRepository->addMember($defaultOrg, $user, $userData['role']);
            
            $this->command->line("   ✓ Created {$userData['role']}: {$user->email}");
        }

        $this->command->info('✅ Production users seeded successfully!');
        $this->command->newLine();
        $this->command->warn('⚠️  IMPORTANT: Change all default passwords in production!');
    }
}
