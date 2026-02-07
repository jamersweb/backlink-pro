<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\ApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ApiKeyController extends Controller
{
    /**
     * List API keys for organization
     */
    public function index(Organization $organization)
    {
        $this->authorize('manage', $organization);

        $keys = $organization->apiKeys()->get();

        return Inertia::render('Organizations/Settings/ApiKeys', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'apiKeys' => $keys->map(function ($key) {
                return [
                    'id' => $key->id,
                    'name' => $key->name,
                    'last_used_at' => $key->last_used_at?->toIso8601String(),
                    'is_active' => $key->is_active,
                    'scopes' => $key->scopes,
                ];
            }),
        ]);
    }

    /**
     * Create new API key
     */
    public function store(Request $request, Organization $organization)
    {
        $this->authorize('manage', $organization);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'scopes' => ['nullable', 'array'],
            'scopes.*' => ['string', 'in:audit:create,audit:read'],
        ]);

        // Generate key
        $rawKey = 'blp_' . Str::random(48);
        $keyHash = Hash::make($rawKey);

        $apiKey = ApiKey::create([
            'organization_id' => $organization->id,
            'name' => $validated['name'],
            'key_hash' => $keyHash,
            'scopes' => $validated['scopes'] ?? null,
        ]);

        // Return with raw key (only shown once)
        return back()->with('apiKeyCreated', [
            'id' => $apiKey->id,
            'name' => $apiKey->name,
            'key' => $rawKey, // Only shown once
        ]);
    }

    /**
     * Revoke API key
     */
    public function destroy(Organization $organization, ApiKey $apiKey)
    {
        $this->authorize('manage', $organization);

        if ($apiKey->organization_id !== $organization->id) {
            abort(403);
        }

        $apiKey->update(['is_active' => false]);

        return back()->with('success', 'API key revoked.');
    }

    /**
     * Get widget snippet
     */
    public function widgetSnippet(Organization $organization, ApiKey $apiKey)
    {
        $this->authorize('manage', $organization);

        if ($apiKey->organization_id !== $organization->id) {
            abort(403);
        }

        // Get the raw key (if just created) or show placeholder
        $snippet = '<script src="' . route('widget.js', ['key' => 'YOUR_API_KEY', 'org' => $organization->slug]) . '"></script>';

        return response()->json([
            'snippet' => $snippet,
            'instructions' => 'Replace YOUR_API_KEY with your actual API key. The widget will automatically render on pages where this script is included.',
        ]);
    }
}
