<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DomainAudit;
use App\Models\DomainBacklinkRun;
use App\Models\DomainMetaChange;
use App\Models\DomainInsightRun;
use App\Models\Domain;
use App\Jobs\Audits\StartDomainAuditJob;
use App\Jobs\Backlinks\StartBacklinkRunJob;
use App\Jobs\Meta\PublishMetaChangeJob;
use App\Jobs\Insights\GenerateDomainInsightsJob;
use App\Jobs\Integrations\SyncGscDomainJob;
use App\Jobs\Integrations\SyncGa4DomainJob;
use App\Services\System\ActivityLogger;
use Illuminate\Http\Request;

class RunRetryController extends Controller
{
    /**
     * Retry audit
     */
    public function retryAudit(DomainAudit $audit)
    {
        if ($audit->status !== DomainAudit::STATUS_FAILED) {
            return back()->withErrors(['error' => 'Only failed audits can be retried']);
        }

        // Create new audit with same settings
        $newAudit = DomainAudit::create([
            'domain_id' => $audit->domain_id,
            'user_id' => $audit->user_id,
            'status' => DomainAudit::STATUS_QUEUED,
            'settings_json' => $audit->settings_json,
        ]);

        // Log retry
        $logger = app(ActivityLogger::class);
        $logger->info(
            'audits',
            'retried',
            "Audit retried (old: {$audit->id}, new: {$newAudit->id})",
            null, // system retry
            $audit->domain_id,
            ['old_audit_id' => $audit->id, 'new_audit_id' => $newAudit->id, 'system_retry' => true]
        );

        // Dispatch job (no quota consumption for admin retry)
        StartDomainAuditJob::dispatch($newAudit->id);

        return back()->with('success', "Audit retried. New audit ID: {$newAudit->id}");
    }

    /**
     * Retry backlink run
     */
    public function retryBacklinks(DomainBacklinkRun $run)
    {
        if ($run->status !== DomainBacklinkRun::STATUS_FAILED) {
            return back()->withErrors(['error' => 'Only failed runs can be retried']);
        }

        // Create new run
        $newRun = DomainBacklinkRun::create([
            'domain_id' => $run->domain_id,
            'user_id' => $run->user_id,
            'status' => DomainBacklinkRun::STATUS_QUEUED,
            'provider' => $run->provider,
            'settings_json' => $run->settings_json,
        ]);

        $logger = app(ActivityLogger::class);
        $logger->info(
            'backlinks',
            'retried',
            "Backlink run retried (old: {$run->id}, new: {$newRun->id})",
            null,
            $run->domain_id,
            ['old_run_id' => $run->id, 'new_run_id' => $newRun->id, 'system_retry' => true]
        );

        StartBacklinkRunJob::dispatch($newRun->id);

        return back()->with('success', "Backlink run retried. New run ID: {$newRun->id}");
    }

    /**
     * Retry meta publish
     */
    public function retryMeta(DomainMetaChange $change)
    {
        if ($change->status !== DomainMetaChange::STATUS_FAILED) {
            return back()->withErrors(['error' => 'Only failed changes can be retried']);
        }

        // Re-queue the same change
        $change->update([
            'status' => DomainMetaChange::STATUS_QUEUED,
            'error_message' => null,
        ]);

        $logger = app(ActivityLogger::class);
        $logger->info(
            'meta',
            'retried',
            "Meta publish retried for change {$change->id}",
            null,
            $change->domain_id,
            ['change_id' => $change->id, 'system_retry' => true]
        );

        PublishMetaChangeJob::dispatch($change->id);

        return back()->with('success', "Meta publish retried");
    }

    /**
     * Retry insights
     */
    public function retryInsights(DomainInsightRun $run)
    {
        if ($run->status !== DomainInsightRun::STATUS_FAILED) {
            return back()->withErrors(['error' => 'Only failed runs can be retried']);
        }

        // Create new run
        $newRun = DomainInsightRun::create([
            'domain_id' => $run->domain_id,
            'user_id' => $run->user_id,
            'status' => DomainInsightRun::STATUS_QUEUED,
            'period_days' => $run->period_days,
        ]);

        $logger = app(ActivityLogger::class);
        $logger->info(
            'insights',
            'retried',
            "Insights run retried (old: {$run->id}, new: {$newRun->id})",
            null,
            $run->domain_id,
            ['old_run_id' => $run->id, 'new_run_id' => $newRun->id, 'system_retry' => true]
        );

        GenerateDomainInsightsJob::dispatch($run->domain_id, $run->period_days);

        return back()->with('success', "Insights run retried. New run ID: {$newRun->id}");
    }

    /**
     * Retry Google sync
     */
    public function retryGoogleSync(Domain $domain)
    {
        $integration = $domain->googleIntegration;
        if (!$integration) {
            return back()->withErrors(['error' => 'No Google integration found']);
        }

        $logger = app(ActivityLogger::class);
        $logger->info(
            'google',
            'sync_retried',
            "Google sync retried for domain {$domain->host}",
            null,
            $domain->id,
            ['system_retry' => true]
        );

        if ($integration->gsc_property) {
            SyncGscDomainJob::dispatch($domain->id);
        }
        if ($integration->ga4_property_id) {
            SyncGa4DomainJob::dispatch($domain->id);
        }

        return back()->with('success', "Google sync retried");
    }
}
