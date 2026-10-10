<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use IamApi\Database\Seeders\OrgRolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationScopedAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => OrgRolePermissionSeeder::class]);
    }

    public function test_user_cannot_login_without_organization()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'TestPassword123!',
            'status' => 'active',
            'organization_id' => null, // No organization assigned
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'TestPassword123!',
            'device_name' => 'Test Device',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid credentials',
            ]);
    }

    public function test_user_can_login_with_assigned_organization()
    {
        // Create organization
        $organization = Organization::create(['name' => 'My Company']);

        // Create user with organization assigned
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'TestPassword123!',
            'status' => 'active',
            'organization_id' => $organization->id,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'TestPassword123!',
            'device_name' => 'Test Device',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user',
                    'tokens' => [
                        'access_token',
                        'refresh_token',
                        'token_type',
                        'expires_in',
                    ],
                ],
            ]);
    }

    public function test_user_login_response_includes_organization()
    {
        // Create organization
        $organization = Organization::create(['name' => 'My Company']);

        // Create user with organization assigned
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'TestPassword123!',
            'status' => 'active',
            'organization_id' => $organization->id,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'TestPassword123!',
            'device_name' => 'Test Device',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'organization_id',
                        'organization' => [
                            'id',
                            'name',
                        ],
                    ],
                    'tokens',
                ],
            ]);

        // Verify organization data
        $this->assertEquals($organization->id, $response->json('data.user.organization_id'));
        $this->assertEquals($organization->name, $response->json('data.user.organization.name'));
    }

    public function test_failed_login_increments_failed_attempts_without_organization()
    {
        // Create user without organization
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'TestPassword123!',
            'status' => 'active',
            'organization_id' => null,
        ]);

        // Initial failed attempts should be 0
        $this->assertEquals(0, $user->failed_login_attempts);

        // Try to login without organization
        $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'TestPassword123!',
            'device_name' => 'Test Device',
        ]);

        // Refresh user and check failed attempts incremented
        $user->refresh();
        $this->assertEquals(1, $user->failed_login_attempts);
    }

    public function test_user_belongs_to_organization_method()
    {
        // Create organization
        $organization = Organization::create(['name' => 'My Company']);

        // Create user with organization assigned
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'TestPassword123!',
            'status' => 'active',
            'organization_id' => $organization->id,
        ]);

        // Test belongsToOrganization method
        $this->assertTrue($user->belongsToOrganization($organization));

        // Create another organization
        $otherOrg = Organization::create(['name' => 'Other Company']);
        $this->assertFalse($user->belongsToOrganization($otherOrg));
    }

    public function test_user_organization_relationship()
    {
        // Create organization
        $organization = Organization::create(['name' => 'My Company']);

        // Create user with organization assigned
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'TestPassword123!',
            'status' => 'active',
            'organization_id' => $organization->id,
        ]);

        // Test organization relationship
        $this->assertNotNull($user->organization);
        $this->assertEquals($organization->id, $user->organization->id);
        $this->assertEquals($organization->name, $user->organization->name);
    }
}
