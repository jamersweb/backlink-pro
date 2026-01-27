<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Set the root template that should be used on the first Inertia page visit.
     */
    public function rootView(Request $request): string
    {
        // Use marketing template for marketing routes
        if ($request->routeIs('marketing.*')) {
            return 'app-marketing';
        }
        
        // Use marketing template for public/error pages that aren't admin/dashboard/api
        // This covers 404/500 error pages rendered via exception handlers
        if (!$request->is('admin/*') && !$request->is('dashboard/*') && !$request->is('api/*') && !$request->user()) {
            return 'app-marketing';
        }
        
        return parent::rootView($request);
    }

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'error' => fn () => $request->session()->get('error'),
                'success' => fn () => $request->session()->get('success'),
                'plan' => fn () => $request->session()->get('plan'),
            ],
            'site' => [
                'brand' => config('marketing_site.brand'),
                'nav' => config('marketing_site.nav'),
                'navSecondary' => config('marketing_site.nav_secondary'),
                'legal' => config('marketing_site.legal'),
                'social' => config('marketing_site.social'),
                'contacts' => config('marketing_site.contacts'),
                'seo' => config('marketing_site.seo'),
                'analytics' => config('marketing_site.analytics'),
                'urls' => config('marketing_site.urls'),
            ],
            'currentUrl' => $request->fullUrl(),
            'appUrl' => config('marketing_site.urls.app_url'),
            'maintenance' => config('marketing_site.maintenance'),
        ];
    }
}

