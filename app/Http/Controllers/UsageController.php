<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\UsageEvent;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UsageController extends Controller
{
    /**
     * Show usage dashboard
     */
    public function index(Request $request, Organization $organization)
    {
        $this->authorize('view', $organization);

        $startDate = $request->input('start_date', now()->subDays(30)->startOfDay());
        $endDate = $request->input('end_date', now()->endOfDay());

        // Get usage by event type
        $usageByType = UsageEvent::where('organization_id', $organization->id)
            ->whereBetween('occurred_at', [$startDate, $endDate])
            ->selectRaw('event_type, SUM(quantity) as total')
            ->groupBy('event_type')
            ->get()
            ->keyBy('event_type');

        // Get daily audit creation count (last 30 days)
        $dailyAudits = UsageEvent::where('organization_id', $organization->id)
            ->where('event_type', UsageEvent::TYPE_AUDIT_CREATED)
            ->whereBetween('occurred_at', [now()->subDays(30)->startOfDay(), now()->endOfDay()])
            ->selectRaw('DATE(occurred_at) as date, SUM(quantity) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Get recent events
        $recentEvents = UsageEvent::where('organization_id', $organization->id)
            ->with('audit')
            ->orderBy('occurred_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'event_type' => $event->event_type,
                    'quantity' => $event->quantity,
                    'occurred_at' => $event->occurred_at->toIso8601String(),
                    'metadata' => $event->metadata,
                    'audit_url' => $event->audit ? route('audit.show', $event->audit) : null,
                ];
            });

        return Inertia::render('Organizations/Usage/Index', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'usageByType' => [
                'audit_created' => $usageByType->get('audit_created')->total ?? 0,
                'page_crawled' => $usageByType->get('page_crawled')->total ?? 0,
                'lighthouse_run' => $usageByType->get('lighthouse_run')->total ?? 0,
                'pdf_export' => $usageByType->get('pdf_export')->total ?? 0,
                'csv_export' => $usageByType->get('csv_export')->total ?? 0,
            ],
            'dailyAudits' => $dailyAudits->map(function ($day) {
                return [
                    'date' => $day->date,
                    'count' => (int) $day->count,
                ];
            }),
            'recentEvents' => $recentEvents,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }
}
