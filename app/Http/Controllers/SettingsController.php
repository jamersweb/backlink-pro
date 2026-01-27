<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class SettingsController extends Controller
{
    /**
     * Show settings page
     */
    public function index()
    {
        $user = Auth::user();
        
        // Load plan and subscription info
        $plan = $user->plan;
        $connectedAccounts = $user->connectedAccounts()
            ->where('provider', 'google')
            ->withCount('campaigns')
            ->latest()
            ->get();

        return Inertia::render('Settings/Index', [
            'user' => $user->only(['id', 'name', 'email', 'created_at', 'plan_id', 'subscription_status', 'stripe_customer_id', 'trial_ends_at']),
            'plan' => $plan ? [
                'id' => $plan->id,
                'name' => $plan->name,
                'price' => $plan->price_monthly ? ($plan->price_monthly / 100) : 0,
                'billing_interval' => 'monthly',
                'max_domains' => $plan->getLimit('max_domains'),
                'max_campaigns' => $plan->getLimit('max_campaigns'),
                'daily_backlink_limit' => $plan->getLimit('daily_backlink_limit'),
            ] : null,
            'connectedAccounts' => $connectedAccounts,
        ]);
    }

    /**
     * Update profile
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
        ]);

        $user->update($validated);

        return back()->with('success', 'Profile updated successfully');
    }

    /**
     * Update password
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|current_password',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Password updated successfully');
    }
}

