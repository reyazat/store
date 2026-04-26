<?php

namespace Tests\Unit\Repositories;

use App\Models\User;
use App\Models\PasswordHistory;
use App\Repositories\Eloquent\EloquentUserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EloquentUserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected EloquentUserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentUserRepository(new User());
    }

    #[Test]
    public function it_can_find_user_by_email()
    {
        /** @var User $user */
        $user = User::factory()->create(['email' => 'test@example.com']);

        $found = $this->repository->findByEmail('test@example.com');

        $this->assertInstanceOf(User::class, $found);
        $this->assertEquals($user->id, $found->id);
    }

    #[Test]
    public function it_returns_null_for_non_existent_email()
    {
        $found = $this->repository->findByEmail('nonexistent@example.com');

        $this->assertNull($found);
    }

    #[Test]
    public function it_can_find_user_by_phone()
    {
        /** @var User $user */
        $user = User::factory()->create(['phone' => '+1234567890']);

        $found = $this->repository->findByPhone('+1234567890');

        $this->assertInstanceOf(User::class, $found);
        $this->assertEquals($user->id, $found->id);
    }

    #[Test]
    public function it_can_find_active_users()
    {
        User::factory()->create(['status' => 'active']);
        User::factory()->create(['status' => 'disabled']);
        User::factory()->create(['status' => 'active']);

        $activeUsers = $this->repository->findActiveUsers();

        $this->assertCount(2, $activeUsers);
        $activeUsers->each(function ($user) {
            $this->assertEquals('active', $user->status);
        });
    }

    #[Test]
    public function it_can_find_locked_users()
    {
        User::factory()->create(['locked_until' => now()->addHours(1)]);
        User::factory()->create(['locked_until' => null]);
        User::factory()->create(['locked_until' => now()->subHours(1)]); // Expired lock

        $lockedUsers = $this->repository->findLockedUsers();

        $this->assertCount(1, $lockedUsers);
    }

    #[Test]
    public function it_can_find_users_with_expired_passwords()
    {
        User::factory()->create(['password_expires_at' => now()->subDays(1)]);
        User::factory()->create(['password_expires_at' => now()->addDays(1)]);
        User::factory()->create(['password_expires_at' => now()->subDays(5)]);

        $expiredUsers = $this->repository->findUsersWithExpiredPasswords();

        $this->assertCount(2, $expiredUsers);
    }

    #[Test]
    public function it_can_find_users_must_change_password()
    {
        User::factory()->create(['must_change_password' => true]);
        User::factory()->create(['must_change_password' => false]);
        User::factory()->create(['must_change_password' => true]);

        $users = $this->repository->findUsersMustChangePassword();

        $this->assertCount(2, $users);
        $users->each(function ($user) {
            $this->assertTrue($user->must_change_password);
        });
    }

    #[Test]
    public function it_can_lock_user()
    {
        /** @var User $user */
        $user = User::factory()->create(['locked_until' => null]);
        $lockUntil = now()->addHours(2);

        $result = $this->repository->lockUser($user, $lockUntil);

        $this->assertTrue($result);
        $user->refresh();
        $this->assertEquals($lockUntil->timestamp, $user->locked_until->timestamp);
    }

    #[Test]
    public function it_can_unlock_user()
    {
        /** @var User $user */
        $user = User::factory()->create([
            'locked_until' => now()->addHours(1),
            'failed_login_attempts' => 5
        ]);

        $result = $this->repository->unlockUser($user);

        $this->assertTrue($result);
        $user->refresh();
        $this->assertNull($user->locked_until);
        $this->assertEquals(0, $user->failed_login_attempts);
    }

    #[Test]
    public function it_can_increment_failed_attempts()
    {
        /** @var User $user */
        $user = User::factory()->create(['failed_login_attempts' => 2]);

        $result = $this->repository->incrementFailedAttempts($user);

        $this->assertTrue($result);
        $user->refresh();
        $this->assertEquals(3, $user->failed_login_attempts);
    }

    #[Test]
    public function it_can_reset_failed_attempts()
    {
        /** @var User $user */
        $user = User::factory()->create(['failed_login_attempts' => 5]);

        $result = $this->repository->resetFailedAttempts($user);

        $this->assertTrue($result);
        $user->refresh();
        $this->assertEquals(0, $user->failed_login_attempts);
    }

    #[Test]
    public function it_can_update_last_login()
    {
        /** @var User $user */
        $user = User::factory()->create([
            'last_login_at' => null,
            'last_login_ip' => null
        ]);
        $ip = '192.168.1.1';

        $result = $this->repository->updateLastLogin($user, $ip);

        $this->assertTrue($result);
        $user->refresh();
        $this->assertNotNull($user->last_login_at);
        $this->assertEquals($ip, $user->last_login_ip);
    }

    #[Test]
    public function it_can_force_password_change()
    {
        /** @var User $user */
        $user = User::factory()->create(['must_change_password' => false]);

        $result = $this->repository->forcePasswordChange($user);

        $this->assertTrue($result);
        $user->refresh();
        $this->assertTrue($user->must_change_password);
    }

    #[Test]
    public function it_can_update_password_with_history()
    {
        /** @var User $user */
        $user = User::factory()->create([
            'password' => 'old_password_hash'
        ]);

        $newPassword = 'new_password_hash';
        $result = $this->repository->updatePasswordWithHistory($user, $newPassword);

        $this->assertTrue($result);
        $user->refresh();

        // Check password was updated
        $this->assertEquals($newPassword, $user->password);
        $this->assertNotNull($user->password_changed_at);
        $this->assertNotNull($user->password_expires_at);
        $this->assertFalse($user->must_change_password);

        // Check password history was created
        $this->assertDatabaseHas('password_histories', [
            'user_id' => $user->id,
            'password_hash' => 'old_password_hash'
        ]);
    }

    #[Test]
    public function it_can_check_password_exists_in_history()
    {
        /** @var User $user */
        $user = User::factory()->create();

        // Create password history
        PasswordHistory::query()->create([
            'user_id' => $user->id,
            'password_hash' => Hash::make('old_password'),
            'changed_at' => now()->subDays(1)
        ]);

        $exists = $this->repository->passwordExistsInHistory($user, 'old_password', 5);
        $notExists = $this->repository->passwordExistsInHistory($user, 'different_password', 5);

        $this->assertTrue($exists);
        $this->assertFalse($notExists);
    }
}
