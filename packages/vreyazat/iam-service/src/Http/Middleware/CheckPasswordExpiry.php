<?php

namespace IamApi\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use IamApi\Http\Resources\AbstractResource;

class CheckPasswordExpiry
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Skip password expiry check for certain routes
        $skipRoutes = [
            'iam.password.change',
            'iam.password.force-change',
            'iam.password.expiry',
            'iam.auth.logout',
            'iam.auth.me',           // Allow reading own profile (needed right after OTP login)
            'iam.auth.me.roles',     // Allow reading own roles (needed right after OTP login)
            'iam.auth.me.permissions',
            'iam.mfa.*', // Allow MFA operations
            'health.check'
        ];

        foreach ($skipRoutes as $pattern) {
            if ($request->routeIs($pattern)) {
                return $next($request);
            }
        }

        // Check if user must change password
        if ($user->must_change_password) {
            return response()->json(
                AbstractResource::error(
                    'Password change required. Please update your password to continue.',
                    [
                        'must_change_password' => true,
                        'reason' => $this->getPasswordChangeReason($user),
                        'change_password_url' => route('password.force-change')
                    ]
                ),
                403
            );
        }

        // Check if the password is expired
        if ($user->password_expires_at && $user->password_expires_at->isPast()) {
            // Update must_change_password flag
            $user->update(['must_change_password' => true]);

            return response()->json(
                AbstractResource::error(
                    'Your password has expired. Please change it to continue.',
                    [
                        'password_expired' => true,
                        'expired_at' => $user->password_expires_at->toISOString(),
                        'must_change_password' => true,
                        'change_password_url' => route('password.force-change')
                    ]
                ),
                403
            );
        }

        // Check if the password expires soon (warning)
        $warningDays = config('iam.password.warning_days', 7);
        if ($user->password_expires_at && $user->password_expires_at->diffInDays(now()) <= $warningDays) {
            $daysRemaining = $user->password_expires_at->diffInDays(now());

            // Add warning header
            $response = $next($request);

            if (method_exists($response, 'header')) {
                $response->header('X-Password-Expires-In-Days', $daysRemaining);
                $response->header('X-Password-Expires-At', $user->password_expires_at->toISOString());
                $response->header('X-Password-Warning', 'Your password will expire soon. Please consider changing it.');
            }

            return $response;
        }

        return $next($request);
    }

    /**
     * Get the reason for password change requirement
     */
    private function getPasswordChangeReason($user): string
    {
        // Check if this is the first login (no password_changed_at)
        if (!$user->password_changed_at) {
            return 'first_login';
        }

        // Check if the password is expired
        if ($user->password_expires_at && $user->password_expires_at->isPast()) {
            return 'password_expired';
        }

        // Check if admin forced change
        if ($user->must_change_password) {
            return 'admin_required';
        }

        return 'policy_required';
    }
}
