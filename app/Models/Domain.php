<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Domain extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'name',
        'url',
        'host',
        'platform',
        'verification_status',
        'verification_method',
        'verification_token',
        'verified_at',
        'default_settings',
        'status',
        'meta_snippet_key',
    ];

    protected $casts = [
        'default_settings' => 'array',
        'verified_at' => 'datetime',
    ];

    /**
     * Domain statuses
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    /**
     * Platform options
     */
    const PLATFORM_WORDPRESS = 'wordpress';
    const PLATFORM_SHOPIFY = 'shopify';
    const PLATFORM_CUSTOM = 'custom';
    const PLATFORM_WEBFLOW = 'webflow';
    const PLATFORM_WIX = 'wix';
    const PLATFORM_SQUARESPACE = 'squarespace';
    const PLATFORM_OTHER = 'other';

    /**
     * Verification statuses
     */
    const VERIFICATION_UNVERIFIED = 'unverified';
    const VERIFICATION_PENDING = 'pending';
    const VERIFICATION_VERIFIED = 'verified';

    /**
     * Verification methods
     */
    const VERIFICATION_METHOD_DNS_TXT = 'dns_txt';
    const VERIFICATION_METHOD_HTML_FILE = 'html_file';
    const VERIFICATION_METHOD_META_TAG = 'meta_tag';

    /**
     * Get the user that owns this domain
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all campaigns for this domain
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    /**
     * Get all audits for this domain
     */
    public function audits(): HasMany
    {
        return $this->hasMany(DomainAudit::class);
    }

    /**
     * Get total backlinks count for this domain
     */
    public function getTotalBacklinksAttribute(): int
    {
        return $this->campaigns()
            ->withCount('backlinks')
            ->get()
            ->sum('backlinks_count');
    }

    /**
     * Scope for active domains
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Get Google integration for this domain
     */
    public function googleIntegration(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(DomainGoogleIntegration::class);
    }

    /**
     * Get GSC daily metrics
     */
    public function gscDailyMetrics(): HasMany
    {
        return $this->hasMany(GscDailyMetric::class);
    }

    /**
     * Get GA4 daily metrics
     */
    public function ga4DailyMetrics(): HasMany
    {
        return $this->hasMany(Ga4DailyMetric::class);
    }

    /**
     * Get all backlink runs for this domain
     */
    public function backlinkRuns(): HasMany
    {
        return $this->hasMany(DomainBacklinkRun::class);
    }

    /**
     * Get connector for this domain (new system)
     */
    public function connector(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(DomainConnector::class);
    }

    /**
     * Get meta connector for this domain (legacy)
     */
    public function metaConnector(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(DomainMetaConnector::class);
    }

    /**
     * Get all meta pages for this domain
     */
    public function metaPages(): HasMany
    {
        return $this->hasMany(DomainMetaPage::class);
    }

    /**
     * Get all meta changes for this domain
     */
    public function metaChanges(): HasMany
    {
        return $this->hasMany(DomainMetaChange::class);
    }

    /**
     * Get all insight runs for this domain
     */
    public function insightRuns(): HasMany
    {
        return $this->hasMany(DomainInsightRun::class);
    }

    /**
     * Get all tasks for this domain
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(DomainTask::class);
    }

    /**
     * Get all alerts for this domain
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(DomainAlert::class);
    }

    /**
     * Get all KPI snapshots for this domain
     */
    public function kpiSnapshots(): HasMany
    {
        return $this->hasMany(DomainKpiSnapshot::class);
    }

    /**
     * Get all public reports for this domain
     */
    public function publicReports(): HasMany
    {
        return $this->hasMany(PublicReport::class);
    }

    /**
     * Get the team this domain belongs to
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get all domain access records
     */
    public function access(): HasMany
    {
        return $this->hasMany(DomainAccess::class);
    }

    /**
     * Get all plans for this domain
     */
    public function plans(): HasMany
    {
        return $this->hasMany(DomainPlan::class);
    }

    /**
     * Get onboarding record
     */
    public function onboarding(): HasOne
    {
        return $this->hasOne(DomainOnboarding::class);
    }

    /**
     * Get provider preferences
     */
    public function providerPreferences(): HasMany
    {
        return $this->hasMany(DomainProviderPreference::class);
    }

    /**
     * Get cost logs
     */
    public function costLogs(): HasMany
    {
        return $this->hasMany(CrawlCostLog::class);
    }

    /**
     * Get automation campaigns
     */
    public function automationCampaigns(): HasMany
    {
        return $this->hasMany(AutomationCampaign::class);
    }

    /**
     * Get snippet installation
     */
    public function snippetInstallation(): HasOne
    {
        return $this->hasOne(SnippetInstallation::class);
    }

    /**
     * Get snippet events
     */
    public function snippetEvents(): HasMany
    {
        return $this->hasMany(SnippetEvent::class);
    }

    /**
     * Get snippet performance
     */
    public function snippetPerformance(): HasMany
    {
        return $this->hasMany(SnippetPerformance::class);
    }

    /**
     * Get snippet commands
     */
    public function snippetCommands(): HasMany
    {
        return $this->hasMany(SnippetCommand::class);
    }

    /**
     * Get keyword opportunities
     */
    public function keywordOpportunities(): HasMany
    {
        return $this->hasMany(KeywordOpportunity::class);
    }

    /**
     * Get content briefs
     */
    public function contentBriefs(): HasMany
    {
        return $this->hasMany(ContentBrief::class);
    }

    /**
     * Get keyword map
     */
    public function keywordMap(): HasMany
    {
        return $this->hasMany(KeywordMap::class);
    }

    /**
     * Get rank keywords for this domain
     */
    public function rankKeywords(): HasMany
    {
        return $this->hasMany(RankKeyword::class);
    }

    /**
     * Get rank checks for this domain
     */
    public function rankChecks(): HasMany
    {
        return $this->hasMany(RankCheck::class);
    }
}

