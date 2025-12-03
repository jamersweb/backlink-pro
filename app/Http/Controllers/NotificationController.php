<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Log;
use App\Models\Backlink;
use App\Models\Campaign;
use Inertia\Inertia;

class NotificationController extends Controller
{
    /**
     * Show notifications page
     */
    public function index()
    {
        $user = Auth::user();
        $campaignIds = Campaign::where('user_id', $user->id)->pluck('id');
        
        // Get recent notifications from logs
        $notifications = Log::whereIn('campaign_id', $campaignIds)
            ->with(['campaign' => function($query) {
                $query->select('id', 'name');
            }])
            ->latest()
            ->limit(50)
            ->get()
            ->map(function($log) {
                return [
                    'id' => $log->id,
                    'type' => $this->getNotificationType($log->level),
                    'title' => $this->getNotificationTitle($log),
                    'message' => $log->message ?? 'Notification',
                    'campaign' => $log->campaign->name ?? 'System',
                    'created_at' => $log->created_at,
                    'read' => false, // You can add read status to logs table later
                ];
            });
        
        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
        ]);
    }

    private function getNotificationType($level)
    {
        $types = [
            'error' => 'error',
            'warning' => 'warning',
            'info' => 'info',
            'success' => 'success',
        ];
        return $types[$level] ?? 'info';
    }

    private function getNotificationTitle($log)
    {
        if (str_contains(strtolower($log->message ?? ''), 'verified')) {
            return 'Backlink Verified';
        }
        if (str_contains(strtolower($log->message ?? ''), 'failed')) {
            return 'Backlink Failed';
        }
        if (str_contains(strtolower($log->message ?? ''), 'created')) {
            return 'Campaign Created';
        }
        return 'System Notification';
    }
}

