<?php

use IamApi\Http\Controllers\API\AuthController;
use IamApi\Http\Controllers\API\UserController;
use IamApi\Http\Controllers\API\OtpController;
use IamApi\Http\Controllers\API\MfaController;
use IamApi\Http\Controllers\API\PasswordController;
use IamApi\Http\Controllers\API\AccountLockoutController;
use IamApi\Http\Controllers\API\SessionController;
use IamApi\Http\Controllers\API\OrganizationController;
use IamApi\Http\Controllers\API\OrganizationMemberController;
use IamApi\Http\Controllers\API\OrganizationInvitationController;
use IamApi\Http\Controllers\API\AuditController;
use IamApi\Http\Controllers\API\RoleController;
use IamApi\Http\Controllers\API\PermissionController;
use IamApi\Http\Controllers\API\RbacIntrospectionController;
use IamApi\Http\Controllers\API\DepartmentController;
use IamApi\Http\Controllers\API\DepartmentMemberController;
use IamApi\Http\Controllers\API\CaptchaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public CAPTCHA Route
Route::get('v1/captcha/{config?}', [CaptchaController::class, 'get'])
    ->middleware('throttle:' . config('captcha.rate_limit.max_requests', 60) . ',' . config('captcha.rate_limit.time_window', 1))
    ->name('iam.captcha.get');

// Public Authentication Routes
Route::prefix('v1/auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('captcha.throttle:login')
        ->name('iam.auth.login');
    Route::post('refresh', [AuthController::class, 'refresh'])->name('iam.auth.refresh');
});

// Public OTP Routes
Route::prefix('v1/otp')->group(function () {
    Route::post('send', [OtpController::class, 'send'])
        ->middleware('captcha.throttle:otp')
        ->name('iam.otp.send');
    Route::post('verify', [OtpController::class, 'verify'])->name('iam.otp.verify');
});

// Public MFA Routes (for login flow)
Route::prefix('v1/mfa')->group(function () {
    Route::post('verify-login', [MfaController::class, 'verifyLogin'])->name('iam.mfa.verify-login');
    Route::post('verify-recovery-login', [MfaController::class, 'verifyRecoveryLogin'])->name('iam.mfa.verify-recovery-login');
});

// Public Password Routes
Route::prefix('v1/password')->group(function () {
    Route::post('forgot', [PasswordController::class, 'forgot'])
        ->middleware('captcha.throttle:password_reset')
        ->name('iam.password.forgot');
    Route::post('reset', [PasswordController::class, 'reset'])->name('iam.password.reset');
    Route::get('policy', [PasswordController::class, 'policy'])->name('iam.password.policy');
    // Force-change is called during login flow using a temp token (user not fully authenticated yet)
    Route::post('force-change-login', [PasswordController::class, 'forceChangeLogin'])
        ->middleware('auth:api')
        ->name('iam.password.force-change-login');
});

// Public User Routes
Route::prefix('v1/users')->group(function () {
    Route::post('register', [UserController::class, 'register'])
        ->middleware('captcha.throttle:registration')
        ->name('iam.users.register');
});

// Public Account Lockout Routes
Route::prefix('v1/account')->group(function () {
    Route::get('lockout/policy', [AccountLockoutController::class, 'policy'])->name('iam.account.lockout.policy');
});

// Protected Routes (Require Authentication)
Route::middleware(['auth:api', 'captcha.throttle:api', 'password.expiry'])->prefix('v1')->group(function () {
    
    // Authentication Routes
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('iam.auth.logout');
        Route::get('me', [AuthController::class, 'me'])->name('iam.auth.me');
        
        // RBAC Introspection
        Route::get('me/roles', [RbacIntrospectionController::class, 'myRoles'])->name('iam.auth.me.roles');
        Route::get('me/permissions', [RbacIntrospectionController::class, 'myPermissions'])->name('iam.auth.me.permissions');
    });

    // Multi-Factor Authentication Routes
    Route::prefix('mfa')->group(function () {
        Route::get('status', [MfaController::class, 'status'])->name('iam.mfa.status');
        Route::post('enable', [MfaController::class, 'enable'])->name('iam.mfa.enable');
        Route::post('verify', [MfaController::class, 'verify'])->name('iam.mfa.verify');
        Route::post('disable', [MfaController::class, 'disable'])->name('iam.mfa.disable');
        Route::post('recovery-codes', [MfaController::class, 'generateRecoveryCodes'])->name('iam.mfa.recovery-codes');
        Route::get('recovery-codes', [MfaController::class, 'getRecoveryCodes'])->name('iam.mfa.recovery-codes.get');
        Route::post('recovery-codes/verify', [MfaController::class, 'verifyRecoveryCode'])->name('iam.mfa.recovery-codes.verify');
    });

    // Password Management Routes
    Route::prefix('password')->group(function () {
        Route::post('change', [PasswordController::class, 'change'])->name('iam.password.change');
        Route::post('force-change', [PasswordController::class, 'forceChange'])->name('iam.password.force-change');
        Route::get('expiry', [PasswordController::class, 'checkExpiry'])->name('iam.password.expiry');
    });

    // Account Lockout Management Routes
    Route::prefix('account')->group(function () {
        Route::get('lockout/status', [AccountLockoutController::class, 'status'])->name('iam.account.lockout.status');
        Route::post('unlock', [AccountLockoutController::class, 'unlock'])->name('iam.account.unlock');
        
        // Super Admin only routes
        Route::middleware(['super.admin'])->group(function () {
            Route::post('lock', [AccountLockoutController::class, 'lock'])->name('iam.account.lock');
        });
    });

    // User Management Routes
    Route::prefix('users')->group(function () {
        Route::get('profile', [UserController::class, 'show'])->name('iam.users.profile');
        Route::put('profile', [UserController::class, 'update'])->name('iam.users.update');
        Route::delete('account', [UserController::class, 'destroy'])->name('iam.users.delete');
        
        // Super Admin Routes (system-wide user management)
        Route::middleware(['super.admin'])->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('iam.users.index');
            Route::get('{id}', [UserController::class, 'showById'])->name('iam.users.show');
        });
    });

    // Session Management Routes
    Route::prefix('sessions')->group(function () {
        Route::get('/', [SessionController::class, 'index'])->name('iam.sessions.index');
        Route::delete('revoke', [SessionController::class, 'revoke'])->name('iam.sessions.revoke');
    });

    // Organization Routes
    Route::prefix('orgs')->group(function () {
        Route::get('/', [OrganizationController::class, 'index'])->name('iam.orgs.index');
        Route::post('/', [OrganizationController::class, 'store'])->name('iam.orgs.store');
        Route::get('/{org}', [OrganizationController::class, 'show'])->name('iam.orgs.show');
        Route::patch('/{org}', [OrganizationController::class, 'update'])->name('iam.orgs.update');
        Route::delete('/{org}', [OrganizationController::class, 'destroy'])->name('iam.orgs.destroy');

        // Organization Members
        Route::prefix('{org}/members')->group(function () {
            Route::get('/', [OrganizationMemberController::class, 'index'])->name('iam.orgs.members.index');
            Route::post('/', [OrganizationMemberController::class, 'store'])->name('iam.orgs.members.store');
            Route::patch('/{user}', [OrganizationMemberController::class, 'update'])->name('iam.orgs.members.update');
            Route::delete('/{user}', [OrganizationMemberController::class, 'destroy'])->name('iam.orgs.members.destroy');
        });

        // Organization Invitations
        Route::prefix('{org}/invites')->group(function () {
            Route::get('/', [OrganizationInvitationController::class, 'index'])->name('iam.orgs.invites.index');
            Route::post('/', [OrganizationInvitationController::class, 'store'])->name('iam.orgs.invites.store');
            Route::post('/{invite}/resend', [OrganizationInvitationController::class, 'resend'])->name('iam.orgs.invites.resend');
            Route::delete('/{invite}', [OrganizationInvitationController::class, 'destroy'])->name('iam.orgs.invites.destroy');
        });

        // Organization Departments
        Route::prefix('{org}/departments')->group(function () {
            Route::get('/', [DepartmentController::class, 'index'])->name('iam.orgs.departments.index');
            Route::post('/', [DepartmentController::class, 'store'])->name('iam.orgs.departments.store');
            Route::get('/{department}', [DepartmentController::class, 'show'])->name('iam.orgs.departments.show');
            Route::patch('/{department}', [DepartmentController::class, 'update'])->name('iam.orgs.departments.update');
            Route::delete('/{department}', [DepartmentController::class, 'destroy'])->name('iam.orgs.departments.destroy');

            // Department Members
            Route::prefix('{department}/members')->group(function () {
                Route::get('/', [DepartmentMemberController::class, 'index'])->name('iam.orgs.departments.members.index');
                Route::post('/', [DepartmentMemberController::class, 'store'])->name('iam.orgs.departments.members.store');
                Route::patch('/{user}', [DepartmentMemberController::class, 'update'])->name('iam.orgs.departments.members.update');
                Route::delete('/{user}', [DepartmentMemberController::class, 'destroy'])->name('iam.orgs.departments.members.destroy');
            });
        });

        // RBAC - Roles Management (Owner or Super Admin only)
        Route::prefix('{org}/roles')->middleware('owner.or.super')->group(function () {
            Route::get('/', [RoleController::class, 'index'])->name('iam.orgs.roles.index');
            Route::post('/', [RoleController::class, 'store'])->name('iam.orgs.roles.store');
            Route::get('/{role}', [RoleController::class, 'show'])->name('iam.orgs.roles.show');
            Route::patch('/{role}', [RoleController::class, 'update'])->name('iam.orgs.roles.update');
            Route::delete('/{role}', [RoleController::class, 'destroy'])->name('iam.orgs.roles.destroy');
            
            // Role Permissions
            Route::post('/{role}/permissions', [RoleController::class, 'assignPermissions'])->name('iam.orgs.roles.permissions.assign');
            Route::delete('/{role}/permissions/{permission}', [RoleController::class, 'revokePermission'])->name('iam.orgs.roles.permissions.revoke');
            
            // Role Assignment to Users
            Route::post('/{role}/assign/{user}', [RoleController::class, 'assignToUser'])
                ->name('iam.orgs.roles.assign');
            Route::delete('/{role}/assign/{user}', [RoleController::class, 'revokeFromUser'])
                ->name('iam.orgs.roles.revoke');
        });

        // RBAC - Permissions Management (Owner or Super Admin only)
        Route::prefix('{org}/permissions')->middleware('owner.or.super')->group(function () {
            Route::get('/', [PermissionController::class, 'index'])->name('iam.orgs.permissions.index');
            Route::post('/', [PermissionController::class, 'store'])->name('iam.orgs.permissions.store');
            Route::get('/{permission}', [PermissionController::class, 'show'])->name('iam.orgs.permissions.show');
            Route::patch('/{permission}', [PermissionController::class, 'update'])->name('iam.orgs.permissions.update');
            Route::delete('/{permission}', [PermissionController::class, 'destroy'])->name('iam.orgs.permissions.destroy');
            
            // Direct Permission Assignment to Users
            Route::post('/{permission}/assign/{user}', [PermissionController::class, 'assignToUser'])
                ->name('iam.orgs.permissions.assign');
            Route::delete('/{permission}/assign/{user}', [PermissionController::class, 'revokeFromUser'])
                ->name('iam.orgs.permissions.revoke');
        });

    });

    // Accept Invitation (Public with Auth)
    Route::post('auth/invites/{token}/accept', [OrganizationInvitationController::class, 'accept'])->name('iam.invites.accept');

    // Audit & Activity Logs Routes
    Route::prefix('audit')->group(function () {
        Route::get('logs', [AuditController::class, 'index'])->name('iam.audit.logs.index');
        Route::get('logs/{id}', [AuditController::class, 'show'])->name('iam.audit.logs.show');
    });
});

// Health Check Endpoint
Route::get('v1/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'version' => config('app.version', '1.0.0'),
        'features' => [
            'jwt_authentication' => true,
            'multi_factor_auth' => true,
            'otp_verification' => true,
            'password_policy' => true,
            'account_lockout' => true,
            'rate_limiting' => true,
            'captcha_protection' => true,
            'session_management' => true,
            'organizations' => true,
            'audit_logs' => true,
            'rbac' => true
        ]
    ]);
})->name('iam.health.check');
