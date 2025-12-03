<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Proxy;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProxyController extends Controller
{
    public function index(Request $request)
    {
        $query = Proxy::query();

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        // Filter by country
        if ($request->has('country') && $request->country) {
            $query->where('country', $request->country);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('host', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $proxies = $query->orderBy('error_count', 'asc')
            ->orderBy('last_used_at', 'desc')
            ->paginate(50)->withQueryString();

        // Get stats
        $stats = [
            'total' => Proxy::count(),
            'active' => Proxy::where('status', Proxy::STATUS_ACTIVE)->count(),
            'disabled' => Proxy::where('status', Proxy::STATUS_DISABLED)->count(),
            'blacklisted' => Proxy::where('status', Proxy::STATUS_BLACKLISTED)->count(),
            'healthy' => Proxy::where('status', Proxy::STATUS_ACTIVE)->where('error_count', '<', 3)->count(),
            'unhealthy' => Proxy::where('error_count', '>=', 3)->count(),
        ];

        // Get unique countries
        $countries = Proxy::select('country')
            ->whereNotNull('country')
            ->distinct()
            ->orderBy('country')
            ->pluck('country');

        return Inertia::render('Admin/Proxies/Index', [
            'proxies' => $proxies,
            'stats' => $stats,
            'countries' => $countries,
            'filters' => $request->only(['status', 'type', 'country', 'search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'type' => 'required|in:http,https,socks5',
            'country' => 'nullable|string|max:100',
            'status' => 'required|in:active,disabled,blacklisted',
        ]);

        Proxy::create($validated);

        return redirect()->route('admin.proxies.index')
            ->with('success', 'Proxy added successfully.');
    }

    public function update(Request $request, Proxy $proxy)
    {
        $validated = $request->validate([
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'type' => 'required|in:http,https,socks5',
            'country' => 'nullable|string|max:100',
            'status' => 'required|in:active,disabled,blacklisted',
        ]);

        $proxy->update($validated);

        return redirect()->route('admin.proxies.index')
            ->with('success', 'Proxy updated successfully.');
    }

    public function destroy(Proxy $proxy)
    {
        $proxy->delete();

        return redirect()->route('admin.proxies.index')
            ->with('success', 'Proxy deleted successfully.');
    }

    public function resetErrors(Proxy $proxy)
    {
        $proxy->resetErrors();

        return back()->with('success', 'Error count reset successfully.');
    }

    public function test(Proxy $proxy)
    {
        // Simple test - just mark as used for now
        // In production, you'd want to actually test the proxy connection
        $proxy->markUsed();

        return back()->with('success', 'Proxy test completed. Marked as used.');
    }
}

