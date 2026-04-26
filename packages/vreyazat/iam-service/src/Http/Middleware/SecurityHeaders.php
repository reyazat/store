<?php

namespace IamApi\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security Headers Middleware
 * 
 * Adds comprehensive security headers to protect against XSS, clickjacking,
 * MIME sniffing, and other common web vulnerabilities.
 * 
 * Configuration is loaded from config/security.php
 */
class SecurityHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Content Security Policy (CSP)
        if (config('security.headers.csp.enabled', true)) {
            $cspDirectives = config('security.headers.csp.directives', []);
            $cspParts = [];
            
            foreach ($cspDirectives as $directive => $values) {
                $directiveName = str_replace('_', '-', $directive);
                $directiveValue = is_array($values) ? implode(' ', $values) : $values;
                $cspParts[] = "{$directiveName} {$directiveValue}";
            }
            
            if (!empty($cspParts)) {
                $response->headers->set('Content-Security-Policy', implode('; ', $cspParts));
            }
        }

        // X-Content-Type-Options
        if ($xContentTypeOptions = config('security.headers.x_content_type_options')) {
            $response->headers->set('X-Content-Type-Options', $xContentTypeOptions);
        }

        // X-Frame-Options
        if ($xFrameOptions = config('security.headers.x_frame_options')) {
            $response->headers->set('X-Frame-Options', $xFrameOptions);
        }

        // X-XSS-Protection
        if ($xXssProtection = config('security.headers.x_xss_protection')) {
            $response->headers->set('X-XSS-Protection', $xXssProtection);
        }

        // Referrer-Policy
        if ($referrerPolicy = config('security.headers.referrer_policy')) {
            $response->headers->set('Referrer-Policy', $referrerPolicy);
        }

        // Permissions-Policy
        $permissionsPolicy = config('security.headers.permissions_policy', []);
        if (!empty($permissionsPolicy)) {
            $permissions = [];
            foreach ($permissionsPolicy as $feature => $allowlist) {
                $allowlistStr = empty($allowlist) ? '()' : '(' . implode(' ', $allowlist) . ')';
                $permissions[] = "{$feature}={$allowlistStr}";
            }
            
            if (!empty($permissions)) {
                $response->headers->set('Permissions-Policy', implode(', ', $permissions));
            }
        }

        // Strict-Transport-Security (HSTS)
        $hstsConfig = config('security.headers.hsts', []);
        if ($request->secure() && ($hstsConfig['enabled'] ?? true)) {
            $hstsValue = 'max-age=' . ($hstsConfig['max_age'] ?? 31536000);
            
            if ($hstsConfig['include_subdomains'] ?? true) {
                $hstsValue .= '; includeSubDomains';
            }
            
            if ($hstsConfig['preload'] ?? true) {
                $hstsValue .= '; preload';
            }
            
            $response->headers->set('Strict-Transport-Security', $hstsValue);
        }

        // Remove server information disclosure
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }
}
