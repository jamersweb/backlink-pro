<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API Token Authentication Middleware
 * 
 * Validates API requests using X-API-Token header.
 * Used for internal API communication (Python workers, etc.)
 */
class ApiTokenAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiToken = trim($request->header('X-API-Token', ''));
        $expectedToken = trim(config('app.api_token', ''));

        // Check if API token is configured
        if (empty($expectedToken)) {
            return response()->json([
                'success' => false,
                'message' => 'API authentication not configured',
                'error_code' => 'API_NOT_CONFIGURED',
            ], 500);
        }

        // Validate the token
        if (empty($apiToken) || $apiToken !== $expectedToken) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing API token',
                'error_code' => 'UNAUTHORIZED',
            ], 401);
        }

        return $next($request);
    }
}
