<?php

namespace IamApi\Repositories;

use Illuminate\Support\ServiceProvider;

// Contracts
use IamApi\Repositories\Contracts\UserRepositoryInterface;
use IamApi\Repositories\Contracts\JwtTokenRepositoryInterface;
use IamApi\Repositories\Contracts\OtpRepositoryInterface;
use IamApi\Repositories\Contracts\UserTotpSecretRepositoryInterface;
use IamApi\Repositories\Contracts\UserRecoveryCodeRepositoryInterface;
use IamApi\Repositories\Contracts\PasswordHistoryRepositoryInterface;
use IamApi\Repositories\Contracts\OrganizationRepositoryInterface;
use IamApi\Repositories\Contracts\OrganizationInvitationRepositoryInterface;
use IamApi\Repositories\Contracts\OrganizationDomainRepositoryInterface;
use IamApi\Repositories\Contracts\VerificationTokenRepositoryInterface;
use IamApi\Repositories\Contracts\ActivityLogRepositoryInterface;

// Eloquent Implementations
use IamApi\Repositories\Eloquent\EloquentUserRepository;
use IamApi\Repositories\Eloquent\EloquentJwtTokenRepository;
use IamApi\Repositories\Eloquent\EloquentOtpRepository;
use IamApi\Repositories\Eloquent\EloquentUserTotpSecretRepository;
use IamApi\Repositories\Eloquent\EloquentUserRecoveryCodeRepository;
use IamApi\Repositories\Eloquent\EloquentPasswordHistoryRepository;
use IamApi\Repositories\Eloquent\EloquentOrganizationRepository;
use IamApi\Repositories\Eloquent\EloquentOrganizationInvitationRepository;
use IamApi\Repositories\Eloquent\EloquentOrganizationDomainRepository;
use IamApi\Repositories\Eloquent\EloquentVerificationTokenRepository;
use IamApi\Repositories\Eloquent\EloquentActivityLogRepository;

// Models
use IamApi\Models\User;
use IamApi\Models\JwtToken;
use IamApi\Models\Otp;
use IamApi\Models\UserTotpSecret;
use IamApi\Models\UserRecoveryCode;
use IamApi\Models\PasswordHistory;
use IamApi\Models\Organization;
use IamApi\Models\OrganizationInvitation;
use IamApi\Models\OrganizationDomain;
use IamApi\Models\VerificationToken;
use Spatie\Activitylog\Models\Activity;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // User Repository
        $this->app->bind(UserRepositoryInterface::class, function ($app) {
            return new EloquentUserRepository(new User());
        });

        // JWT Token Repository
        $this->app->bind(JwtTokenRepositoryInterface::class, function ($app) {
            return new EloquentJwtTokenRepository(new JwtToken());
        });

        // OTP Repository
        $this->app->bind(OtpRepositoryInterface::class, function ($app) {
            return new EloquentOtpRepository(new Otp());
        });

        // User TOTP Secret Repository
        $this->app->bind(UserTotpSecretRepositoryInterface::class, function ($app) {
            return new EloquentUserTotpSecretRepository(new UserTotpSecret());
        });

        // User Recovery Code Repository
        $this->app->bind(UserRecoveryCodeRepositoryInterface::class, function ($app) {
            return new EloquentUserRecoveryCodeRepository(new UserRecoveryCode());
        });

        // Password History Repository
        $this->app->bind(PasswordHistoryRepositoryInterface::class, function ($app) {
            return new EloquentPasswordHistoryRepository(new PasswordHistory());
        });

        // Organization Repository
        $this->app->bind(OrganizationRepositoryInterface::class, function ($app) {
            return new EloquentOrganizationRepository(new Organization());
        });

        // Organization Invitation Repository
        $this->app->bind(OrganizationInvitationRepositoryInterface::class, function ($app) {
            return new EloquentOrganizationInvitationRepository(new OrganizationInvitation());
        });

        // Organization Domain Repository
        $this->app->bind(OrganizationDomainRepositoryInterface::class, function ($app) {
            return new EloquentOrganizationDomainRepository(new OrganizationDomain());
        });

        // Verification Token Repository
        $this->app->bind(VerificationTokenRepositoryInterface::class, function ($app) {
            return new EloquentVerificationTokenRepository(new VerificationToken());
        });

        // Activity Log Repository
        $this->app->bind(ActivityLogRepositoryInterface::class, function ($app) {
            return new EloquentActivityLogRepository(new Activity());
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
