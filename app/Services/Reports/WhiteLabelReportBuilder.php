<?php

namespace App\Services\Reports;

use App\Models\Domain;
use App\Models\DomainAudit;
use App\Models\DomainAuditIssue;
use App\Models\DomainBacklinkRun;
use App\Models\DomainRefDomain;
use App\Models\Organization;
use App\Models\RankKeyword;
use App\Models\RankProject;
use App\Models\RankResult;
use App\Models\WhiteLabelReportProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class WhiteLabelReportBuilder
{
    public function build(Organization $organization, WhiteLabelReportProfile $profile): array
    {
        $branding = $organization->brandingProfile;
        $domain = $this->resolveDomain($profile);
        $latestAudit = $this->resolveLatestAudit($domain);
        $latestBacklinkRun = $this->resolveLatestBacklinkRun($domain);
        $keywordRows = $this->resolveKeywordRows($organization, $domain, $profile);
        $topIssues = $this->resolveTopIssues($latestAudit);
        $recommendations = $this->resolveRecommendations($profile, $topIssues);
        $summaryBullets = $this->resolveExecutiveSummary($latestAudit, $latestBacklinkRun, $keywordRows, $profile);

        $issueCounts = [
            'critical' => $latestAudit ? DomainAuditIssue::where('domain_audit_id', $latestAudit->id)->where('severity', DomainAuditIssue::SEVERITY_CRITICAL)->count() : 0,
            'warning' => $latestAudit ? DomainAuditIssue::where('domain_audit_id', $latestAudit->id)->where('severity', DomainAuditIssue::SEVERITY_WARNING)->count() : 0,
            'info' => $latestAudit ? DomainAuditIssue::where('domain_audit_id', $latestAudit->id)->where('severity', DomainAuditIssue::SEVERITY_INFO)->count() : 0,
        ];

        $topRefDomains = $latestBacklinkRun
            ? DomainRefDomain::query()
                ->where('run_id', $latestBacklinkRun->id)
                ->orderByDesc('backlinks_count')
                ->limit(5)
                ->get()
                ->map(fn (DomainRefDomain $refDomain) => [
                    'domain' => $refDomain->domain,
                    'backlinks_count' => $refDomain->backlinks_count,
                    'risk_score' => $refDomain->risk_score,
                ])
                ->values()
                ->all()
            : [];

        $backlinkSummary = $latestBacklinkRun?->summary_json ?? $latestBacklinkRun?->totals_json ?? [];
        $auditSummary = $latestAudit?->summary_json ?? [];

        return [
            'generated_at' => now(),
            'profile' => [
                'id' => $profile->id,
                'client_name' => $profile->client_name,
                'client_website' => $profile->client_website,
                'report_title' => $profile->report_title,
                'reporting_period_label' => $this->formatReportingPeriod($profile),
                'target_keywords' => $this->splitLines($profile->target_keywords),
                'notes' => $profile->notes,
                'recommendations' => $this->splitLines($profile->recommendations),
            ],
            'branding' => [
                'enabled' => (bool) ($branding?->white_label_enabled),
                'brand_name' => $branding?->brand_name ?: $organization->name,
                'logo_url' => $branding?->logo_path ? url(Storage::disk('public')->url($branding->logo_path)) : null,
                'primary_color' => $branding?->primary_color ?: '#FF5626',
                'secondary_color' => $branding?->secondary_color ?: '#1C1B1B',
                'website' => $branding?->website,
                'support_email' => $branding?->support_email,
                'support_phone' => $branding?->support_phone,
                'company_address' => $branding?->company_address,
                'footer_text' => $branding?->report_footer_text ?: 'Professional SEO reporting powered by your saved workspace branding.',
            ],
            'domain' => $domain ? [
                'id' => $domain->id,
                'name' => $domain->name,
                'host' => $domain->host,
                'url' => $domain->url,
            ] : null,
            'sections' => [
                'cover' => [
                    'client_name' => $profile->client_name,
                    'client_website' => $profile->client_website,
                    'report_title' => $profile->report_title,
                    'reporting_period' => $this->formatReportingPeriod($profile),
                ],
                'executive_summary' => [
                    'available' => !empty($summaryBullets) || !empty($profile->notes),
                    'summary_bullets' => $summaryBullets,
                    'custom_summary' => $profile->notes,
                ],
                'keyword_overview' => [
                    'available' => count($keywordRows) > 0 || count($this->splitLines($profile->target_keywords)) > 0,
                    'tracked_keywords' => $keywordRows,
                    'target_keywords' => $this->splitLines($profile->target_keywords),
                ],
                'backlink_overview' => [
                    'available' => !empty($backlinkSummary),
                    'total_backlinks' => $backlinkSummary['total_backlinks'] ?? $backlinkSummary['backlinks'] ?? null,
                    'referring_domains' => $backlinkSummary['ref_domains'] ?? $backlinkSummary['referring_domains'] ?? null,
                    'follow_links' => $backlinkSummary['follow'] ?? null,
                    'nofollow_links' => $backlinkSummary['nofollow'] ?? null,
                    'captured_at' => $latestBacklinkRun?->finished_at,
                    'top_ref_domains' => $topRefDomains,
                ],
                'technical_seo_summary' => [
                    'available' => (bool) $latestAudit,
                    'health_score' => $latestAudit?->health_score,
                    'pages_crawled' => $auditSummary['pages_crawled'] ?? $auditSummary['pages_crawled_count'] ?? $latestAudit?->pages()?->count(),
                    'issue_counts' => $issueCounts,
                    'captured_at' => $latestAudit?->finished_at,
                    'top_issues' => $topIssues,
                ],
                'recommendations' => [
                    'available' => count($recommendations) > 0,
                    'items' => $recommendations,
                ],
                'footer_branding' => [
                    'footer_text' => $branding?->report_footer_text ?: 'Thank you for reviewing this SEO report.',
                    'website' => $branding?->website,
                    'support_email' => $branding?->support_email,
                    'support_phone' => $branding?->support_phone,
                    'company_address' => $branding?->company_address,
                ],
            ],
        ];
    }

    protected function resolveDomain(WhiteLabelReportProfile $profile): ?Domain
    {
        if ($profile->domain && $profile->domain->user_id === $profile->user_id) {
            return $profile->domain;
        }

        $host = $this->normalizeHost($profile->client_website);
        if (!$host) {
            return null;
        }

        return Domain::query()
            ->where('user_id', $profile->user_id)
            ->where(function ($query) use ($host) {
                $query->where('host', $host)
                    ->orWhere('name', $host)
                    ->orWhere('url', 'like', '%' . $host . '%');
            })
            ->orderBy('id')
            ->first();
    }

    protected function resolveLatestAudit(?Domain $domain): ?DomainAudit
    {
        if (!$domain) {
            return null;
        }

        return DomainAudit::query()
            ->with(['pages'])
            ->where('domain_id', $domain->id)
            ->where('status', DomainAudit::STATUS_COMPLETED)
            ->latest('finished_at')
            ->first();
    }

    protected function resolveLatestBacklinkRun(?Domain $domain): ?DomainBacklinkRun
    {
        if (!$domain) {
            return null;
        }

        return DomainBacklinkRun::query()
            ->where('domain_id', $domain->id)
            ->where('status', DomainBacklinkRun::STATUS_COMPLETED)
            ->latest('finished_at')
            ->first();
    }

    protected function resolveKeywordRows(Organization $organization, ?Domain $domain, WhiteLabelReportProfile $profile): array
    {
        $host = $this->normalizeHost($profile->client_website) ?: $domain?->host;
        if (!$host) {
            return [];
        }

        $rankProject = RankProject::query()
            ->where('organization_id', $organization->id)
            ->where(function ($query) use ($host) {
                $query->where('target_domain', $host)
                    ->orWhere('target_domain', 'www.' . $host)
                    ->orWhere('target_domain', str_replace('www.', '', $host));
            })
            ->orderBy('id')
            ->first();

        if (!$rankProject) {
            return [];
        }

        return RankKeyword::query()
            ->where('rank_project_id', $rankProject->id)
            ->where('is_active', true)
            ->orderBy('keyword')
            ->limit(10)
            ->get()
            ->map(function (RankKeyword $keyword) {
                $latestResult = RankResult::query()
                    ->where('rank_keyword_id', $keyword->id)
                    ->latest('fetched_at')
                    ->first();

                return [
                    'keyword' => $keyword->keyword,
                    'position' => $latestResult?->position,
                    'matched_url' => $latestResult?->found_url,
                    'captured_at' => $latestResult?->fetched_at,
                ];
            })
            ->values()
            ->all();
    }

    protected function resolveTopIssues(?DomainAudit $audit): array
    {
        if (!$audit) {
            return [];
        }

        return DomainAuditIssue::query()
            ->where('domain_audit_id', $audit->id)
            ->orderByRaw("CASE severity WHEN 'critical' THEN 1 WHEN 'warning' THEN 2 ELSE 3 END")
            ->latest('id')
            ->limit(6)
            ->get()
            ->map(function (DomainAuditIssue $issue) {
                return [
                    'severity' => $issue->severity,
                    'type' => $issue->type,
                    'message' => $issue->message,
                ];
            })
            ->values()
            ->all();
    }

    protected function resolveRecommendations(WhiteLabelReportProfile $profile, array $topIssues): array
    {
        $custom = $this->splitLines($profile->recommendations);
        if (!empty($custom)) {
            return $custom;
        }

        return collect($topIssues)
            ->take(4)
            ->map(function (array $issue) {
                return trim(($issue['type'] ?: 'SEO task') . ': ' . $issue['message']);
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function resolveExecutiveSummary(?DomainAudit $audit, ?DomainBacklinkRun $backlinkRun, array $keywordRows, WhiteLabelReportProfile $profile): array
    {
        $bullets = [];

        if ($audit) {
            $bullets[] = sprintf(
                'Technical health score is %s with %d critical issues and %d warning issues in the latest audit.',
                $audit->health_score ?? 'N/A',
                DomainAuditIssue::where('domain_audit_id', $audit->id)->where('severity', DomainAuditIssue::SEVERITY_CRITICAL)->count(),
                DomainAuditIssue::where('domain_audit_id', $audit->id)->where('severity', DomainAuditIssue::SEVERITY_WARNING)->count()
            );
        }

        if ($backlinkRun) {
            $summary = $backlinkRun->summary_json ?? $backlinkRun->totals_json ?? [];
            $bullets[] = sprintf(
                'Backlink visibility currently shows %s backlinks across %s referring domains.',
                $summary['total_backlinks'] ?? $summary['backlinks'] ?? 'available',
                $summary['ref_domains'] ?? $summary['referring_domains'] ?? 'available'
            );
        }

        if (!empty($keywordRows)) {
            $rankedKeywords = collect($keywordRows)->filter(fn (array $row) => !is_null($row['position']));
            if ($rankedKeywords->isNotEmpty()) {
                $averagePosition = round($rankedKeywords->avg('position'), 1);
                $bullets[] = sprintf(
                    'Tracked keyword coverage is available for %d keywords with an average observed position of %s.',
                    $rankedKeywords->count(),
                    $averagePosition
                );
            }
        }

        if (empty($bullets) && $profile->notes) {
            $bullets[] = 'This report currently relies on the custom client summary because linked live SEO data is not yet available for the selected website.';
        }

        return $bullets;
    }

    protected function splitLines(?string $value): array
    {
        if (!$value) {
            return [];
        }

        return collect(preg_split('/\r\n|\r|\n|,/', $value))
            ->map(fn (?string $line) => trim((string) $line))
            ->filter()
            ->values()
            ->all();
    }

    protected function formatReportingPeriod(WhiteLabelReportProfile $profile): string
    {
        $start = $profile->reporting_period_start ? Carbon::parse($profile->reporting_period_start) : null;
        $end = $profile->reporting_period_end ? Carbon::parse($profile->reporting_period_end) : null;

        if (!$start || !$end) {
            return 'Period not specified';
        }

        return $start->format('M j, Y') . ' - ' . $end->format('M j, Y');
    }

    protected function normalizeHost(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (!$host && !str_contains($url, '://')) {
            $host = parse_url('https://' . $url, PHP_URL_HOST);
        }

        if (!$host) {
            return null;
        }

        return strtolower(preg_replace('/^www\./', '', $host));
    }
}
