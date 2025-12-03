<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DomainController extends Controller
{
    /**
     * List all domains
     */
    public function index()
    {
        $domains = Domain::where('user_id', Auth::id())
            ->withCount('campaigns')
            ->latest()
            ->get();

        return Inertia::render('Domains/Index', [
            'domains' => $domains,
        ]);
    }

    /**
     * Show create form
     */
    public function create()
    {
        return Inertia::render('Domains/Create');
    }

    /**
     * Store new domain
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'default_settings' => 'nullable|array',
            'status' => 'nullable|in:active,inactive',
        ]);

        $domain = Domain::create([
            'user_id' => Auth::id(),
            'name' => $validated['name'],
            'default_settings' => $validated['default_settings'] ?? [],
            'status' => $validated['status'] ?? Domain::STATUS_ACTIVE,
        ]);

        return redirect()->route('domains.index')
            ->with('success', 'Domain created successfully');
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $domain = Domain::where('user_id', Auth::id())
            ->findOrFail($id);

        return Inertia::render('Domains/Edit', [
            'domain' => $domain,
        ]);
    }

    /**
     * Update domain
     */
    public function update(Request $request, $id)
    {
        $domain = Domain::where('user_id', Auth::id())
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'default_settings' => 'nullable|array',
            'status' => 'nullable|in:active,inactive',
        ]);

        $domain->update($validated);

        return redirect()->route('domains.index')
            ->with('success', 'Domain updated successfully');
    }

    /**
     * Delete domain
     */
    public function destroy($id)
    {
        $domain = Domain::where('user_id', Auth::id())
            ->findOrFail($id);

        $domain->delete();

        return redirect()->route('domains.index')
            ->with('success', 'Domain deleted successfully');
    }
}

