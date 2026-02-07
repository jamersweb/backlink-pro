<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\SeoAlertRule;
use App\Models\SeoAlert;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SeoAlertController extends Controller
{
    /**
     * List alert rules
     */
    public function index(Organization $organization)
    {
        $this->authorize('view', $organization);

        $rules = SeoAlertRule::where('organization_id', $organization->id)
            ->withCount('alerts')
            ->orderBy('created_at', 'desc')
            ->get();

        $alerts = SeoAlert::where('organization_id', $organization->id)
            ->with('rule')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return Inertia::render('SEO/Alerts', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'rules' => $rules->map(function ($rule) {
                return [
                    'id' => $rule->id,
                    'name' => $rule->name,
                    'type' => $rule->type,
                    'is_enabled' => $rule->is_enabled,
                    'config' => $rule->config,
                    'alerts_count' => $rule->alerts_count,
                ];
            }),
            'alerts' => $alerts->map(function ($alert) {
                return [
                    'id' => $alert->id,
                    'severity' => $alert->severity,
                    'title' => $alert->title,
                    'message' => $alert->message,
                    'diff' => $alert->diff,
                    'created_at' => $alert->created_at->toIso8601String(),
                ];
            }),
        ]);
    }

    /**
     * Create alert rule
     */
    public function storeRule(Request $request, Organization $organization)
    {
        $this->authorize('manage', $organization);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:rank_drop,gsc_clicks_drop,ga4_sessions_drop,conversion_drop'],
            'config' => ['required', 'array'],
            'config.threshold' => ['required', 'numeric', 'min:0', 'max:100'],
            'config.lookback_days' => ['required', 'integer', 'min:1', 'max:30'],
            'notify_emails' => ['nullable', 'array'],
            'notify_emails.*' => ['email'],
        ]);

        SeoAlertRule::create([
            'organization_id' => $organization->id,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'config' => $validated['config'],
            'notify_emails' => $validated['notify_emails'] ?? [],
            'is_enabled' => true,
        ]);

        return redirect()->back()->with('success', 'Alert rule created.');
    }

    /**
     * Toggle rule
     */
    public function toggleRule(Organization $organization, SeoAlertRule $rule)
    {
        $this->authorize('manage', $organization);

        if ($rule->organization_id !== $organization->id) {
            abort(403);
        }

        $rule->update([
            'is_enabled' => !$rule->is_enabled,
        ]);

        return redirect()->back()->with('success', 'Rule ' . ($rule->is_enabled ? 'enabled' : 'disabled') . '.');
    }
}
