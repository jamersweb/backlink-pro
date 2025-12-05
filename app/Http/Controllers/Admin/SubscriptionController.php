<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Plan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
{
    /**
     * Display a listing of subscriptions with filters
     */
    public function index(Request $request)
    {
        $query = User::with('plan:id,name,price,billing_interval')
            ->where(function($q) {
                $q->whereNotNull('plan_id')
                  ->orWhereNotNull('stripe_subscription_id');
            });

        // Filter by subscription status
        if ($request->has('status') && $request->status !== 'all') {
            if ($request->status === 'pending') {
                // Pending subscriptions (incomplete or trialing)
                $query->where(function($q) {
                    $q->where('subscription_status', 'incomplete')
                      ->orWhere('subscription_status', 'trialing');
                });
            } elseif ($request->status === 'canceled') {
                $query->where('subscription_status', 'canceled');
            } elseif ($request->status === 'active') {
                $query->where('subscription_status', 'active');
            } elseif ($request->status === 'past_due') {
                $query->where('subscription_status', 'past_due');
            }
        }

        // Filter by first-time purchase
        if ($request->has('first_time') && $request->first_time === 'true') {
            // Users who have a subscription but created their account/subscription recently (within 30 days)
            // This is a simplified approach - in production, you might want to track subscription history
            $query->whereNotNull('stripe_subscription_id')
                  ->whereNotNull('plan_id')
                  ->whereRaw('created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)');
        }

        // Search by customer name or email
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Get statistics
        $stats = [
            'total' => User::where(function($q) {
                $q->whereNotNull('plan_id')
                  ->orWhereNotNull('stripe_subscription_id')
                  ->orWhereNotNull('subscription_status');
            })->count(),
            'active' => User::where('subscription_status', 'active')->count(),
            'pending' => User::whereIn('subscription_status', ['incomplete', 'trialing'])->count(),
            'canceled' => User::where('subscription_status', 'canceled')->count(),
            'past_due' => User::where('subscription_status', 'past_due')->count(),
        ];

        // Get first-time purchase count (users with subscription created within last 30 days)
        $stats['first_time'] = User::whereNotNull('stripe_subscription_id')
            ->whereNotNull('plan_id')
            ->whereRaw('created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)')
            ->count();

        $subscriptions = $query->latest('updated_at')->paginate(20)->withQueryString();

        // Transform data for frontend
        $subscriptions->getCollection()->transform(function($user) {
            // Determine if this is a first-time purchase
            $isFirstTime = $user->stripe_subscription_id &&
                          $user->plan_id &&
                          $user->created_at &&
                          $user->created_at->diffInDays(now()) <= 30;

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'plan' => $user->plan ? [
                    'id' => $user->plan->id,
                    'name' => $user->plan->name,
                    'price' => $user->plan->price,
                    'billing_interval' => $user->plan->billing_interval,
                ] : null,
                'subscription_status' => $user->subscription_status,
                'stripe_customer_id' => $user->stripe_customer_id,
                'stripe_subscription_id' => $user->stripe_subscription_id,
                'is_first_time' => $isFirstTime,
                'created_at' => $user->created_at?->toDateTimeString(),
                'updated_at' => $user->updated_at?->toDateTimeString(),
                'trial_ends_at' => $user->trial_ends_at?->toDateTimeString(),
            ];
        });

        return Inertia::render('Admin/Subscriptions/Index', [
            'subscriptions' => $subscriptions,
            'stats' => $stats,
            'filters' => [
                'status' => $request->status ?? 'all',
                'first_time' => $request->first_time ?? 'false',
                'search' => $request->search ?? '',
            ],
        ]);
    }

    /**
     * Show subscription details
     */
    public function show($id)
    {
        $user = User::with([
            'plan:id,name,price,billing_interval,description',
            'campaigns' => function($query) {
                $query->latest()->limit(10);
            },
        ])
        ->withCount('campaigns')
        ->findOrFail($id);

        $isFirstTime = $user->stripe_subscription_id &&
                      $user->plan_id &&
                      $user->created_at &&
                      $user->created_at->diffInDays(now()) <= 30;

        return Inertia::render('Admin/Subscriptions/Show', [
            'subscription' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'plan' => $user->plan,
                'subscription_status' => $user->subscription_status,
                'stripe_customer_id' => $user->stripe_customer_id,
                'stripe_subscription_id' => $user->stripe_subscription_id,
                'is_first_time' => $isFirstTime,
                'created_at' => $user->created_at?->toDateTimeString(),
                'updated_at' => $user->updated_at?->toDateTimeString(),
                'trial_ends_at' => $user->trial_ends_at?->toDateTimeString(),
                'email_verified_at' => $user->email_verified_at?->toDateTimeString(),
            ],
            'campaigns' => $user->campaigns,
            'campaigns_count' => $user->campaigns_count,
        ]);
    }
}

