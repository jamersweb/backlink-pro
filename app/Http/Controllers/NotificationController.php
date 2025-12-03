<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificationController extends Controller
{
    /**
     * Show notifications page
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $query = UserNotification::where('user_id', $user->id)
            ->latest();

        // Filter by read/unread
        if ($request->has('filter') && $request->filter === 'unread') {
            $query->unread();
        } elseif ($request->has('filter') && $request->filter === 'read') {
            $query->read();
        }

        // Filter by type
        if ($request->has('type') && $request->type) {
            $query->type($request->type);
        }

        $notifications = $query->paginate(50)->withQueryString();

        // Get unread count
        $unreadCount = UserNotification::where('user_id', $user->id)->unread()->count();

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'filters' => $request->only(['filter', 'type']),
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($id)
    {
        $notification = UserNotification::where('user_id', Auth::id())
            ->findOrFail($id);
        
        $notification->markAsRead();

        return back()->with('success', 'Notification marked as read.');
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        UserNotification::where('user_id', Auth::id())
            ->unread()
            ->update([
                'read' => true,
                'read_at' => now(),
            ]);

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Delete notification
     */
    public function destroy($id)
    {
        $notification = UserNotification::where('user_id', Auth::id())
            ->findOrFail($id);
        
        $notification->delete();

        return back()->with('success', 'Notification deleted.');
    }
}

