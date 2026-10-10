<?php

namespace Tests\Unit\Middleware;

use App\Models\User;
use IamApi\Http\Middleware\EnsureSuperAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EnsureSuperAdminTest extends TestCase
{
    use RefreshDatabase;

    protected EnsureSuperAdmin $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new EnsureSuperAdmin;
    }

    #[Test]
    public function it_allows_super_admin_to_pass()
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $superAdmin);

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals('OK', $response->getContent());
    }

    #[Test]
    public function it_blocks_regular_user()
    {
        $user = User::factory()->create(['is_super_admin' => false]);
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('This action requires Super Admin privileges', $data['message']);
    }

    #[Test]
    public function it_blocks_unauthenticated_request()
    {
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => null);

        $response = $this->middleware->handle($request, fn ($req) => response('OK'));

        $this->assertEquals(403, $response->getStatusCode());
    }
}
