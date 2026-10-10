<?php

namespace Tests\Unit\Actions;

use App\Models\User;
use IamApi\Actions\Auth\LoginWithPasswordAction;
use IamApi\Services\AuthService;
use IamApi\Services\MfaService;
use IamApi\Services\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginWithPasswordActionTest extends TestCase
{
    use RefreshDatabase;

    protected AuthService $authService;

    protected TokenService $tokenService;

    protected MfaService $mfaService;

    protected LoginWithPasswordAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authService = Mockery::mock(AuthService::class);
        $this->tokenService = Mockery::mock(TokenService::class);
        $this->mfaService = Mockery::mock(MfaService::class);

        $this->action = new LoginWithPasswordAction(
            $this->authService,
            $this->tokenService,
            $this->mfaService
        );
    }

    #[Test]
    public function it_returns_error_for_invalid_credentials(): void
    {
        $data = [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ];

        $this->authService
            ->shouldReceive('validateCredentials')
            ->with('test@example.com', 'wrong-password', null)
            ->andReturn(null);

        $result = $this->action->execute($data);

        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid credentials', $result['message']);
        $this->assertEquals(400, $result['code']);
    }

    #[Test]
    public function it_returns_error_for_locked_account(): void
    {
        $user = User::factory()->make([
            'locked_until' => now()->addHours(1),
        ]);

        $data = [
            'email' => 'test@example.com',
            'password' => 'correct-password',
        ];

        $this->authService
            ->shouldReceive('validateCredentials')
            ->with('test@example.com', 'correct-password', null)
            ->andReturn($user);

        $result = $this->action->execute($data);

        $this->assertFalse($result['success']);
        $this->assertEquals('Account is temporarily locked', $result['message']);
        $this->assertEquals(423, $result['code']);
    }

    #[Test]
    public function it_returns_error_for_inactive_user(): void
    {
        $user = User::factory()->make([
            'status' => 'disabled',
            'locked_until' => null,
        ]);

        $data = [
            'email' => 'test@example.com',
            'password' => 'correct-password',
        ];

        $this->authService
            ->shouldReceive('validateCredentials')
            ->with('test@example.com', 'correct-password', null)
            ->andReturn($user);

        $result = $this->action->execute($data);

        $this->assertFalse($result['success']);
        $this->assertEquals('Account is disabled', $result['message']);
        $this->assertEquals(403, $result['code']);
    }

    #[Test]
    public function it_requires_password_change_when_needed(): void
    {
        $user = User::factory()->make([
            'status' => 'active',
            'must_change_password' => true,
            'locked_until' => null,
        ]);

        $data = [
            'email' => 'test@example.com',
            'password' => 'correct-password',
        ];

        $this->authService
            ->shouldReceive('validateCredentials')
            ->with('test@example.com', 'correct-password', null)
            ->andReturn($user);

        $this->tokenService
            ->shouldReceive('issue')
            ->with($user, ['temp' => true, 'force_password_change' => true], ['device_name' => 'Unknown Device'])
            ->andReturn(['access_token' => 'temp-token']);

        $result = $this->action->execute($data);

        $this->assertTrue($result['success']);
        $this->assertEquals('Password change required', $result['message']);
        $this->assertEquals('change_password', $result['data']['action_required']);
        $this->assertEquals('temp-token', $result['data']['temp_token']);
    }

    #[Test]
    public function it_requires_mfa_when_enabled(): void
    {
        $user = User::factory()->make([
            'id' => 1,
            'status' => 'active',
            'must_change_password' => false,
            'locked_until' => null,
        ]);

        $data = [
            'email' => 'test@example.com',
            'password' => 'correct-password',
            'device_name' => 'Test Device',
        ];

        $tempTokens = [
            'access_token' => 'temp-token',
            'refresh_token' => 'temp-refresh',
            'expires_in' => 900,
        ];

        $this->authService
            ->shouldReceive('validateCredentials')
            ->with('test@example.com', 'correct-password', null)
            ->andReturn($user);

        $this->mfaService
            ->shouldReceive('isMfaEnabled')
            ->with($user)
            ->andReturn(true);

        $this->tokenService
            ->shouldReceive('issue')
            ->with($user, ['temp' => true, 'mfa_required' => true], ['device_name' => 'Test Device'])
            ->andReturn($tempTokens);

        $result = $this->action->execute($data);

        $this->assertTrue($result['success']);
        $this->assertEquals('MFA verification required', $result['message']);
        $this->assertEquals('mfa_verification', $result['data']['action_required']);
        $this->assertEquals('temp-token', $result['data']['temp_token']);
    }

    #[Test]
    public function it_successfully_logs_in_user_without_mfa(): void
    {
        $user = User::factory()->make([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'status' => 'active',
            'must_change_password' => false,
            'locked_until' => null,
            'email_verified_at' => now(),
            'phone_verified_at' => null,
        ]);

        $data = [
            'email' => 'test@example.com',
            'password' => 'correct-password',
            'device_name' => 'Test Device',
        ];

        $tokens = [
            'access_token' => 'access-token',
            'refresh_token' => 'refresh-token',
            'expires_in' => 900,
            'token_type' => 'Bearer',
        ];

        $this->authService
            ->shouldReceive('validateCredentials')
            ->with('test@example.com', 'correct-password', null)
            ->andReturn($user);

        $this->mfaService
            ->shouldReceive('isMfaEnabled')
            ->with($user)
            ->andReturn(false);

        $this->tokenService
            ->shouldReceive('issue')
            ->with($user, [], ['device_name' => 'Test Device'])
            ->andReturn($tokens);

        $this->authService
            ->shouldReceive('touchLogin')
            ->with($user, Mockery::any())
            ->once();

        $result = $this->action->execute($data);

        $this->assertTrue($result['success']);
        $this->assertEquals('Login successful', $result['message']);
        $this->assertEquals(1, $result['data']['user']['id']);
        $this->assertEquals('test@example.com', $result['data']['user']['email']);
        $this->assertEquals('access-token', $result['data']['tokens']['access_token']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
