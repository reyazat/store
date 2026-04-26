<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'OrgRolePermissionSeeder']);
    }

    /** @test */
    public function it_can_check_if_user_is_super_admin()
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        $regularUser = User::factory()->create(['is_super_admin' => false]);

        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertFalse($regularUser->isSuperAdmin());
    }

    /** @test */
    public function it_can_check_if_user_belongs_to_organization()
    {
        $organization = Organization::factory()->create();
        $owner = User::factory()->create(['organization_id' => $organization->id]);
        $notMember = User::factory()->create();

        $this->assertTrue($owner->belongsToOrganization($organization));
        $this->assertFalse($notMember->belongsToOrganization($organization));
    }

    /** @test */
    public function user_has_organization_relationship()
    {
        $org = Organization::factory()->create(['name' => 'Org 1']);
        $user = User::factory()->create(['organization_id' => $org->id]);

        $this->assertEquals($org->id, $user->organization_id);
        $this->assertEquals($org->id, $user->organization->id);
        $this->assertEquals('Org 1', $user->organization->name);
    }

    /** @test */
    public function is_super_admin_field_is_cast_to_boolean()
    {
        $user = User::factory()->create(['is_super_admin' => 1]);

        $this->assertIsBool($user->is_super_admin);
        $this->assertTrue($user->is_super_admin);
    }

    /** @test */
    public function is_super_admin_defaults_to_false()
    {
        $user = User::factory()->create();

        $this->assertFalse($user->is_super_admin);
        $this->assertFalse($user->isSuperAdmin());
    }
}
