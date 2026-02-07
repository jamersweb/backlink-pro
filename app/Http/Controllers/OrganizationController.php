<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Referral;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;

class OrganizationController extends Controller
{
    /**
     * List user's organizations
     */
    public function index()
    {
        $organizations = auth()->user()->organizationUsers()
            ->with('organization')
            ->get()
            ->pluck('organization');

        return Inertia::render('Organizations/Index', [
            'organizations' => $organizations->map(function ($org) {
                return [
                    'id' => $org->id,
                    'name' => $org->name,
                    'slug' => $org->slug,
                    'role' => $org->getUserRole(auth()->user()),
                ];
            }),
        ]);
    }

    /**
     * Show create form
     */
    public function create()
    {
        return Inertia::render('Organizations/Create');
    }

    /**
     * Store new organization
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $organization = Organization::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'owner_user_id' => auth()->id(),
        ]);

        // Add owner as organization user
        $organization->users()->create([
            'user_id' => auth()->id(),
            'role' => 'owner',
        ]);

        // Attach referral to organization if cookie exists
        $this->attachReferralToOrganization($organization, $request);

        return redirect()->route('orgs.settings.branding', $organization)
            ->with('success', 'Organization created successfully.');
    }

    /**
     * Attach referral to organization if cookie exists
     */
    protected function attachReferralToOrganization(Organization $organization, Request $request): void
    {
        $refCookie = $request->cookie('bp_ref');
        if (!$refCookie) {
            return;
        }

        try {
            $affiliateId = Crypt::decryptString($refCookie);
            
            // Find active referral for this visitor (within 30 days)
            $referral = Referral::where('affiliate_id', $affiliateId)
                ->where('visitor_id', $request->cookie('bp_visitor_id'))
                ->where('first_touch_at', '>', now()->subDays(30))
                ->whereNull('referred_org_id')
                ->first();

            if ($referral) {
                $referral->update([
                    'referred_org_id' => $organization->id,
                    'status' => Referral::STATUS_TRIAL_STARTED,
                ]);
            }
        } catch (\Exception $e) {
            // Invalid cookie, ignore
            \Log::debug('Failed to attach referral to org: ' . $e->getMessage());
        }
    }
}
