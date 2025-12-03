<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockedSite;
use App\Services\BlocklistService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BlockedSiteController extends Controller
{
    /**
     * List all blocked sites
     */
    public function index(Request $request)
    {
        $query = BlockedSite::latest();

        // Filter by active/inactive
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $query->where(function($q) use ($request) {
                $q->where('domain', 'like', '%' . $request->search . '%')
                  ->orWhere('reason', 'like', '%' . $request->search . '%')
                  ->orWhere('blocked_by', 'like', '%' . $request->search . '%');
            });
        }

        $blockedSites = $query->paginate(50)->withQueryString();

        $stats = [
            'total' => BlockedSite::count(),
            'active' => BlockedSite::where('is_active', true)->count(),
            'inactive' => BlockedSite::where('is_active', false)->count(),
        ];

        return Inertia::render('Admin/BlockedSites/Index', [
            'blockedSites' => $blockedSites,
            'stats' => $stats,
            'filters' => $request->only(['is_active', 'search']),
        ]);
    }

    /**
     * Store a new blocked site
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'domain' => 'required|string|max:255',
            'reason' => 'nullable|string|max:1000',
            'blocked_by' => 'nullable|string|max:255',
        ]);

        BlocklistService::blockDomain(
            $validated['domain'],
            $validated['reason'] ?? null,
            $validated['blocked_by'] ?? auth()->user()->email
        );

        return back()->with('success', 'Site blocked successfully.');
    }

    /**
     * Update blocked site
     */
    public function update(Request $request, $id)
    {
        $blockedSite = BlockedSite::findOrFail($id);

        $validated = $request->validate([
            'domain' => 'required|string|max:255',
            'reason' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        $blockedSite->update($validated);

        return back()->with('success', 'Blocked site updated successfully.');
    }

    /**
     * Delete blocked site
     */
    public function destroy($id)
    {
        $blockedSite = BlockedSite::findOrFail($id);
        $blockedSite->delete();

        return back()->with('success', 'Blocked site removed successfully.');
    }

    /**
     * Toggle active status
     */
    public function toggle($id)
    {
        $blockedSite = BlockedSite::findOrFail($id);
        $blockedSite->update(['is_active' => !$blockedSite->is_active]);

        return back()->with('success', 'Blocked site status updated.');
    }
}
