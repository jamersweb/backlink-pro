<?php

namespace App\Http\Middleware;

use App\Models\CustomDomain;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ResolveOrganizationFromHost
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        
        // Check if host matches a custom domain
        $customDomain = CustomDomain::where('domain', $host)
            ->where('status', CustomDomain::STATUS_ACTIVE)
            ->first();

        if ($customDomain) {
            $organization = $customDomain->organization;
            $brandingProfile = $organization->brandingProfile;

            // Set organization and branding in request
            $request->attributes->set('currentOrganization', $organization);
            $request->attributes->set('currentBrandingProfile', $brandingProfile);

            // Share with views
            View::share('currentOrganization', $organization);
            View::share('currentBrandingProfile', $brandingProfile);
        }

        return $next($request);
    }
}
