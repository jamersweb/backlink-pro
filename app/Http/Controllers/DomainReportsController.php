<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\PublicReport;
use App\Services\Reports\PublicReportBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class DomainReportsController extends Controller
{
    /**
     * List reports for a domain
     */
    public function index(Domain $domain)
    {
        // Authorize domain ownership
        if ($domain->user_id !== Auth::id()) {
            abort(403);
        }

        $reports = PublicReport::where('domain_id', $domain->id)
            ->withCount('views')
            ->latest()
            ->get()
            ->map(function ($report) {
                return [
                    'id' => $report->id,
                    'title' => $report->title,
                    'status' => $report->status,
                    'token' => $report->token,
                    'public_url' => $report->getPublicUrl(),
                    'created_at' => $report->created_at,
                    'expires_at' => $report->expires_at,
                    'views_count' => $report->views_count,
                    'sections' => array_keys(array_filter($report->settings_json['sections'] ?? [])),
                ];
            });

        return Inertia::render('Domains/Reports/Index', [
            'domain' => $domain,
            'reports' => $reports,
        ]);
    }

    /**
     * Create a new report
     */
    public function store(Request $request, Domain $domain)
    {
        // Authorize domain ownership
        if ($domain->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'expires_at' => 'nullable|date|after:today',
            'password' => 'nullable|string|min:4',
            'sections' => 'required|array',
            'sections.analyzer' => 'boolean',
            'sections.google' => 'boolean',
            'sections.backlinks' => 'boolean',
            'sections.meta' => 'boolean',
            'sections.insights' => 'boolean',
            'sections.content' => 'boolean',
            'branding' => 'nullable|array',
            'branding.company_name' => 'nullable|string|max:255',
            'branding.logo_url' => 'nullable|url|max:500',
            'branding.accent_color' => 'nullable|string|max:7',
        ]);

        // Generate secure token
        $token = hash('sha256', random_bytes(32));

        // Create report
        $report = PublicReport::create([
            'domain_id' => $domain->id,
            'user_id' => Auth::id(),
            'token' => $token,
            'title' => $validated['title'] ?? "SEO Report for {$domain->name}",
            'status' => PublicReport::STATUS_ACTIVE,
            'expires_at' => $validated['expires_at'] ?? null,
            'password_hash' => isset($validated['password']) ? Hash::make($validated['password']) : null,
            'settings_json' => [
                'sections' => $validated['sections'],
                'branding' => $validated['branding'] ?? [
                    'company_name' => null,
                    'logo_url' => null,
                    'accent_color' => '#2E2E2E',
                ],
            ],
        ]);

        // Build initial snapshot
        $builder = new PublicReportBuilder($domain, $report);
        $snapshot = $builder->build();

        $report->update([
            'snapshot_json' => $snapshot,
            'snapshot_generated_at' => now(),
        ]);

        return back()->with('success', 'Report created successfully');
    }

    /**
     * Revoke a report
     */
    public function revoke(Domain $domain, PublicReport $report)
    {
        // Authorize domain ownership
        if ($domain->user_id !== Auth::id() || $report->domain_id !== $domain->id) {
            abort(403);
        }

        $report->update([
            'status' => PublicReport::STATUS_REVOKED,
        ]);

        return back()->with('success', 'Report revoked');
    }

    /**
     * Refresh report snapshot
     */
    public function refresh(Domain $domain, PublicReport $report)
    {
        // Authorize domain ownership
        if ($domain->user_id !== Auth::id() || $report->domain_id !== $domain->id) {
            abort(403);
        }

        $builder = new PublicReportBuilder($domain, $report);
        $snapshot = $builder->build();

        $report->update([
            'snapshot_json' => $snapshot,
            'snapshot_generated_at' => now(),
        ]);

        return back()->with('success', 'Report refreshed');
    }
}
