<?php

namespace App\Http\Controllers;

use App\Models\SiteAccount;
use App\Models\Campaign;
use App\Jobs\WaitForVerificationEmailJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SiteAccountController extends Controller
{
    /**
     * List all site accounts
     */
    public function index(Request $request)
    {
        $query = SiteAccount::where('user_id', Auth::id())
            ->with('campaign')
            ->withCount('backlinks')
            ->latest();

        if ($request->has('campaign_id') && $request->campaign_id) {
            $query->where('campaign_id', $request->campaign_id);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $siteAccounts = $query->paginate(20)->withQueryString();

        $campaigns = Campaign::where('user_id', Auth::id())
            ->get(['id', 'name']);

        return Inertia::render('SiteAccounts/Index', [
            'siteAccounts' => $siteAccounts,
            'campaigns' => $campaigns,
            'filters' => $request->only(['campaign_id', 'status']),
        ]);
    }

    /**
     * Show create form
     */
    public function create()
    {
        $campaigns = Campaign::where('user_id', Auth::id())
            ->get(['id', 'name']);

        return Inertia::render('SiteAccounts/Create', [
            'campaigns' => $campaigns,
        ]);
    }

    /**
     * Store new site account
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'campaign_id' => 'required|exists:campaigns,id',
            'site_domain' => 'required|string|max:255',
            'login_email' => 'required|email|max:255',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string',
            'status' => 'nullable|in:created,waiting_email,verified,failed',
        ]);

        // Verify campaign belongs to user
        $campaign = Campaign::where('user_id', Auth::id())
            ->findOrFail($validated['campaign_id']);

        $siteAccount = SiteAccount::create([
            'user_id' => Auth::id(),
            'campaign_id' => $validated['campaign_id'],
            'site_domain' => $validated['site_domain'],
            'login_email' => $validated['login_email'],
            'username' => $validated['username'] ?? null,
            'password' => $validated['password'] ?? null,
            'status' => $validated['status'] ?? SiteAccount::STATUS_CREATED,
        ]);

        if ($campaign->requires_email_verification && $campaign->gmailAccount) {
            $siteAccount->update([
                'status' => SiteAccount::STATUS_WAITING_EMAIL,
                'email_verification_status' => SiteAccount::EMAIL_STATUS_PENDING,
                'last_verification_check_at' => now(),
            ]);

            WaitForVerificationEmailJob::dispatch($siteAccount, $campaign->id);
        }

        return redirect()->route('site-accounts.index')
            ->with('success', 'Site account created successfully');
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $siteAccount = SiteAccount::where('user_id', Auth::id())
            ->with('campaign')
            ->findOrFail($id);

        $campaigns = Campaign::where('user_id', Auth::id())
            ->get(['id', 'name']);

        return Inertia::render('SiteAccounts/Edit', [
            'siteAccount' => $siteAccount,
            'campaigns' => $campaigns,
        ]);
    }

    /**
     * Update site account
     */
    public function update(Request $request, $id)
    {
        $siteAccount = SiteAccount::where('user_id', Auth::id())
            ->findOrFail($id);

        $validated = $request->validate([
            'campaign_id' => 'required|exists:campaigns,id',
            'site_domain' => 'required|string|max:255',
            'login_email' => 'required|email|max:255',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string',
            'status' => 'nullable|in:created,waiting_email,verified,failed',
        ]);

        // Verify campaign belongs to user
        Campaign::where('user_id', Auth::id())
            ->findOrFail($validated['campaign_id']);

        $siteAccount->update($validated);

        return redirect()->route('site-accounts.index')
            ->with('success', 'Site account updated successfully');
    }

    /**
     * Delete site account
     */
    public function destroy($id)
    {
        $siteAccount = SiteAccount::where('user_id', Auth::id())
            ->findOrFail($id);

        $siteAccount->delete();

        return redirect()->route('site-accounts.index')
            ->with('success', 'Site account deleted successfully');
    }
}

