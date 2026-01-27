<?php

namespace App\Http\Controllers;

use App\Models\NotificationRule;
use App\Models\Domain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificationSettingsController extends Controller
{
    /**
     * Show notification settings
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $domainId = $request->query('domain_id');

        $query = NotificationRule::where('user_id', $user->id);
        if ($domainId) {
            $query->where('domain_id', $domainId);
        } else {
            $query->whereNull('domain_id'); // Global rules only
        }

        $rules = $query->get()->keyBy('type');

        // Get all notification types
        $types = [
            NotificationRule::TYPE_RANK_DROP => 'Rank Drop',
            NotificationRule::TYPE_TOXIC_SPIKE => 'Toxic Spike',
            NotificationRule::TYPE_AUDIT_CRITICAL => 'Audit Critical',
            NotificationRule::TYPE_GOOGLE_DISCONNECT => 'Google Disconnect',
            NotificationRule::TYPE_BACKLINKS_LOST_SPIKE => 'Backlinks Lost Spike',
            NotificationRule::TYPE_META_PUBLISH_FAILED => 'Meta Publish Failed',
            NotificationRule::TYPE_QUOTA_LIMIT => 'Quota Limit',
        ];

        return Inertia::render('Settings/Notifications/Index', [
            'rules' => $rules,
            'types' => $types,
            'domainId' => $domainId,
        ]);
    }

    /**
     * Store or update notification rule
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'domain_id' => 'nullable|exists:domains,id',
            'is_enabled' => 'boolean',
            'severity' => 'required|in:info,warning,critical',
            'cooldown_minutes' => 'required|integer|min:1|max:10080', // Max 7 days
            'thresholds_json' => 'nullable|array',
            'channels_json' => 'required|array',
            'channels_json.in_app' => 'boolean',
            'channels_json.email' => 'boolean',
            'channels_json.webhook' => 'boolean',
        ]);

        $rule = NotificationRule::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'domain_id' => $validated['domain_id'] ?? null,
                'type' => $validated['type'],
            ],
            [
                'is_enabled' => $validated['is_enabled'] ?? true,
                'severity' => $validated['severity'],
                'cooldown_minutes' => $validated['cooldown_minutes'],
                'thresholds_json' => $validated['thresholds_json'] ?? null,
                'channels_json' => $validated['channels_json'],
            ]
        );

        return back()->with('success', 'Notification rule saved');
    }
}
