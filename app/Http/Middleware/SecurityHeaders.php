<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SecurityHeaders Middleware
 * 
 * Adds essential security headers to all responses to protect against:
 * - XSS attacks
 * - Clickjacking
 * - MIME sniffing
 * - Information disclosure
 * 
 * @see https://owasp.org/www-project-secure-headers/
 */
class SecurityHeaders
{
    /**
     * Headers to remove for security
     */
    protected array $removeHeaders = [
        'X-Powered-By',
        'Server',
    ];

    /**
     * Security headers to add
     */
    protected array $securityHeaders = [
        // Prevent clickjacking attacks
        'X-Frame-Options' => 'SAMEORIGIN',
        
        // Enable XSS filtering in browser
        'X-XSS-Protection' => '1; mode=block',
        
        // Prevent MIME type sniffing
        'X-Content-Type-Options' => 'nosniff',
        
        // Control referrer information sent with requests
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        
        // Control browser feature permissions
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(self), payment=()',
        
        // Strict Transport Security (HTTPS only in production)
        // 'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Remove sensitive headers
        foreach ($this->removeHeaders as $header) {
            $response->headers->remove($header);
        }

        // Add security headers
        foreach ($this->securityHeaders as $header => $value) {
            $response->headers->set($header, $value);
        }

        // Add HSTS header only in production with HTTPS
        if (app()->environment('production') && $request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        // Add Content-Security-Policy for non-API routes
        if (!$request->is('api/*')) {
            $response->headers->set('Content-Security-Policy', $this->getCSP());
        }

        return $response;
    }

    /**
     * Get Content Security Policy header value
     */
    protected function getCSP(): string
    {
        $policies = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://js.stripe.com https://cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net",
            "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net data:",
            "img-src 'self' data: https: blob:",
            "connect-src 'self' https://api.stripe.com wss:",
            "frame-src 'self' https://js.stripe.com https://hooks.stripe.com https://backlinkproreport.lovable.app",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ];

        if (app()->environment('local')) {
            $policies = array_map(function ($policy) {
                if (str_starts_with($policy, 'script-src')) {
                    return $policy . ' http://localhost:5173 http://127.0.0.1:5173';
                }
                if (str_starts_with($policy, 'connect-src')) {
                    return $policy . ' ws://localhost:5173 ws://127.0.0.1:5173 http://localhost:5173 http://127.0.0.1:5173';
                }
                return $policy;
            }, $policies);
        }

        return implode('; ', $policies);
    }
}
