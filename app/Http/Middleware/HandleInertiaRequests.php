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
        // Force React app shell for the embedded audit report
        if ($request->is('Backlink/auditreport') || $request->is('backlink/auditreport')) {
            return parent::rootView($request);
        }

        // Use marketing template ONLY for marketing-named routes and blog routes
        if ($request->routeIs('marketing.*') || $request->routeIs('blog.*')) {
            return 'app-marketing';
        }

        // User Audit Report (auth required) uses React app
        if ($request->routeIs('audit-report.*') || $request->is('audit-report') || $request->is('audit-report/*')) {
            return parent::rootView($request);
        }

        // Public audit pages are Vue (use marketing app shell)
        if ($request->routeIs('audit.*') || $request->is('audit/*') || $request->is('r/*')) {
            return 'app-marketing';
        }

        // Auth routes (login, register, password reset, etc.) should use the React app template
        // These routes are NOT marketing pages
        if ($request->routeIs('login') ||
            $request->routeIs('register') ||
            $request->routeIs('password.*') ||
            $request->routeIs('verification.*') ||
            $request->is('login') ||
            $request->is('register') ||
            $request->is('forgot-password') ||
            $request->is('reset-password/*')) {
            return parent::rootView($request);
        }

        // For other unauthenticated public pages that aren't admin/dashboard/api, use marketing
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
            'features' => config('features', []),
            'oauthProviders' => [
                'google' => $this->isOauthProviderConfigured('google'),
                'apple' => $this->isOauthProviderConfigured('apple'),
                'github' => $this->isOauthProviderConfigured('github'),
                'microsoft' => $this->isOauthProviderConfigured('microsoft'),
                'facebook' => $this->isOauthProviderConfigured('facebook'),
            ],
        ];
    }

    private function isOauthProviderConfigured(string $provider): bool
    {
        $clientId = (string) config("services.{$provider}.client_id", '');
        $clientSecret = (string) config("services.{$provider}.client_secret", '');

        if ($clientId === '' || $clientSecret === '') {
            return false;
        }

        return match ($provider) {
            'microsoft' => class_exists(\SocialiteProviders\Microsoft\Provider::class),
            'apple' => class_exists(\SocialiteProviders\Apple\Provider::class),
            default => true,
        };
    }
}




