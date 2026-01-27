<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Domain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificationController extends Controller
{
    /**
     * Show notifications inbox
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Notification::where('user_id', $user->id)
            ->with('domain');

        // Filters
        if ($request->has('domain_id') && $request->domain_id) {
            $query->where('domain_id', $request->domain_id);
        }
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }
        if ($request->has('severity') && $request->severity) {
            $query->where('severity', $request->severity);
        }
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', '!=', Notification::STATUS_ARCHIVED);
        }

        // Filter out snoozed
        $query->where(function($q) {
            $q->whereNull('snoozed_until')
              ->orWhere('snoozed_until', '<', now());
        });

        $notifications = $query->orderBy('created_at', 'desc')->paginate(50);

        // Get unread count
        $unreadCount = Notification::where('user_id', $user->id)
            ->where('status', Notification::STATUS_UNREAD)
            ->where(function($q) {
                $q->whereNull('snoozed_until')
                  ->orWhere('snoozed_until', '<', now());
            })
            ->count();

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'filters' => $request->only(['domain_id', 'type', 'severity', 'status']),
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markRead(Notification $notification)
    {
        if ($notification->user_id !== Auth::id()) {
            abort(403);
        }

        $notification->update(['status' => Notification::STATUS_READ]);

        return back();
    }

    /**
     * Archive notification
     */
    public function archive(Notification $notification)
    {
        if ($notification->user_id !== Auth::id()) {
            abort(403);
        }

        $notification->update(['status' => Notification::STATUS_ARCHIVED]);

        return back();
    }

    /**
     * Snooze notification
     */
    public function snooze(Notification $notification, Request $request)
    {
        if ($notification->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'hours' => 'required|integer|min:1|max:168', // Max 7 days
        ]);

        $notification->update([
            'snoozed_until' => now()->addHours($validated['hours']),
        ]);

        return back();
    }

    /**
     * Mute notifications by type or domain
     */
    public function mute(Request $request)
    {
        $validated = $request->validate([
            'type' => 'nullable|string',
            'domain_id' => 'nullable|exists:domains,id',
        ]);

        $query = Notification::where('user_id', Auth::id());

        if ($validated['type']) {
            $query->where('type', $validated['type']);
        }
        if ($validated['domain_id']) {
            $query->where('domain_id', $validated['domain_id']);
        }

        $query->update(['muted' => true]);

        return back()->with('success', 'Notifications muted');
    }

    /**
     * Get unread count (for badge)
     */
    public function unreadCount()
    {
        $count = Notification::where('user_id', Auth::id())
            ->where('status', Notification::STATUS_UNREAD)
            ->where('muted', false)
            ->where(function($q) {
                $q->whereNull('snoozed_until')
                  ->orWhere('snoozed_until', '<', now());
            })
            ->count();

        return response()->json(['count' => $count]);
    }
}
