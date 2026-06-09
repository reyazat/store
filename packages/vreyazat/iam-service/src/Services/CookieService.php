<?php

namespace IamApi\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Cookie Service for secure token storage
 * Implements Double Cookie Pattern for enhanced security
 */
class CookieService
{
    /**
     * Cookie names
     */
    private const ACCESS_TOKEN_COOKIE = 'access_token';
    private const REFRESH_TOKEN_COOKIE = 'refresh_token';
    private const CSRF_TOKEN_COOKIE = 'csrf_token';

    /**
     * Set authentication cookies
     *
     * @return array{response: Response|JsonResponse, csrf_token: string}
     */
    public function setAuthCookies(
        Response|JsonResponse $response,
        string $accessToken,
        string $refreshToken,
        int $accessTtlMinutes = 30,
        int $refreshTtlMinutes = 43200
    ): array {
        // Generate CSRF token first (before setting cookies)
        $csrfToken = $this->generateCsrfToken();

        // Convert TTL from minutes to Unix timestamp (Symfony 6+ expects timestamp or 0)
        $accessExpire  = $accessTtlMinutes > 0  ? time() + ($accessTtlMinutes  * 60) : 0;
        $refreshExpire = $refreshTtlMinutes > 0 ? time() + ($refreshTtlMinutes * 60) : 0;

        // Access Token - HttpOnly, Secure (configurable), SameSite
        $accessCookie = Cookie::create(
            self::ACCESS_TOKEN_COOKIE,
            $accessToken,
            $accessExpire,
            $this->getCookiePath(),
            $this->getCookieDomain(),
            $this->isSecure(),
            $this->isHttpOnly(),
            false,
            $this->getSameSitePolicy()
        );

        // Refresh Token - HttpOnly, Secure (configurable), SameSite
        $refreshCookie = Cookie::create(
            self::REFRESH_TOKEN_COOKIE,
            $refreshToken,
            $refreshExpire,
            $this->getCookiePath(),
            $this->getCookieDomain(),
            $this->isSecure(),
            $this->isHttpOnly(),
            false,
            $this->getSameSitePolicy()
        );

        // CSRF Token for Double Submit Cookie pattern
        // Not HttpOnly so JS can read it
        $csrfCookie = Cookie::create(
            self::CSRF_TOKEN_COOKIE,
            $csrfToken,
            $accessExpire,
            $this->getCookiePath(),
            $this->getCookieDomain(),
            $this->isSecure(),
            false, // NOT HttpOnly - JS needs to read this
            false,
            $this->getSameSitePolicy()
        );

        $response->cookie($accessCookie);
        $response->cookie($refreshCookie);
        $response->cookie($csrfCookie);

        // Store CSRF in session for validation
        session()->put('csrf_token', $csrfToken);

        return [
            'response' => $response,
            'csrf_token' => $csrfToken,
        ];
    }

    /**
     * Get access token from cookie
     */
    public function getAccessToken(Request $request): ?string
    {
        return $request->cookie(self::ACCESS_TOKEN_COOKIE);
    }

    /**
     * Get refresh token from cookie
     */
    public function getRefreshToken(Request $request): ?string
    {
        return $request->cookie(self::REFRESH_TOKEN_COOKIE);
    }

    /**
     * Get CSRF token from cookie
     */
    public function getCsrfToken(Request $request): ?string
    {
        return $request->cookie(self::CSRF_TOKEN_COOKIE);
    }

    /**
     * Validate CSRF token (Double Submit Cookie)
     */
    public function validateCsrfToken(Request $request): bool
    {
        $cookieToken = $this->getCsrfToken($request);
        $headerToken = $request->header('X-CSRF-Token');
        $sessionToken = session()->get('csrf_token');

        // All three must match
        if (!$cookieToken || !$headerToken || !$sessionToken) {
            return false;
        }

        return hash_equals($cookieToken, $headerToken) &&
               hash_equals($cookieToken, $sessionToken);
    }

    /**
     * Clear all auth cookies
     */
    public function clearAuthCookies(Response|JsonResponse $response): Response|JsonResponse
    {
        $cookies = [
            self::ACCESS_TOKEN_COOKIE,
            self::REFRESH_TOKEN_COOKIE,
            self::CSRF_TOKEN_COOKIE,
        ];

        foreach ($cookies as $cookieName) {
            // Expire the cookie by setting its expiry to 1 second in the past
            $cookie = Cookie::create(
                $cookieName,
                '',
                time() - 3600,
                $this->getCookiePath(),
                $this->getCookieDomain(),
                $this->isSecure(),
                true,
                false,
                $this->getSameSitePolicy()
            );
            $response->cookie($cookie);
        }

        session()->forget('csrf_token');

        return $response;
    }

    /**
     * Check if request has auth cookies
     */
    public function hasAuthCookies(Request $request): bool
    {
        return $request->hasCookie(self::ACCESS_TOKEN_COOKIE) &&
               $request->hasCookie(self::REFRESH_TOKEN_COOKIE);
    }

    /**
     * Generate secure CSRF token
     */
    private function generateCsrfToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Check if secure cookies should be used
     * In CDN → HTTP setups, the CDN terminates SSL and communicates with backend via HTTP
     * We use config to explicitly set cookie security, not auto-detect from environment
     */
    private function isSecure(): bool
    {
        return config('iam.cookie.secure', true);
    }

    /**
     * Check if cookies should be HttpOnly
     */
    private function isHttpOnly(): bool
    {
        return config('iam.cookie.http_only', true);
    }

    /**
     * Get SameSite policy
     */
    private function getSameSitePolicy(): string
    {
        return config('iam.cookie.same_site', 'lax');
    }

    /**
     * Get cookie path
     */
    private function getCookiePath(): string
    {
        return config('iam.cookie.path', '/');
    }

    /**
     * Get cookie domain
     */
    private function getCookieDomain(): ?string
    {
        return config('iam.cookie.domain');
    }
}
