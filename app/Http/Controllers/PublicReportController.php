<?php

namespace App\Http\Controllers;

use App\Models\PublicReport;
use App\Models\PublicReportView;
use App\Services\Reports\PublicReportBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Inertia\Inertia;
use Carbon\Carbon;

class PublicReportController extends Controller
{
    /**
     * Show public report
     */
    public function show(string $token)
    {
        $report = PublicReport::where('token', $token)->firstOrFail();

        // Check if accessible
        if (!$report->isAccessible()) {
            if ($report->isExpired()) {
                $report->update(['status' => PublicReport::STATUS_EXPIRED]);
            }
            return Inertia::render('PublicReport/Expired', [
                'report' => $report,
            ]);
        }

        // Check password
        $unlocked = $this->isUnlocked($request, $token);
        if ($report->requiresPassword() && !$unlocked) {
            return Inertia::render('PublicReport/Unlock', [
                'token' => $token,
            ]);
        }

        // Build or use cached snapshot
        $builder = new PublicReportBuilder($report->domain, $report);
        if (!$builder->isSnapshotFresh()) {
            $snapshot = $builder->build();
            $report->update([
                'snapshot_json' => $snapshot,
                'snapshot_generated_at' => now(),
            ]);
        } else {
            $snapshot = $report->snapshot_json ?? [];
        }

        // Record view
        $this->recordView($report, $request);

        return Inertia::render('PublicReport/Show', [
            'report' => $report,
            'snapshot' => $snapshot,
            'branding' => $report->settings_json['branding'] ?? [],
        ]);
    }

    /**
     * Unlock password-protected report
     */
    public function unlock(Request $request, string $token)
    {
        $report = PublicReport::where('token', $token)->firstOrFail();

        $validated = $request->validate([
            'password' => 'required|string',
        ]);

        if (!$report->verifyPassword($validated['password'])) {
            return back()->withErrors([
                'password' => 'Incorrect password',
            ]);
        }

        // Set cookie for 24 hours
        $cookie = Cookie::make("report_unlocked_{$token}", '1', 60 * 24);

        return redirect()->route('public.report.show', $token)
            ->withCookie($cookie);
    }

    /**
     * Check if report is unlocked
     */
    protected function isUnlocked(Request $request, string $token): bool
    {
        return $request->cookie("report_unlocked_{$token}") === '1';
    }

    /**
     * Record view
     */
    protected function recordView(PublicReport $report, Request $request): void
    {
        // Hash IP for privacy
        $ipHash = hash('sha256', $request->ip() . config('app.key'));

        PublicReportView::create([
            'public_report_id' => $report->id,
            'ip_hash' => $ipHash,
            'user_agent' => $request->userAgent(),
            'viewed_at' => now(),
        ]);
    }
}
