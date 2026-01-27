<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Campaign;
use App\Models\Backlink;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class UsersController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('plan:id,name')
            ->latest()
            ->paginate(20);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'total' => User::count(),
        ]);
    }

    public function show($id)
    {
        $user = User::with([
            'plan:id,name,code,price_monthly',
            'campaigns' => function($query) {
                $query->latest()->limit(10);
            },
            'domains' => function($query) {
                $query->latest()->limit(10);
            },
            'connectedAccounts:id,user_id,email,status'
        ])
        ->withCount(['campaigns', 'domains', 'connectedAccounts'])
        ->findOrFail($id);

        // Get user statistics
        $stats = [
            'total_campaigns' => $user->campaigns()->count(),
            'active_campaigns' => $user->campaigns()->where('status', Campaign::STATUS_ACTIVE)->count(),
            'total_backlinks' => Backlink::whereHas('campaign', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })->count(),
            'verified_backlinks' => Backlink::whereHas('campaign', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })->where('status', 'verified')->count(),
            'total_domains' => $user->domains()->count(),
            'connected_gmail_accounts' => $user->connectedAccounts()->where('provider', 'google')->count(),
        ];

        // Get recent backlinks
        $recentBacklinks = Backlink::whereHas('campaign', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->with('campaign:id,name')
        ->latest()
        ->limit(10)
        ->get();

        return Inertia::render('Admin/Users/Show', [
            'user' => $user,
            'stats' => $stats,
            'recentBacklinks' => $recentBacklinks,
        ]);
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $user = User::with('plan')->findOrFail($id);
        $plans = Plan::active()->ordered()->get(['id', 'name', 'code', 'price_monthly'])->map(function($plan) {
            return [
                'id' => $plan->id,
                'name' => $plan->name,
                'code' => $plan->code,
                'price' => $plan->price_monthly ? ($plan->price_monthly / 100) : 0,
                'billing_interval' => 'monthly',
            ];
        });

        return Inertia::render('Admin/Users/Edit', [
            'user' => $user,
            'plans' => $plans,
        ]);
    }

    /**
     * Update user
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'plan_id' => 'nullable|exists:plans,id',
            'subscription_status' => 'nullable|in:active,cancelled,past_due,trialing',
        ]);

        $user->update($validated);

        return redirect()->route('admin.users.show', $user->id)
            ->with('success', 'User updated successfully.');
    }

    /**
     * Reset user password
     */
    public function resetPassword(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Password reset successfully.');
    }
}

