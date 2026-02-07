<?php

namespace App\Http\Controllers;

use App\Models\AuditMonitor;
use App\Models\Organization;
use App\Jobs\RunMonitorAuditJob;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MonitoringController extends Controller
{
    /**
     * List monitors
     */
    public function index(Organization $organization)
    {
        $this->authorize('view', $organization);

        $monitors = AuditMonitor::where('organization_id', $organization->id)
            ->with(['audits' => function ($query) {
                $query->orderBy('finished_at', 'desc')->limit(10);
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Monitoring/Index', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'monitors' => $monitors->map(function ($monitor) {
                return [
                    'id' => $monitor->id,
                    'name' => $monitor->name,
                    'target_url' => $monitor->target_url,
                    'schedule_rrule' => $monitor->schedule_rrule,
                    'is_enabled' => $monitor->is_enabled,
                    'last_audit' => $monitor->audits->first()?->finished_at?->toIso8601String(),
                    'created_at' => $monitor->created_at->toIso8601String(),
                ];
            }),
        ]);
    }

    /**
     * Create monitor
     */
    public function store(Request $request, Organization $organization)
    {
        $this->authorize('manage', $organization);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'target_url' => ['required', 'url', 'max:2048'],
            'schedule_rrule' => ['required', 'string'],
            'pages_limit' => ['required', 'integer', 'min:1', 'max:1000'],
            'crawl_depth' => ['required', 'integer', 'min:1', 'max:5'],
            'lighthouse_pages' => ['required', 'integer', 'min:0', 'max:10'],
            'notify_emails' => ['nullable', 'array'],
            'notify_emails.*' => ['email'],
            'slack_webhook_url' => ['nullable', 'url'],
        ]);

        $monitor = AuditMonitor::create([
            'organization_id' => $organization->id,
            'name' => $validated['name'],
            'target_url' => $validated['target_url'],
            'schedule_rrule' => $validated['schedule_rrule'],
            'pages_limit' => $validated['pages_limit'],
            'crawl_depth' => $validated['crawl_depth'],
            'lighthouse_pages' => $validated['lighthouse_pages'],
            'notify_emails' => $validated['notify_emails'] ?? [],
            'slack_webhook_url' => $validated['slack_webhook_url'] ?? null,
            'is_enabled' => true,
        ]);

        return redirect()->route('monitoring.show', [
            'organization' => $organization->id,
            'monitor' => $monitor->id,
        ])->with('success', 'Monitor created.');
    }

    /**
     * Show monitor
     */
    public function show(Organization $organization, AuditMonitor $monitor)
    {
        $this->authorize('view', $organization);

        if ($monitor->organization_id !== $organization->id) {
            abort(403);
        }

        $monitor->load(['audits.snapshots', 'alerts']);

        return Inertia::render('Monitoring/Show', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'monitor' => [
                'id' => $monitor->id,
                'name' => $monitor->name,
                'target_url' => $monitor->target_url,
                'schedule_rrule' => $monitor->schedule_rrule,
                'is_enabled' => $monitor->is_enabled,
                'audits' => $monitor->audits->map(function ($audit) {
                    return [
                        'id' => $audit->id,
                        'overall_score' => $audit->overall_score,
                        'finished_at' => $audit->finished_at?->toIso8601String(),
                    ];
                }),
                'alerts' => $monitor->alerts->map(function ($alert) {
                    return [
                        'id' => $alert->id,
                        'severity' => $alert->severity,
                        'title' => $alert->title,
                        'message' => $alert->message,
                        'created_at' => $alert->created_at->toIso8601String(),
                    ];
                }),
            ],
        ]);
    }

    /**
     * Run monitor manually
     */
    public function run(Organization $organization, AuditMonitor $monitor)
    {
        $this->authorize('manage', $organization);

        if ($monitor->organization_id !== $organization->id) {
            abort(403);
        }

        RunMonitorAuditJob::dispatch($monitor->id);

        return redirect()->back()->with('success', 'Monitor audit started.');
    }

    /**
     * Toggle monitor
     */
    public function toggle(Organization $organization, AuditMonitor $monitor)
    {
        $this->authorize('manage', $organization);

        if ($monitor->organization_id !== $organization->id) {
            abort(403);
        }

        $monitor->update([
            'is_enabled' => !$monitor->is_enabled,
        ]);

        return redirect()->back()->with('success', 'Monitor ' . ($monitor->is_enabled ? 'enabled' : 'disabled') . '.');
    }
}
