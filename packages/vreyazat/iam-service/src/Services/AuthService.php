<?php

namespace IamApi\Services;

use IamApi\Models\User;
use IamApi\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AuthService
{
    protected UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function validateCredentials(string $email, string $password, ?string $rateKey): ?User
    {
        $user = $this->userRepository->findByEmail($email);

        // اگر کاربر وجود ندارد یا رمز عبور اشتباه است
        if (!$user || !Hash::check($password, $user->password)) {
            if ($user) {
                // اگر کاربر وجود دارد اما رمز اشتباه است، failed attempts را افزایش دهیم
                $this->handleFailedLogin($user, $rateKey);
            } else if ($rateKey) {
                RateLimiter::hit($rateKey, config('iam.authentication.throttle.window_seconds', 900));
            }
            return null;
        }

        // بررسی اینکه کاربر یک سازمان دارد (Super Admin استثنا است)
        if (!$user->organization_id && !$user->is_super_admin) {
            // کاربر هیچ سازمانی ندارد و Super Admin هم نیست
            $this->handleFailedLogin($user, $rateKey);
            return null;
        }

        // موفق: پاک‌کردن throttle و reset failed attempts
        if ($rateKey) {
            RateLimiter::clear($rateKey);
        }

        $this->userRepository->resetFailedAttempts($user);

        return $user;
    }

    public function touchLogin(User $user, ?string $ip = null): void
    {
        $this->userRepository->updateLastLogin($user, $ip ?? request()->ip());
    }

    public function handleFailedLogin(User $user, ?string $rateKey): void
    {
        $this->userRepository->incrementFailedAttempts($user);

        // قفل کردن حساب بعد از تعداد مشخصی تلاش ناموفق
        $maxAttempts = config('iam.authentication.throttle.lock_attempts', 10);
        if ($user->failed_login_attempts >= $maxAttempts) {
            $lockDuration = config('iam.authentication.throttle.lock_seconds', 1800); // 30 دقیقه
            $this->userRepository->lockUser($user, now()->addSeconds($lockDuration));
        }

        if ($rateKey) {
            RateLimiter::hit($rateKey, config('iam.authentication.throttle.window_seconds', 900));
        }
    }

}
