<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleMiddleware
{
    /**
     * Supported locales.
     *
     * @var array
     */
    protected array $supportedLocales = ['fa', 'en', 'ar', 'fr'];

    /**
     * Handle an incoming request.
     *
     * Priority:
     * 1. Query parameter (?locale=fa)
     * 2. Request header (Accept-Language or X-Locale)
     * 3. User preference (from JWT or session)
     * 4. Cookie
     * 5. Browser Accept-Language header
     * 6. Default locale
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        $locale = $this->detectLocale($request);

        // Validate and set locale
        if ($this->isValidLocale($locale)) {
            App::setLocale($locale);

            // Store in request for later use
            $request->attributes->set('locale', $locale);

            // Set locale cookie for next request (optional)
            if (config('app.locale_cookie_enabled', true)) {
                cookie()->queue('locale', $locale, 60 * 24 * 365); // 1 year
            }
        }

        $response = $next($request);

        // Add Content-Language header to response
        if ($response instanceof Response) {
            $response->headers->set('Content-Language', App::getLocale());
        }

        return $response;
    }

    /**
     * Detect locale from various sources.
     *
     * @param Request $request
     * @return string
     */
    protected function detectLocale(Request $request): string
    {
        // 1. Query parameter (the highest priority)
        if ($locale = $request->query('locale')) {
            return $locale;
        }

        // 2. Custom header (X-Locale)
        if ($locale = $request->header('X-Locale')) {
            return $locale;
        }

        // 3. User preference from JWT
        if (($payload = $request->attributes->get('jwt_payload')) && isset($payload->locale)) {
            return $payload->locale;
        }

        // 4. User preference from authenticated user
        if (($user = $request->attributes->get('user')) && isset($user->preferred_locale)) {
            return $user->preferred_locale;
        }

        // 5. Cookie
        if ($locale = $request->cookie('locale')) {
            return $locale;
        }

        // 6. Accept-Language header (browser preference)
        if ($locale = $this->parseAcceptLanguage($request)) {
            return $locale;
        }

        // 7. Default locale
        return config('app.locale', 'fa');
    }

    /**
     * Parse Accept-Language header.
     *
     * @param Request $request
     * @return string|null
     */
    protected function parseAcceptLanguage(Request $request): ?string
    {
        $acceptLanguage = $request->header('Accept-Language');

        if (!$acceptLanguage) {
            return null;
        }

        // Parse Accept-Language header
        // Example: "fa-IR,fa;q=0.9,en-US;q=0.8,en;q=0.7"
        $languages = explode(',', $acceptLanguage);

        foreach ($languages as $language) {
            // Extract language code (before - or ;)
            $locale = strtok(trim($language), '-;');

            if ($this->isValidLocale($locale)) {
                return $locale;
            }
        }

        return null;
    }

    /**
     * Check if locale is valid.
     *
     * @param string|null $locale
     * @return bool
     */
    protected function isValidLocale(?string $locale): bool
    {
        if (!$locale) {
            return false;
        }

        return in_array($locale, $this->supportedLocales, true);
    }
}
