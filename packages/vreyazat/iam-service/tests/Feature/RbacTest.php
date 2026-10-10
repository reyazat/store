<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use IamApi\Database\Seeders\OrgRolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => OrgRolePermissionSeeder::class]);

        // Create organization
        $this->organization = Organization::query()->create([
            'name' => 'Test Organization',
        ]);

        // Create user with organization_id
        $this->user = User::factory()->create([
            'email' => 'admin@test.com',
            'status' => 'active',
            'organization_id' => $this->organization->id,
        ]);

        // Set team context
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->organization->id);

        // Create owner role
        $ownerRole = Role::create([
            'name' => 'owner',
            'guard_name' => 'api',
            'organization_id' => $this->organization->id,
        ]);

        // Create org.manage permission
        $permission = Permission::create([
            'name' => 'org.manage',
            'guard_name' => 'api',
            'organization_id' => $this->organization->id,
        ]);

        // Assign permission to role
        $ownerRole->givePermissionTo($permission);

        // Assign role to user
        $this->user->assignRole($ownerRole);
    }

    #[Test]
    public function it_can_list_roles()
    {
        $this->actingAs($this->user, 'api');

        $response = $this->getJson("/api/v1/orgs/{$this->organization->uuid}/roles");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'roles',
                    'total',
                ],
            ]);
    }

    #[Test]
    public function it_can_create_role()
    {
        $this->actingAs($this->user, 'api');

        $response = $this->postJson("/api/v1/orgs/{$this->organization->uuid}/roles", [
            'name' => 'editor',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Role created successfully.',
            ]);

        $this->assertDatabaseHas('roles', [
            'name' => 'editor',
            'organization_id' => $this->organization->id,
        ]);
    }

    #[Test]
    public function it_can_update_role()
    {
        $this->actingAs($this->user, 'api');

        // Create role first
        $role = Role::create([
            'name' => 'editor',
            'guard_name' => 'api',
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->patchJson("/api/v1/orgs/{$this->organization->uuid}/roles/{$role->id}", [
            'name' => 'senior-editor',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Role updated successfully.',
            ]);

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => 'senior-editor',
        ]);
    }

    #[Test]
    public function it_can_delete_role()
    {
        $this->actingAs($this->user, 'api');

        // Create role
        $role = Role::create([
            'name' => 'editor',
            'guard_name' => 'api',
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->deleteJson("/api/v1/orgs/{$this->organization->uuid}/roles/{$role->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Role deleted successfully.',
            ]);

        $this->assertDatabaseMissing('roles', [
            'id' => $role->id,
        ]);
    }

    #[Test]
    public function it_cannot_delete_role_assigned_to_users()
    {
        $this->actingAs($this->user, 'api');

        // Create role and assign to user
        $role = Role::create([
            'name' => 'editor',
            'guard_name' => 'api',
            'organization_id' => $this->organization->id,
        ]);

        $this->user->assignRole($role);

        $response = $this->deleteJson("/api/v1/orgs/{$this->organization->uuid}/roles/{$role->id}");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
        ]);
    }

    #[Test]
    public function it_can_list_permissions()
    {
        $this->actingAs($this->user, 'api');

        $response = $this->getJson("/api/v1/orgs/{$this->organization->uuid}/permissions");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'permissions',
                    'total',
                ],
            ]);
    }

    #[Test]
    public function it_can_create_permission()
    {
        $this->actingAs($this->user, 'api');

        $response = $this->postJson("/api/v1/orgs/{$this->organization->uuid}/permissions", [
            'name' => 'article.publish',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Permission created successfully.',
            ]);

        $this->assertDatabaseHas('permissions', [
            'name' => 'article.publish',
            'organization_id' => $this->organization->id,
        ]);
    }

    #[Test]
    public function it_can_get_user_roles()
    {
        $this->actingAs($this->user, 'api');

        // Create and assign role
        $role = Role::create([
            'name' => 'editor',
            'guard_name' => 'api',
            'organization_id' => $this->organization->id,
        ]);

        $this->user->assignRole($role);

        $response = $this->getJson("/api/v1/auth/me/roles?organization_id={$this->organization->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'organization_id' => $this->organization->id,
                ],
            ])
            ->assertJsonFragment([
                'name' => 'editor',
            ]);
    }

    #[Test]
    public function it_can_get_user_permissions()
    {
        $this->actingAs($this->user, 'api');

        $response = $this->getJson("/api/v1/auth/me/permissions?organization_id={$this->organization->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'organization_id' => $this->organization->id,
                ],
            ])
            ->assertJsonFragment([
                'name' => 'org.manage',
            ]);
    }

    #[Test]
    public function it_validates_unique_role_name_per_organization()
    {
        $this->actingAs($this->user, 'api');

        // Create first role
        Role::create([
            'name' => 'editor',
            'guard_name' => 'api',
            'organization_id' => $this->organization->id,
        ]);

        // Try to create duplicate
        $response = $this->postJson("/api/v1/orgs/{$this->organization->uuid}/roles", [
            'name' => 'editor',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function it_requires_authentication_for_rbac_endpoints()
    {
        $response = $this->getJson("/api/v1/orgs/{$this->organization->uuid}/roles");

        $response->assertStatus(401);
    }
}
