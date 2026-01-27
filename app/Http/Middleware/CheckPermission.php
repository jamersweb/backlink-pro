<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        // Admin always has access
        if ($request->user()->hasRole('admin')) {
            return $next($request);
        }

        // Check specific permission
        if (!$request->user()->hasPermissionTo($permission)) {
            abort(403, 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}
