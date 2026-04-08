<?php

namespace App\Providers;

use App\Services\SeoAudit\LinkMetrics\DomainBacklinkLinkMetricsProvider;
use App\Services\SeoAudit\LinkMetrics\LinkMetricsProviderContract;
use App\Services\SeoAudit\LinkMetrics\NullLinkMetricsProvider;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LinkMetricsProviderContract::class, function ($app) {
            return match (config('seo_audit.link_metrics.driver', 'null')) {
                'domain_backlinks' => $app->make(DomainBacklinkLinkMetricsProvider::class),
                default => $app->make(NullLinkMetricsProvider::class),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (class_exists(\SocialiteProviders\Manager\SocialiteWasCalled::class)) {
            Event::listen(\SocialiteProviders\Manager\SocialiteWasCalled::class, function ($event): void {
                if (class_exists(\SocialiteProviders\Microsoft\Provider::class)) {
                    $event->extendSocialite('microsoft', \SocialiteProviders\Microsoft\Provider::class);
                }

                if (class_exists(\SocialiteProviders\Apple\Provider::class)) {
                    $event->extendSocialite('apple', \SocialiteProviders\Apple\Provider::class);
                }
            });
        }
    }
}
