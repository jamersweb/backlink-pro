<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\KeywordMap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class KeywordMapController extends Controller
{
    /**
     * Show keyword map
     */
    public function index(Domain $domain)
    {
        Gate::authorize('domain.view', $domain);

        $keywordMap = KeywordMap::where('domain_id', $domain->id)
            ->orderBy('keyword')
            ->paginate(50);

        return Inertia::render('Domains/Content/KeywordMap', [
            'domain' => $domain,
            'keywordMap' => $keywordMap,
        ]);
    }

    /**
     * Store manual keyword mapping
     */
    public function store(Domain $domain, Request $request)
    {
        Gate::authorize('domain.view', $domain);

        $validated = $request->validate([
            'keyword' => 'required|string|max:255',
            'url' => 'required|url',
        ]);

        // Check for conflicts
        $existing = KeywordMap::where('domain_id', $domain->id)
            ->where('keyword', $validated['keyword'])
            ->first();

        if ($existing && $existing->url !== $validated['url']) {
            return back()->with('error', "Keyword '{$validated['keyword']}' is already mapped to: {$existing->url}");
        }

        KeywordMap::updateOrCreate(
            [
                'domain_id' => $domain->id,
                'keyword' => $validated['keyword'],
            ],
            [
                'url' => $validated['url'],
                'source' => KeywordMap::SOURCE_MANUAL,
            ]
        );

        return back()->with('success', 'Keyword mapped');
    }

    /**
     * Delete keyword mapping
     */
    public function destroy(Domain $domain, KeywordMap $keywordMap)
    {
        Gate::authorize('domain.view', $domain);

        if ($keywordMap->domain_id !== $domain->id) {
            abort(403);
        }

        $keywordMap->delete();

        return back()->with('success', 'Keyword mapping deleted');
    }
}
