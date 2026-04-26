<?php

namespace Tests\Unit\Actions;

use App\Actions\Users\RegisterUserAction;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\OtpService;
use App\Services\PasswordPolicyService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Mockery;

class RegisterUserActionTest extends TestCase
{
    use RefreshDatabase;

    protected UserRepositoryInterface $userRepository;
    protected OtpService $otpService;
    protected PasswordPolicyService $passwordPolicyService;
    protected RegisterUserAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
        $this->otpService = Mockery::mock(OtpService::class);
        $this->passwordPolicyService = Mockery::mock(PasswordPolicyService::class);

        $this->action = new RegisterUserAction(
            $this->userRepository,
            $this->otpService,
            $this->passwordPolicyService
        );
    }


    public function it_returns_error_for_weak_password(): void
    {
        $this->expectException(ValidationException::class);
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'weak',
            'password_confirmation' => 'weak'
        ];

        $this->passwordPolicyService
            ->shouldReceive('validatePassword')
            ->with('weak')
            ->andReturn(['Password is too weak']);

        $result = $this->action->execute($data);

        $this->assertFalse($result['success']);
        $this->assertEquals('Password validation failed', $result['message']);
        $this->assertEquals(422, $result['code']);
    }

    #[Test]
    public function it_successfully_registers_user_with_email_verification()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
            'send_verification_email' => true
        ];

        $user = User::factory()->make([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'status' => 'active',
            'created_at' => now()
        ]);

        $otpData = [
            'code' => '123456',
            'expires_at' => now()->addMinutes(60)
        ];

        $this->passwordPolicyService
            ->shouldReceive('validatePassword')
            ->with('StrongPassword123!')
            ->andReturn([]);

        $this->userRepository
            ->shouldReceive('create')
            ->with(Mockery::subset([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '+1234567890',
                'password' => 'StrongPassword123!',
                'status' => 'active'
            ]))
            ->andReturn($user);

        $this->otpService
            ->shouldReceive('generateOtp')
            ->with($user, 'email_verification', 'email', 60)
            ->andReturn($otpData);

        $this->otpService
            ->shouldReceive('sendOtp')
            ->with($user, '123456', 'email', 'email_verification')
            ->andReturn(true);

        $result = $this->action->execute($data);

        $this->assertTrue($result['success']);
        $this->assertEquals('User registered successfully', $result['message']);
        $this->assertEquals(1, $result['data']['user']['id']);
        $this->assertEquals('john@example.com', $result['data']['user']['email']);
        $this->assertTrue($result['data']['email_verification_sent']);
    }

    #[Test]
    public function it_registers_user_without_email_verification()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
            'send_verification_email' => false
        ];

        $user = User::factory()->make([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => 'active',
            'created_at' => now()
        ]);

        $this->passwordPolicyService
            ->shouldReceive('validatePassword')
            ->with('StrongPassword123!')
            ->andReturn([]);

        $this->userRepository
            ->shouldReceive('create')
            ->andReturn($user);

        $result = $this->action->execute($data);

        $this->assertTrue($result['success']);
        $this->assertEquals('User registered successfully', $result['message']);
        $this->assertFalse($result['data']['email_verification_sent']);
    }

    #[Test]
    public function it_handles_registration_failure()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!'
        ];

        $this->passwordPolicyService
            ->shouldReceive('validatePassword')
            ->with('StrongPassword123!')
            ->andReturn([]);

        $this->userRepository
            ->shouldReceive('create')
            ->andThrow(new Exception('Database error'));

        $result = $this->action->execute($data);

        $this->assertFalse($result['success']);
        $this->assertStringContains('Registration failed', $result['message']);
        $this->assertEquals(500, $result['code']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
