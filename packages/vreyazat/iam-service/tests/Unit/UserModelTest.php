<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\User;
use IamApi\Database\Seeders\OrgRolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => OrgRolePermissionSeeder::class]);
    }

    #[Test]
    public function it_can_check_if_user_is_super_admin()
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        $regularUser = User::factory()->create(['is_super_admin' => false]);

        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertFalse($regularUser->isSuperAdmin());
    }

    #[Test]
    public function it_can_check_if_user_belongs_to_organization()
    {
        $organization = Organization::create(['name' => fake()->company()]);
        $owner = User::factory()->create(['organization_id' => $organization->id]);
        $notMember = User::factory()->create();

        $this->assertTrue($owner->belongsToOrganization($organization));
        $this->assertFalse($notMember->belongsToOrganization($organization));
    }

    #[Test]
    public function user_has_organization_relationship()
    {
        $org = Organization::create(['name' => 'Org 1']);
        $user = User::factory()->create(['organization_id' => $org->id]);

        $this->assertEquals($org->id, $user->organization_id);
        $this->assertEquals($org->id, $user->organization->id);
        $this->assertEquals('Org 1', $user->organization->name);
    }

    #[Test]
    public function is_super_admin_field_is_cast_to_boolean()
    {
        $user = User::factory()->create(['is_super_admin' => 1]);

        $this->assertIsBool($user->is_super_admin);
        $this->assertTrue($user->is_super_admin);
    }

    #[Test]
    public function is_super_admin_defaults_to_false()
    {
        $user = User::factory()->create();

        $this->assertFalse($user->is_super_admin);
        $this->assertFalse($user->isSuperAdmin());
    }
}
