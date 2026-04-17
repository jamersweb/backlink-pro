<?php

namespace App\Providers;

use App\Models\Setting;
use App\Services\SeoAudit\LinkMetrics\DomainBacklinkLinkMetricsProvider;
use App\Services\SeoAudit\LinkMetrics\LinkMetricsProviderContract;
use App\Services\SeoAudit\LinkMetrics\NullLinkMetricsProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

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
        $this->mergeStripeSettingsFromDatabase();

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

    /**
     * Admin UI stores Stripe keys in the settings table; merge into config so
     * billing controllers and webhooks use the same credentials as .env.
     */
    protected function mergeStripeSettingsFromDatabase(): void
    {
        try {
            if (!Schema::hasTable('settings')) {
                return;
            }
        } catch (\Throwable) {
            return;
        }

        $key = Setting::get('stripe_key') ?: Setting::get('stripe_stripe_key');
        if (is_string($key) && $key !== '') {
            Config::set('services.stripe.key', $key);
        }

        $secret = Setting::get('stripe_secret') ?: Setting::get('stripe_stripe_secret');
        if (is_string($secret) && $secret !== '') {
            Config::set('services.stripe.secret', $secret);
        }

        $webhook = Setting::get('stripe_webhook_secret') ?: Setting::get('stripe_stripe_webhook_secret');
        if (is_string($webhook) && $webhook !== '') {
            Config::set('services.stripe.webhook_secret', $webhook);
        }

        $enabled = Setting::get('stripe_enabled');
        if ($enabled !== null && $enabled !== '') {
            Config::set('services.stripe.enabled', filter_var($enabled, FILTER_VALIDATE_BOOLEAN));
        }
    }
}
