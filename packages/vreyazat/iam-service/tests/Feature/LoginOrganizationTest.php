<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginOrganizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'OrgRolePermissionSeeder']);
    }

    public function test_login_response_includes_user_organizations_with_roles()
    {
        // Create organization
        $org = Organization::create(['name' => 'Acme Corp']);

        // Create a user with organization_id
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'TestPassword123!',
            'status' => 'active',
            'organization_id' => $org->id
        ]);

        // Login
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'TestPassword123!',
            'device_name' => 'Test Device'
        ]);

        // Assert response structure
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'status',
                        'organization_id',
                        'organization' => [
                            'id',
                            'name',
                            'created_at',
                            'updated_at'
                        ]
                    ],
                    'tokens' => [
                        'access_token',
                        'refresh_token',
                        'token_type',
                        'expires_in'
                    ]
                ]
            ]);

        // Assert organization data
        $userData = $response->json('data.user');
        $this->assertEquals($org->id, $userData['organization_id']);
        $this->assertEquals($org->id, $userData['organization']['id']);
        $this->assertEquals('Acme Corp', $userData['organization']['name']);
    }

    public function test_login_response_includes_organization_when_user_has_organization()
    {
        // Create organization
        $org = Organization::create(['name' => 'Tech Solutions']);

        // Create a user with organization_id
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'TestPassword123!',
            'status' => 'active',
            'organization_id' => $org->id
        ]);

        // Login
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'TestPassword123!',
            'device_name' => 'Test Device'
        ]);

        // Assert response
        $response->assertStatus(200);
        
        $userData = $response->json('data.user');
        $this->assertNotNull($userData['organization']);
        $this->assertEquals($org->id, $userData['organization']['id']);
        $this->assertEquals('Tech Solutions', $userData['organization']['name']);
    }
}
