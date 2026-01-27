<?php

namespace App\Http\Controllers;

use App\Models\NotificationEndpoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WebhookController extends Controller
{
    /**
     * Show webhooks
     */
    public function index()
    {
        $webhooks = NotificationEndpoint::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Settings/Notifications/Webhooks', [
            'webhooks' => $webhooks,
        ]);
    }

    /**
     * Store webhook endpoint
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url',
            'secret' => 'nullable|string|max:255',
        ]);

        NotificationEndpoint::create([
            'user_id' => Auth::id(),
            'name' => $validated['name'],
            'url' => $validated['url'],
            'secret' => $validated['secret'] ?? null,
            'is_active' => true,
        ]);

        return back()->with('success', 'Webhook endpoint created');
    }

    /**
     * Toggle webhook active status
     */
    public function toggle(NotificationEndpoint $endpoint)
    {
        if ($endpoint->user_id !== Auth::id()) {
            abort(403);
        }

        $endpoint->update(['is_active' => !$endpoint->is_active]);

        return back()->with('success', 'Webhook ' . ($endpoint->is_active ? 'activated' : 'deactivated'));
    }

    /**
     * Delete webhook endpoint
     */
    public function destroy(NotificationEndpoint $endpoint)
    {
        if ($endpoint->user_id !== Auth::id()) {
            abort(403);
        }

        $endpoint->delete();

        return back()->with('success', 'Webhook endpoint deleted');
    }
}
