<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Domain;
use App\Services\Auth\DomainAccessService;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // Domain::class => DomainPolicy::class,
        \App\Models\Organization::class => \App\Policies\OrganizationPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Define domain capability gate
        Gate::define('domain.can', function ($user, Domain $domain, string $capability) {
            $service = app(DomainAccessService::class);
            return $service->can($user, $domain, $capability);
        });

        // Define specific capability gates for convenience
        Gate::define('domain.view', fn($user, Domain $domain) => Gate::allows('domain.can', [$domain, 'domains.view']));
        Gate::define('domain.manage', fn($user, Domain $domain) => Gate::allows('domain.can', [$domain, 'domains.manage']));
        Gate::define('analyzer.run', fn($user, Domain $domain) => Gate::allows('domain.can', [$domain, 'analyzer.run']));
        Gate::define('analyzer.view', fn($user, Domain $domain) => Gate::allows('domain.can', [$domain, 'analyzer.view']));
        Gate::define('google.connect', fn($user, Domain $domain) => Gate::allows('domain.can', [$domain, 'google.connect']));
        Gate::define('google.sync_now', fn($user, Domain $domain) => Gate::allows('domain.can', [$domain, 'google.sync_now']));
        Gate::define('backlinks.run', fn($user, Domain $domain) => Gate::allows('domain.can', [$domain, 'backlinks.run']));
        Gate::define('backlinks.view', fn($user, Domain $domain) => Gate::allows('domain.can', [$domain, 'backlinks.view']));
        Gate::define('meta.edit', fn($user, Domain $domain) => Gate::allows('domain.can', [$domain, 'meta.edit']));
        Gate::define('meta.publish', fn($user, Domain $domain) => Gate::allows('domain.can', [$domain, 'meta.publish']));
        Gate::define('insights.run', fn($user, Domain $domain) => Gate::allows('domain.can', [$domain, 'insights.run']));
        Gate::define('insights.view', fn($user, Domain $domain) => Gate::allows('domain.can', [$domain, 'insights.view']));
        Gate::define('reports.manage', fn($user, Domain $domain) => Gate::allows('domain.can', [$domain, 'reports.manage']));
        Gate::define('reports.view', fn($user, Domain $domain) => Gate::allows('domain.can', [$domain, 'reports.view']));
    }
}


