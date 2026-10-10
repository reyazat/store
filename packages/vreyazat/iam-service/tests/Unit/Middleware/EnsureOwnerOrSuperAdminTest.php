<?php

namespace Tests\Unit\Middleware;

use App\Models\Organization;
use App\Models\User;
use IamApi\Http\Middleware\EnsureOwnerOrSuperAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class EnsureOwnerOrSuperAdminTest extends TestCase
{
    use RefreshDatabase;

    protected EnsureOwnerOrSuperAdmin $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new EnsureOwnerOrSuperAdmin;
    }

    #[Test]
    public function it_allows_super_admin_to_pass()
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        $organization = Organization::create(['name' => fake()->company()]);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $superAdmin);

        $route = new Route('GET', '/test', []);
        $route->bind($request);
        $route->setParameter('org', $organization);
        $request->setRouteResolver(fn () => $route);

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('OK', $response->getContent());
    }

    #[Test]
    public function it_allows_owner_to_pass()
    {
        $organization = Organization::create(['name' => fake()->company()]);
        $owner = User::factory()->create(['is_super_admin' => false, 'organization_id' => $organization->id]);

        // Assign owner role to user
        Role::firstOrCreate(
            ['name' => 'owner', 'guard_name' => 'api'],
            ['organization_id' => $organization->id]
        );
        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);
        $ownerRole = Role::where('name', 'owner')->where('organization_id', $organization->id)->first();
        $owner->assignRole($ownerRole);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $owner);

        $route = new Route('GET', '/test', []);
        $route->bind($request);
        $route->setParameter('org', $organization);
        $request->setRouteResolver(fn () => $route);

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('OK', $response->getContent());
    }

    #[Test]
    public function it_blocks_admin()
    {
        $organization = Organization::create(['name' => fake()->company()]);
        $admin = User::factory()->create(['is_super_admin' => false, 'organization_id' => $organization->id]);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $admin);

        $route = new Route('GET', '/test', []);
        $route->bind($request);
        $route->setParameter('org', $organization);
        $request->setRouteResolver(fn () => $route);

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('This action requires organization owner, admin, or editor privileges', $data['message']);
    }

    #[Test]
    public function it_blocks_user()
    {
        $organization = Organization::create(['name' => fake()->company()]);
        $user = User::factory()->create(['is_super_admin' => false, 'organization_id' => $organization->id]);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $route = new Route('GET', '/test', []);
        $route->bind($request);
        $route->setParameter('org', $organization);
        $request->setRouteResolver(fn () => $route);

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals(403, $response->getStatusCode());
    }

    #[Test]
    public function it_blocks_member()
    {
        $organization = Organization::create(['name' => fake()->company()]);
        $member = User::factory()->create(['is_super_admin' => false, 'organization_id' => $organization->id]);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $member);

        $route = new Route('GET', '/test', []);
        $route->bind($request);
        $route->setParameter('org', $organization);
        $request->setRouteResolver(fn () => $route);

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals(403, $response->getStatusCode());
    }

    #[Test]
    public function it_blocks_unauthenticated_request()
    {
        $organization = Organization::create(['name' => fake()->company()]);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => null);

        $route = new Route('GET', '/test', []);
        $route->bind($request);
        $route->setParameter('org', $organization);
        $request->setRouteResolver(fn () => $route);

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals(401, $response->getStatusCode());
    }
}
