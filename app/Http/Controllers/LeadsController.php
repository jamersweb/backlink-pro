<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Lead;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeadsController extends Controller
{
    /**
     * List leads for organization
     */
    public function index(Request $request, Organization $organization)
    {
        $this->authorize('view', $organization);

        $query = $organization->leads()->with('audit');

        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('source')) {
            $query->where('source', $request->source);
        }

        $leads = $query->orderBy('created_at', 'desc')->paginate(50);

        return Inertia::render('Organizations/Leads/Index', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'leads' => $leads->map(function ($lead) {
                return [
                    'id' => $lead->id,
                    'email' => $lead->email,
                    'name' => $lead->name,
                    'company' => $lead->company,
                    'website' => $lead->website,
                    'source' => $lead->source,
                    'status' => $lead->status,
                    'audit_id' => $lead->audit_id,
                    'audit_url' => $lead->audit ? route('audit.show', $lead->audit) : null,
                    'created_at' => $lead->created_at->toIso8601String(),
                    'metadata' => $lead->metadata,
                ];
            }),
            'filters' => [
                'status' => $request->status,
                'source' => $request->source,
            ],
        ]);
    }

    /**
     * Show lead details
     */
    public function show(Organization $organization, Lead $lead)
    {
        $this->authorize('view', $organization);

        if ($lead->organization_id !== $organization->id) {
            abort(403);
        }

        return Inertia::render('Organizations/Leads/Show', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'lead' => [
                'id' => $lead->id,
                'email' => $lead->email,
                'name' => $lead->name,
                'phone' => $lead->phone,
                'company' => $lead->company,
                'website' => $lead->website,
                'source' => $lead->source,
                'status' => $lead->status,
                'notes' => $lead->notes,
                'audit_id' => $lead->audit_id,
                'created_at' => $lead->created_at->toIso8601String(),
                'metadata' => $lead->metadata,
            ],
        ]);
    }

    /**
     * Update lead status/notes
     */
    public function update(Request $request, Organization $organization, Lead $lead)
    {
        $this->authorize('manage', $organization);

        if ($lead->organization_id !== $organization->id) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => ['nullable', 'in:new,contacted,qualified,won,lost'],
            'notes' => ['nullable', 'string'],
        ]);

        $lead->update($validated);

        return back()->with('success', 'Lead updated.');
    }
}
