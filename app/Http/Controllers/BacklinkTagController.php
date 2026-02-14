<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\BacklinkTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BacklinkTagController extends Controller
{
    /**
     * Create a tag for a domain
     */
    public function store(Request $request, Domain $domain)
    {
        if ($domain->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'color' => 'nullable|string|max:20',
        ]);

        BacklinkTag::create([
            'domain_id' => $domain->id,
            'name' => $validated['name'],
            'color' => $validated['color'] ?? '#6B7280',
        ]);

        return back()->with('success', 'Tag created.');
    }

    /**
     * Delete a tag
     */
    public function destroy(Domain $domain, BacklinkTag $tag)
    {
        if ($domain->user_id !== Auth::id() || $tag->domain_id !== $domain->id) {
            abort(403);
        }

        $tag->backlinks()->detach();
        $tag->delete();

        return back()->with('success', 'Tag deleted.');
    }
}
