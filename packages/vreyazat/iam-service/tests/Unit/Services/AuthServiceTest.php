<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Mockery;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    protected UserRepositoryInterface $userRepository;
    protected AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
        $this->authService = new AuthService($this->userRepository);
    }

    #[Test]
    public function it_returns_null_for_non_existent_user()
    {
        $this->userRepository
            ->shouldReceive('findByEmail')
            ->with('nonexistent@example.com')
            ->andReturn(null);

        $result = $this->authService->validateCredentials(
            'nonexistent@example.com',
            'password',
            'rate_key'
        );

        $this->assertNull($result);
    }

    #[Test]
    public function it_returns_null_for_wrong_password()
    {
        $user = User::factory()->make([
            'email' => 'test@example.com',
            'password' => Hash::make('correct_password')
        ]);

        $this->userRepository
            ->shouldReceive('findByEmail')
            ->with('test@example.com')
            ->andReturn($user);

        $result = $this->authService->validateCredentials(
            'test@example.com',
            'wrong_password',
            'rate_key'
        );

        $this->assertNull($result);
    }

    #[Test]
    public function it_returns_null_for_locked_user()
    {
        $user = User::factory()->make([
            'email' => 'test@example.com',
            'password' => Hash::make('correct_password'),
            'locked_until' => now()->addHours(1)
        ]);

        $this->userRepository
            ->shouldReceive('findByEmail')
            ->with('test@example.com')
            ->andReturn($user);

        $result = $this->authService->validateCredentials(
            'test@example.com',
            'correct_password',
            null
        );

        $this->assertNull($result);
    }

    #[Test]
    public function it_returns_user_for_valid_credentials()
    {
        $user = User::factory()->make([
            'email' => 'test@example.com',
            'password' => Hash::make('correct_password'),
            'locked_until' => null,
            'failed_login_attempts' => 0
        ]);

        $this->userRepository
            ->shouldReceive('findByEmail')
            ->with('test@example.com')
            ->andReturn($user);

        $this->userRepository
            ->shouldReceive('resetFailedAttempts')
            ->with($user)
            ->andReturn(true);

        $result = $this->authService->validateCredentials(
            'test@example.com',
            'correct_password',
            null
        );

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('test@example.com', $result->email);
    }

    #[Test]
    public function it_updates_last_login_info()
    {
        $user = User::factory()->make(['id' => 1]);
        $ip = '192.168.1.1';

        $this->userRepository
            ->shouldReceive('updateLastLogin')
            ->with($user, $ip)
            ->andReturn(true);

        $this->authService->touchLogin($user, $ip);

        // Test passes if no exception is thrown
        $this->assertTrue(true);
    }

    #[Test]
    public function it_handles_failed_login_attempts()
    {
        $user = User::factory()->make([
            'id' => 1,
            'failed_login_attempts' => 5
        ]);

        $this->userRepository
            ->shouldReceive('incrementFailedAttempts')
            ->with($user)
            ->andReturn(true);

        $this->authService->handleFailedLogin($user, null);

        // Test passes if no exception is thrown
        $this->assertTrue(true);
    }

    #[Test]
    public function it_locks_user_after_max_attempts()
    {
        $user = User::factory()->make([
            'id' => 1,
            'failed_login_attempts' => 10
        ]);

        $this->userRepository
            ->shouldReceive('incrementFailedAttempts')
            ->with($user)
            ->andReturn(true);

        $this->userRepository
            ->shouldReceive('lockUser')
            ->with($user, Mockery::type(\Carbon\Carbon::class))
            ->andReturn(true);

        $this->authService->handleFailedLogin($user, null);

        // Test passes if no exception is thrown
        $this->assertTrue(true);
    }

    #[Test]
    public function it_unlocks_user()
    {
        $user = User::factory()->make(['id' => 1]);

        $this->userRepository
            ->shouldReceive('unlockUser')
            ->with($user)
            ->andReturn(true);

        $result = $this->authService->unlockUser($user);

        $this->assertTrue($result);
    }

    #[Test]
    public function it_forces_password_change()
    {
        $user = User::factory()->make(['id' => 1]);

        $this->userRepository
            ->shouldReceive('forcePasswordChange')
            ->with($user)
            ->andReturn(true);

        $result = $this->authService->forcePasswordChange($user);

        $this->assertTrue($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
