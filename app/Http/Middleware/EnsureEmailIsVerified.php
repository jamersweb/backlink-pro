<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip for admin users (they don't need email verification)
        if (Auth::check() && Auth::user()->hasRole('admin')) {
            return $next($request);
        }

        // Check if user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // Check if email is verified
        if (!Auth::user()->hasVerifiedEmail()) {
            // Allow access to verification routes
            if ($request->routeIs('verification.*') || $request->routeIs('logout')) {
                return $next($request);
            }

            // Redirect to verification notice page
            return redirect()->route('verification.notice')
                ->with('error', 'Please verify your email address before accessing this page.');
        }

        return $next($request);
    }
}
