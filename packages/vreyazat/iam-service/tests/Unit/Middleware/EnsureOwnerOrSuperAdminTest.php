<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\EnsureOwnerOrSuperAdmin;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Tests\TestCase;

class EnsureOwnerOrSuperAdminTest extends TestCase
{
    use RefreshDatabase;

    protected EnsureOwnerOrSuperAdmin $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new EnsureOwnerOrSuperAdmin();
    }

    /** @test */
    public function it_allows_super_admin_to_pass()
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        $organization = Organization::factory()->create();

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn() => $superAdmin);
        
        $route = new Route('GET', '/test', []);
        $route->setParameter('org', $organization);
        $request->setRouteResolver(fn() => $route);

        $response = $this->middleware->handle($request, fn($req) => response('OK'));

        $this->assertEquals('OK', $response->getContent());
    }

    /** @test */
    public function it_allows_owner_to_pass()
    {
        $organization = Organization::factory()->create();
        $owner = User::factory()->create(['is_super_admin' => false, 'organization_id' => $organization->id]);
        
        // Assign owner role to user
        \Spatie\Permission\Models\Role::firstOrCreate(
            ['name' => 'owner', 'guard_name' => 'api'],
            ['organization_id' => $organization->id]
        );
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($organization->id);
        $ownerRole = \Spatie\Permission\Models\Role::where('name', 'owner')->where('organization_id', $organization->id)->first();
        $owner->assignRole($ownerRole);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn() => $owner);
        
        $route = new Route('GET', '/test', []);
        $route->setParameter('org', $organization);
        $request->setRouteResolver(fn() => $route);

        $response = $this->middleware->handle($request, fn($req) => response('OK'));

        $this->assertEquals('OK', $response->getContent());
    }

    /** @test */
    public function it_blocks_admin()
    {
        $organization = Organization::factory()->create();
        $admin = User::factory()->create(['is_super_admin' => false, 'organization_id' => $organization->id]);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn() => $admin);
        
        $route = new Route('GET', '/test', []);
        $route->setParameter('org', $organization);
        $request->setRouteResolver(fn() => $route);

        $response = $this->middleware->handle($request, fn($req) => response('OK'));

        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('This action requires organization owner privileges', $data['message']);
    }

    /** @test */
    public function it_blocks_user()
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['is_super_admin' => false, 'organization_id' => $organization->id]);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn() => $user);
        
        $route = new Route('GET', '/test', []);
        $route->setParameter('org', $organization);
        $request->setRouteResolver(fn() => $route);

        $response = $this->middleware->handle($request, fn($req) => response('OK'));

        $this->assertEquals(403, $response->getStatusCode());
    }

    /** @test */
    public function it_blocks_member()
    {
        $organization = Organization::factory()->create();
        $member = User::factory()->create(['is_super_admin' => false, 'organization_id' => $organization->id]);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn() => $member);
        
        $route = new Route('GET', '/test', []);
        $route->setParameter('org', $organization);
        $request->setRouteResolver(fn() => $route);

        $response = $this->middleware->handle($request, fn($req) => response('OK'));

        $this->assertEquals(403, $response->getStatusCode());
    }

    /** @test */
    public function it_blocks_unauthenticated_request()
    {
        $organization = Organization::factory()->create();

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn() => null);
        
        $route = new Route('GET', '/test', []);
        $route->setParameter('org', $organization);
        $request->setRouteResolver(fn() => $route);

        $response = $this->middleware->handle($request, fn($req) => response('OK'));

        $this->assertEquals(401, $response->getStatusCode());
    }
}
