<!-- Audit Dashboard UI Redesign ? Template v1 -->
<template>
    <div class="audit-shell">
        <Head>
            <title>SEO Audit Report - {{ audit.url }}</title>
        </Head>

        <header class="audit-header">
            <div class="audit-container header-grid">
                <div>
                    <h1 class="audit-title">SEO Dashboard</h1>
                    <p class="audit-subtitle">
                        <span class="muted">{{ audit.url }}</span>
                        <span class="divider">|</span>
                        <span class="muted">Last run:</span>
                        {{ audit.finished_at ? new Date(audit.finished_at).toLocaleString() : 'Recently' }}
                    </p>
                </div>
                <div class="header-actions">
                    <a :href="rerunUrl" class="btn-secondary">Re-run Audit</a>
                    <a :href="exportUrl" class="btn-primary">Export PDF</a>
                </div>
            </div>
        </header>

        <main class="audit-container">
            <div class="status-row">
                <span
                    :class="{
                        'status-pill status-running': audit.status === 'queued' || audit.status === 'running',
                        'status-pill status-success': audit.status === 'completed',
                        'status-pill status-failed': audit.status === 'failed',
                    }"
                >
                    {{ statusLabel }}
                </span>
                <button v-if="isOwner" @click="shareAudit" class="ghost-link">Share report</button>
            </div>

            <div v-if="audit.status === 'queued' || audit.status === 'running'" class="state-card">
                <h2 class="section-title">Running SEO audit...</h2>
                <p class="muted mt-1">This may take a few moments.</p>
                <div v-if="audit.pages_scanned > 0" class="mt-4">
                    <div class="muted">
                        Scanned {{ audit.pages_scanned }} / {{ audit.pages_discovered || '?' }} pages
                    </div>
                    <div class="progress-track mt-2">
                        <div class="progress-bar" :style="{ width: (audit.progress_percent || 0) + '%' }"></div>
                    </div>
                </div>
            </div>

            <div v-else-if="audit.status === 'failed'" class="state-card is-error">
                <h2 class="section-title">Audit Failed</h2>
                <p class="muted mt-1">{{ audit.error || 'An error occurred while running the audit.' }}</p>
            </div>

            <div v-else-if="audit.status === 'completed'" class="audit-grid">
                <DashboardCard title="Rankings" class="col-span-4">
                    <div class="mini-grid">
                        <MiniStat label="Google Rankings" :value="display(rankingScore)" />
                        <MiniStat label="Google Change" :value="display(rankingDelta)" :trend="rankingDelta" />
                    </div>
                    <div class="chart-wrap">
                        <ApexStackedBar
                            v-if="rankingSeries.length"
                            :series="rankingSeries"
                            :categories="rankingCategories"
                            height="180"
                        />
                        <div v-else class="chart-skeleton">N/A</div>
                    </div>
                </DashboardCard>

                <DashboardCard title="Google Analytics" class="col-span-4">
                    <div class="ga-layout">
                        <div class="chart-wrap">
                            <ApexDonutChart
                                v-if="ga4Donut.series.length"
                                :series="ga4Donut.series"
                                :labels="ga4Donut.labels"
                                height="220"
                            />
                            <div v-else class="chart-skeleton">N/A</div>
                        </div>
                        <ul class="legend" v-if="ga4Donut.labels.length">
                            <li v-for="(label, idx) in ga4Donut.labels" :key="label">
                                <span class="legend-dot" :style="{ background: ga4Donut.colors[idx] }"></span>
                                <span>{{ label }}</span>
                                <span class="legend-value">{{ ga4Donut.series[idx] }}</span>
                            </li>
                        </ul>
                        <div v-else class="muted">Connect GA4 to see channel data.</div>
                    </div>
                    <div class="mini-grid mt-3">
                        <MiniStat label="Sessions" :value="display(ga4Summary?.current?.sessions)" />
                        <MiniStat label="Conversions" :value="display(ga4Summary?.current?.conversions)" />
                    </div>
                    <div v-if="ga4Connected" class="ga-controls">
                        <select v-model="ga4SelectedPropertyId" class="control-input">
                            <option value="">Select a property</option>
                            <option
                                v-for="property in ga4Properties"
                                :key="property.property_id"
                                :value="property.property_id"
                            >
                                {{ property.display_name }} ({{ property.property_id }})
                            </option>
                        </select>
                        <button @click="saveGa4Property(false)" class="btn-secondary">Save & Load</button>
                        <button @click="refreshGa4Summary" class="btn-secondary">Refresh</button>
                    </div>
                    <div v-if="!ga4Connected" class="cta-row">
                        <a :href="ga4ConnectUrl" class="btn-secondary">Connect GA4</a>
                    </div>
                </DashboardCard>

                <DashboardCard title="Google Lighthouse" class="col-span-4">
                    <div class="radial-grid">
                        <ApexRadialGauge
                            label="Performance"
                            :value="pagespeedKpis?.categories?.performance_score"
                        />
                        <ApexRadialGauge
                            label="SEO"
                            :value="pagespeedKpis?.categories?.seo_score"
                        />
                        <ApexRadialGauge
                            label="Accessibility"
                            :value="pagespeedKpis?.categories?.accessibility_score"
                        />
                        <ApexRadialGauge
                            label="Best Practices"
                            :value="pagespeedKpis?.categories?.best_practices_score"
                        />
                    </div>
                </DashboardCard>

                <DashboardCard title="Backlinks" class="col-span-8">
                    <div class="backlink-grid">
                        <div class="chart-wrap">
                            <ApexRadialGauge
                                label="Citation Flow"
                                :value="citationFlow"
                                :size="200"
                            />
                        </div>
                        <div class="chart-wrap">
                            <ApexStackedBar
                                v-if="backlinkSeries.length"
                                :series="backlinkSeries"
                                :categories="backlinkCategories"
                                height="200"
                            />
                            <div v-else class="chart-skeleton">N/A</div>
                        </div>
                    </div>
                </DashboardCard>

                <DashboardCard title="Google Search Console" class="col-span-4">
                    <div class="gsc-stat">
                        <div class="kpi-number">{{ display(gscData?.current?.impressions) }}</div>
                        <div class="muted">Impressions</div>
                    </div>
                    <div class="chart-wrap">
                        <ApexSparkline
                            v-if="gscTrend.length"
                            :series="gscTrend"
                            height="120"
                        />
                        <div v-else class="chart-skeleton">N/A</div>
                    </div>
                    <div v-if="!googleConnected" class="cta-row">
                        <a :href="ga4ConnectUrl" class="btn-secondary">Connect Google</a>
                    </div>
                </DashboardCard>
            </div>

            <section v-if="audit.status === 'completed'" class="details-section">
                <DashboardCard title="Audit KPI Overview">
                    <div class="overview-grid">
                        <div class="overview-main">
                            <div class="kpi-number">{{ display(overviewKpis?.overall_score) }}</div>
                            <div class="muted">Overall Score</div>
                        </div>
                        <div class="overview-main">
                            <div class="kpi-number">{{ display(overviewKpis?.overall_grade) }}</div>
                            <div class="muted">Overall Grade</div>
                        </div>
                        <div class="kpi-list">
                            <div class="kpi-row"><span>Issues Total</span><strong>{{ display(overviewKpis?.issues_total) }}</strong></div>
                            <div class="kpi-row"><span>Warnings</span><strong>{{ display(overviewKpis?.warnings_count) }}</strong></div>
                            <div class="kpi-row"><span>Passed Checks</span><strong>{{ display(overviewKpis?.passed_checks) }}</strong></div>
                            <div class="kpi-row"><span>Pages Crawled</span><strong>{{ display(overviewKpis?.pages_crawled_count) }}</strong></div>
                        </div>
                    </div>
                    <div class="chip-grid mt-3">
                        <span class="chip">On-Page: {{ display(overviewKpis?.category_grades?.on_page_seo_grade) }}</span>
                        <span class="chip">Links: {{ display(overviewKpis?.category_grades?.links_grade) }}</span>
                        <span class="chip">Usability: {{ display(overviewKpis?.category_grades?.usability_grade) }}</span>
                        <span class="chip">Performance: {{ display(overviewKpis?.category_grades?.performance_grade) }}</span>
                        <span class="chip">Social: {{ display(overviewKpis?.category_grades?.social_grade) }}</span>
                        <span class="chip">Technical: {{ display(overviewKpis?.category_grades?.technical_grade) }}</span>
                        <span class="chip">Security: {{ display(overviewKpis?.category_grades?.security_grade) }}</span>
                    </div>
                </DashboardCard>

                <DashboardCard title="On-Page SEO">
                    <div class="kpi-grid">
                        <MiniStat label="Title Missing" :value="display(onPageKpis?.title_missing_count)" />
                        <MiniStat label="Title Duplicate" :value="display(onPageKpis?.title_duplicate_count)" />
                        <MiniStat label="Title Too Long" :value="display(onPageKpis?.title_too_long_count)" />
                        <MiniStat label="Title Too Short" :value="display(onPageKpis?.title_too_short_count)" />
                        <MiniStat label="Meta Missing" :value="display(onPageKpis?.meta_missing_count)" />
                        <MiniStat label="Meta Duplicate" :value="display(onPageKpis?.meta_duplicate_count)" />
                        <MiniStat label="Meta Too Long" :value="display(onPageKpis?.meta_too_long_count)" />
                        <MiniStat label="Meta Too Short" :value="display(onPageKpis?.meta_too_short_count)" />
                        <MiniStat label="H1 Missing" :value="display(onPageKpis?.h1_missing_count)" />
                        <MiniStat label="Multiple H1" :value="display(onPageKpis?.h1_multiple_count)" />
                        <MiniStat label="Images Total" :value="display(onPageKpis?.images_total)" />
                        <MiniStat label="Missing Alt" :value="display(onPageKpis?.images_missing_alt_total)" />
                        <MiniStat label="Avg Word Count" :value="display(onPageKpis?.avg_word_count)" />
                        <MiniStat label="Thin Pages" :value="display(onPageKpis?.thin_pages_count)" />
                    </div>
                    <div class="details-grid mt-4">
                        <div>
                            <p class="section-label">Duplicate Titles</p>
                            <div class="table-wrap">
                                <table class="report-table">
                                    <thead><tr><th>URL</th><th>Title</th></tr></thead>
                                    <tbody>
                                        <tr v-for="row in duplicateTitlesTable" :key="row.url">
                                            <td class="truncate">{{ row.url }}</td>
                                            <td>{{ row.title }}</td>
                                        </tr>
                                        <tr v-if="duplicateTitlesTable.length === 0"><td colspan="2">N/A</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div>
                            <p class="section-label">Missing Meta Description</p>
                            <div class="table-wrap">
                                <table class="report-table">
                                    <thead><tr><th>URL</th></tr></thead>
                                    <tbody>
                                        <tr v-for="row in missingMetaTable" :key="row.url">
                                            <td class="truncate">{{ row.url }}</td>
                                        </tr>
                                        <tr v-if="missingMetaTable.length === 0"><td>N/A</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div>
                            <p class="section-label">Pages Missing H1</p>
                            <div class="table-wrap">
                                <table class="report-table">
                                    <thead><tr><th>URL</th></tr></thead>
                                    <tbody>
                                        <tr v-for="row in missingH1Table" :key="row.url">
                                            <td class="truncate">{{ row.url }}</td>
                                        </tr>
                                        <tr v-if="missingH1Table.length === 0"><td>N/A</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </DashboardCard>

                <DashboardCard title="Technical SEO">
                    <div class="kpi-grid">
                        <MiniStat label="HTTPS Enabled" :value="displayBool(technicalKpis?.https_enabled)" />
                        <MiniStat label="HTTPS Redirect OK" :value="displayBool(technicalKpis?.https_redirect_ok)" />
                        <MiniStat label="robots.txt Found" :value="displayBool(technicalKpis?.robots_txt_present)" />
                        <MiniStat label="Sitemap Found" :value="displayBool(technicalKpis?.xml_sitemap_present)" />
                        <MiniStat label="Canonical Present" :value="display(technicalKpis?.canonical_present_count)" />
                        <MiniStat label="Indexability Issues" :value="display(technicalKpis?.indexability_issues_count)" />
                        <MiniStat label="2xx Pages" :value="display(technicalKpis?.status_code_distribution?.['2xx'])" />
                        <MiniStat label="3xx Pages" :value="display(technicalKpis?.status_code_distribution?.['3xx'])" />
                        <MiniStat label="4xx Pages" :value="display(technicalKpis?.status_code_distribution?.['4xx'])" />
                        <MiniStat label="5xx Pages" :value="display(technicalKpis?.status_code_distribution?.['5xx'])" />
                    </div>
                    <div class="details-grid mt-4">
                        <div>
                            <p class="section-label">Broken Links</p>
                            <div class="table-wrap">
                                <table class="report-table">
                                    <thead><tr><th>Source URL</th><th>Broken URL</th><th>Status</th></tr></thead>
                                    <tbody>
                                        <tr v-for="row in brokenLinksTable" :key="row.from_url + row.to_url">
                                            <td class="truncate">{{ row.from_url }}</td>
                                            <td class="truncate">{{ row.to_url }}</td>
                                            <td>{{ row.status_code }}</td>
                                        </tr>
                                        <tr v-if="brokenLinksTable.length === 0"><td colspan="3">N/A</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div>
                            <p class="section-label">Redirect Chains</p>
                            <div class="table-wrap">
                                <table class="report-table">
                                    <thead><tr><th>URL</th><th>Target</th><th>Chain</th></tr></thead>
                                    <tbody>
                                        <tr v-for="row in redirectChainsTable" :key="row.from_url + row.to_url">
                                            <td class="truncate">{{ row.from_url }}</td>
                                            <td class="truncate">{{ row.to_url }}</td>
                                            <td>{{ row.redirect_hops }}</td>
                                        </tr>
                                        <tr v-if="redirectChainsTable.length === 0"><td colspan="3">N/A</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div>
                            <p class="section-label">Non-200 Pages</p>
                            <div class="table-wrap">
                                <table class="report-table">
                                    <thead><tr><th>URL</th><th>Status</th></tr></thead>
                                    <tbody>
                                        <tr v-for="row in non200PagesTable" :key="row.url">
                                            <td class="truncate">{{ row.url }}</td>
                                            <td>{{ row.status_code }}</td>
                                        </tr>
                                        <tr v-if="non200PagesTable.length === 0"><td colspan="2">N/A</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </DashboardCard>

                <DashboardCard title="Performance Details">
                    <div class="details-grid">
                        <div>
                            <p class="section-label">PSI / Lighthouse Metrics</p>
                            <div v-if="pagespeedLoading" class="skeleton-block"></div>
                            <div v-else class="kpi-list">
                                <div v-for="card in pagespeedLabCards" :key="card.label" class="kpi-row">
                                    <span>{{ card.label }}</span>
                                    <strong>{{ card.value }}</strong>
                                </div>
                            </div>
                        </div>
                        <div>
                            <p class="section-label">Page Weight Breakdown (MB)</p>
                            <div class="kpi-list">
                                <div class="kpi-row"><span>HTML</span><strong>{{ display(performanceKpis?.download_size_breakdown_mb?.html_mb) }}</strong></div>
                                <div class="kpi-row"><span>CSS</span><strong>{{ display(performanceKpis?.download_size_breakdown_mb?.css_mb) }}</strong></div>
                                <div class="kpi-row"><span>JS</span><strong>{{ display(performanceKpis?.download_size_breakdown_mb?.js_mb) }}</strong></div>
                                <div class="kpi-row"><span>Images</span><strong>{{ display(performanceKpis?.download_size_breakdown_mb?.images_mb) }}</strong></div>
                                <div class="kpi-row"><span>Other</span><strong>{{ display(performanceKpis?.download_size_breakdown_mb?.other_mb) }}</strong></div>
                            </div>
                        </div>
                    </div>
                    <div class="details-grid mt-4">
                        <div>
                            <p class="section-label">Top Heavy Assets</p>
                            <div class="table-wrap">
                                <table class="report-table">
                                    <thead><tr><th>Asset</th><th>Type</th><th>Size (KB)</th></tr></thead>
                                    <tbody>
                                        <tr v-for="row in heavyAssetsTable" :key="row.asset_url">
                                            <td class="truncate">{{ row.asset_url }}</td>
                                            <td>{{ row.type }}</td>
                                            <td>{{ row.size_kb }}</td>
                                        </tr>
                                        <tr v-if="heavyAssetsTable.length === 0"><td colspan="3">N/A</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div>
                            <p class="section-label">Top Opportunities</p>
                            <div class="table-wrap">
                                <table class="report-table">
                                    <thead><tr><th>Opportunity</th><th>Estimated Savings (s)</th></tr></thead>
                                    <tbody>
                                        <tr v-for="row in pagespeedOpportunities" :key="row.id">
                                            <td>{{ row.title }}</td>
                                            <td>{{ formatSecondsFromMs(row.savings_ms) }}</td>
                                        </tr>
                                        <tr v-if="pagespeedOpportunities.length === 0"><td colspan="2">N/A</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </DashboardCard>

                <DashboardCard title="Links / Backlinks">
                    <div class="kpi-grid">
                        <MiniStat label="Total Backlinks" :value="display(linksKpis?.total_backlinks_count)" />
                        <MiniStat label="Referring Domains" :value="display(linksKpis?.referring_domains_count)" />
                        <MiniStat label="Dofollow" :value="display(linksKpis?.dofollow_backlinks_count)" />
                        <MiniStat label="Nofollow" :value="display(linksKpis?.nofollow_backlinks_count)" />
                        <MiniStat label="New" :value="display(linksKpis?.new_backlinks_count)" />
                        <MiniStat label="Lost" :value="display(linksKpis?.lost_backlinks_count)" />
                    </div>
                    <div v-if="!linksProviderAvailable" class="cta-row">
                        <span class="muted">Connect provider to view backlink data.</span>
                    </div>
                    <div class="details-grid mt-4">
                        <div>
                            <p class="section-label">Top Referring Domains</p>
                            <div class="table-wrap">
                                <table class="report-table">
                                    <thead><tr><th>Domain</th><th>Backlinks</th></tr></thead>
                                    <tbody>
                                        <tr v-for="row in topRefDomains" :key="row.domain">
                                            <td>{{ row.domain }}</td>
                                            <td>{{ row.count }}</td>
                                        </tr>
                                        <tr v-if="topRefDomains.length === 0"><td colspan="2">N/A</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div>
                            <p class="section-label">Top Anchors</p>
                            <div class="table-wrap">
                                <table class="report-table">
                                    <thead><tr><th>Anchor</th><th>Count</th></tr></thead>
                                    <tbody>
                                        <tr v-for="row in topAnchors" :key="row.anchor">
                                            <td>{{ row.anchor }}</td>
                                            <td>{{ row.count }}</td>
                                        </tr>
                                        <tr v-if="topAnchors.length === 0"><td colspan="2">N/A</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div>
                            <p class="section-label">Top Linked Pages</p>
                            <div class="table-wrap">
                                <table class="report-table">
                                    <thead><tr><th>Page</th><th>Backlinks</th></tr></thead>
                                    <tbody>
                                        <tr v-for="row in topLinkedPages" :key="row.page">
                                            <td class="truncate">{{ row.page }}</td>
                                            <td>{{ row.count }}</td>
                                        </tr>
                                        <tr v-if="topLinkedPages.length === 0"><td colspan="2">N/A</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </DashboardCard>

                <DashboardCard title="Google Search Console">
                    <div class="kpi-grid">
                        <MiniStat label="Clicks" :value="display(gscData?.current?.clicks)" />
                        <MiniStat label="Impressions" :value="display(gscData?.current?.impressions)" />
                        <MiniStat label="CTR" :value="display(gscData?.current?.ctr)" />
                        <MiniStat label="Avg Position" :value="display(gscData?.current?.position)" />
                    </div>
                    <div v-if="!googleConnected" class="cta-row">
                        <a :href="ga4ConnectUrl" class="btn-secondary">Connect Google</a>
                    </div>
                    <div class="details-grid mt-4">
                        <div>
                            <p class="section-label">Top Queries</p>
                            <div class="table-wrap">
                                <table class="report-table">
                                    <thead><tr><th>Query</th><th>Clicks</th><th>Impr.</th><th>CTR</th><th>Pos</th></tr></thead>
                                    <tbody>
                                        <tr v-for="row in gscTopQueries" :key="row.query">
                                            <td>{{ row.query }}</td>
                                            <td>{{ row.clicks }}</td>
                                            <td>{{ row.impressions }}</td>
                                            <td>{{ row.ctr }}</td>
                                            <td>{{ row.position }}</td>
                                        </tr>
                                        <tr v-if="gscTopQueries.length === 0"><td colspan="5">N/A</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div>
                            <p class="section-label">Top Pages</p>
                            <div class="table-wrap">
                                <table class="report-table">
                                    <thead><tr><th>Page</th><th>Clicks</th><th>Impr.</th><th>CTR</th><th>Pos</th></tr></thead>
                                    <tbody>
                                        <tr v-for="row in gscTopPages" :key="row.page_url">
                                            <td class="truncate">{{ row.page_url }}</td>
                                            <td>{{ row.clicks }}</td>
                                            <td>{{ row.impressions }}</td>
                                            <td>{{ row.ctr }}</td>
                                            <td>{{ row.position }}</td>
                                        </tr>
                                        <tr v-if="gscTopPages.length === 0"><td colspan="5">N/A</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </DashboardCard>

                <DashboardCard title="Google Analytics">
                    <div class="kpi-grid">
                        <MiniStat label="Users" :value="display(ga4Summary?.current?.active_users)" />
                        <MiniStat label="Sessions" :value="display(ga4Summary?.current?.sessions)" />
                        <MiniStat label="Engagement" :value="formatPercent(ga4Summary?.current?.engagement_rate)" />
                        <MiniStat label="Conversions" :value="display(ga4Summary?.current?.conversions)" />
                        <MiniStat label="Revenue" :value="display(ga4Summary?.current?.revenue)" />
                    </div>
                    <div v-if="!ga4Connected" class="cta-row">
                        <a :href="ga4ConnectUrl" class="btn-secondary">Connect GA4</a>
                    </div>
                    <div class="details-grid mt-4">
                        <div>
                            <p class="section-label">Top Pages</p>
                            <div class="table-wrap">
                                <table class="report-table">
                                    <thead><tr><th>Path</th><th>Views</th><th>Users</th><th>Conversions</th></tr></thead>
                                    <tbody>
                                        <tr v-for="row in ga4TopPages" :key="row.page_path">
                                            <td class="truncate">{{ row.page_path }}</td>
                                            <td>{{ row.sessions }}</td>
                                            <td>{{ row.active_users }}</td>
                                            <td>{{ row.conversions }}</td>
                                        </tr>
                                        <tr v-if="ga4TopPages.length === 0"><td colspan="4">N/A</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div>
                            <p class="section-label">Traffic Channels</p>
                            <div class="table-wrap">
                                <table class="report-table">
                                    <thead><tr><th>Channel</th><th>Sessions</th></tr></thead>
                                    <tbody>
                                        <tr v-for="row in ga4Channels" :key="row.channel">
                                            <td>{{ row.channel }}</td>
                                            <td>{{ row.sessions }}</td>
                                        </tr>
                                        <tr v-if="ga4Channels.length === 0"><td colspan="2">N/A</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </DashboardCard>

                <DashboardCard title="Social + Local SEO">
                    <div class="kpi-grid">
                        <MiniStat label="Open Graph" :value="displayBool(socialKpis?.open_graph_tags_present)" />
                        <MiniStat label="Twitter Cards" :value="displayBool(socialKpis?.x_cards_present)" />
                        <MiniStat label="Facebook" :value="displayBool(socialKpis?.facebook_page_linked)" />
                        <MiniStat label="Instagram" :value="displayBool(socialKpis?.instagram_linked)" />
                        <MiniStat label="X" :value="displayBool(socialKpis?.x_profile_linked)" />
                        <MiniStat label="LinkedIn" :value="displayBool(socialKpis?.linkedin_linked)" />
                        <MiniStat label="YouTube" :value="displayBool(socialKpis?.youtube_channel_linked)" />
                        <MiniStat label="Address Found" :value="displayBool(localKpis?.address_found)" />
                        <MiniStat label="Phone Found" :value="displayBool(localKpis?.phone_found)" />
                        <MiniStat label="Local Schema" :value="displayBool(localKpis?.local_business_schema_present)" />
                    </div>
                    <div class="table-wrap mt-4">
                        <table class="report-table">
                            <thead><tr><th>Platform</th><th>URL</th></tr></thead>
                            <tbody>
                                <tr v-for="row in socialLinksTable" :key="row.platform">
                                    <td>{{ row.platform }}</td>
                                    <td class="truncate">{{ row.url }}</td>
                                </tr>
                                <tr v-if="socialLinksTable.length === 0"><td colspan="2">N/A</td></tr>
                            </tbody>
                        </table>
                    </div>
                </DashboardCard>

                <DashboardCard title="Technology + Security">
                    <div class="kpi-grid">
                        <MiniStat label="Server" :value="display(techKpis?.web_server)" />
                        <MiniStat label="Server IP" :value="display(techKpis?.server_ip)" />
                        <MiniStat label="Charset" :value="display(techKpis?.charset)" />
                        <MiniStat label="SPF" :value="displayBool(techKpis?.spf_present)" />
                        <MiniStat label="DMARC" :value="displayBool(techKpis?.dmarc_present)" />
                        <MiniStat label="HSTS" :value="display(securityHeaderCoverage?.hsts)" />
                        <MiniStat label="CSP" :value="display(securityHeaderCoverage?.csp)" />
                        <MiniStat label="X-Frame" :value="display(securityHeaderCoverage?.x_frame_options)" />
                    </div>
                    <div class="details-grid mt-4">
                        <div>
                            <p class="section-label">Detected Technologies</p>
                            <div class="table-wrap">
                                <table class="report-table">
                                    <thead><tr><th>Name</th><th>Version</th></tr></thead>
                                    <tbody>
                                        <tr v-for="row in techListTable" :key="row.name">
                                            <td>{{ row.name }}</td>
                                            <td>{{ row.version || 'N/A' }}</td>
                                        </tr>
                                        <tr v-if="techListTable.length === 0"><td colspan="2">N/A</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div>
                            <p class="section-label">Security Headers</p>
                            <div class="table-wrap">
                                <table class="report-table">
                                    <thead><tr><th>Header</th><th>Pages With Header</th></tr></thead>
                                    <tbody>
                                        <tr v-for="row in securityHeadersTable" :key="row.header">
                                            <td>{{ row.header }}</td>
                                            <td>{{ row.pages_with_header }}/{{ row.total_pages }}</td>
                                        </tr>
                                        <tr v-if="securityHeadersTable.length === 0"><td colspan="2">N/A</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </DashboardCard>

                <DashboardCard title="Issues">
                    <div class="filter-row">
                        <label class="section-label">Severity</label>
                        <select v-model="issueSeverity" class="control-input">
                            <option v-for="option in issueSeverityOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                    <div class="issue-groups">
                        <div v-for="group in groupedIssues" :key="group.category" class="issue-group">
                            <p class="section-label">{{ group.category }}</p>
                            <div class="table-wrap">
                                <table class="report-table">
                                    <thead><tr><th>Severity</th><th>Issue</th><th>Affected Pages</th><th>Details</th></tr></thead>
                                    <tbody>
                                        <tr v-for="issue in group.items" :key="issue.id">
                                            <td><span :class="impactBadgeClass(issue.impact)" class="badge">{{ issue.impact }}</span></td>
                                            <td>{{ issue.title }}</td>
                                            <td>{{ issueAffectedCount(issue) }}</td>
                                            <td>
                                                <details>
                                                    <summary class="link">View details</summary>
                                                    <div class="issue-details">
                                                        <p class="muted">{{ issue.description || issue.recommendation || 'N/A' }}</p>
                                                        <ul class="issue-list">
                                                            <li v-for="url in issueSampleUrls(issue)" :key="url">{{ url }}</li>
                                                        </ul>
                                                    </div>
                                                </details>
                                            </td>
                                        </tr>
                                        <tr v-if="group.items.length === 0"><td colspan="4">N/A</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </DashboardCard>

                <DashboardCard title="Pages / Crawl">
                    <div class="table-wrap">
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th>URL</th>
                                    <th>Status</th>
                                    <th>Title Length</th>
                                    <th>Meta Desc Length</th>
                                    <th>Word Count</th>
                                    <th>Images Missing Alt</th>
                                    <th>Indexability</th>
                                    <th>Canonical URL</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in pagesTableRows" :key="row.url">
                                    <td class="truncate">{{ row.url }}</td>
                                    <td>{{ row.status_code }}</td>
                                    <td>{{ row.title_len }}</td>
                                    <td>{{ row.meta_len }}</td>
                                    <td>{{ row.word_count }}</td>
                                    <td>{{ row.images_missing_alt }}</td>
                                    <td>{{ row.indexability }}</td>
                                    <td class="truncate">{{ row.canonical_url }}</td>
                                </tr>
                                <tr v-if="pagesTableRows.length === 0"><td colspan="8">No pages found.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </DashboardCard>
            </section>

        </main>
    </div>
</template>



<script setup>
import { ref, computed, onMounted, onUnmounted, defineComponent, h } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import VueApexCharts from 'vue3-apexcharts';
import axios from 'axios';

const props = defineProps({
    audit: Object,
    page: Object,
    issues: Array,
    pages: Array,
    links: Array,
    assets: Array,
    isOwner: Boolean,
    shareUrl: String,
    google: Object,
    ga4Integration: Object,
});

// Component tree: Audit/Show.vue -> DashboardCard, MiniStat, ApexDonutChart, ApexRadialGauge, ApexStackedBar, ApexSparkline

const pollingInterval = ref(null);
const pagespeedInterval = ref(null);
const cruxInterval = ref(null);
const pagespeedLocal = ref(props.google?.pagespeed || null);
const cruxLocal = ref(props.google?.crux || null);
const pagespeedMode = ref('mobile');
const pagespeedModes = ['mobile', 'desktop'];
const cruxMode = ref('mobile');
const cruxModes = ['mobile', 'desktop'];
const issueSeverity = ref('all');
const issueSeverityOptions = [
    { value: 'all', label: 'All severities' },
    { value: 'high', label: 'High' },
    { value: 'medium', label: 'Medium' },
    { value: 'low', label: 'Low' },
];

const statusLabel = computed(() => {
    const labels = { queued: 'Queued', running: 'Running', completed: 'Completed', failed: 'Failed' };
    return labels[props.audit.status] || props.audit.status;
});

const rerunUrl = computed(() => {
    if (!props.audit?.url) return '/audit';
    return `/audit?url=${encodeURIComponent(props.audit.url)}`;
});

const summary = computed(() => props.audit.summary || {});
const googleData = computed(() => props.google || {});
const googleConnected = computed(() => googleData.value.connected);
const kpis = computed(() => props.audit?.audit_kpis || {});
const overviewKpis = computed(() => kpis.value?.overview || {});
const onPageKpis = computed(() => kpis.value?.on_page_seo || {});
const technicalKpis = computed(() => kpis.value?.technical || {});
const performanceKpis = computed(() => kpis.value?.performance || {});
const linksKpis = computed(() => kpis.value?.links || {});
const socialKpis = computed(() => kpis.value?.social || {});
const localKpis = computed(() => kpis.value?.local_seo || {});
const techKpis = computed(() => kpis.value?.tech_email || {});
const pagespeedFromAudit = computed(() => kpis.value?.google?.pagespeed || null);
const cruxFromAudit = computed(() => kpis.value?.google?.crux || null);
const pagespeedConfigured = computed(() => {
    if (googleData.value.pagespeed_configured) return true;
    return !!pagespeedFromAudit.value;
});
const cruxConfigured = computed(() => {
    if (googleData.value.crux_configured) return true;
    return !!cruxFromAudit.value;
});
const kpisOverview = computed(() => kpis.value?.overview || null);
const kpisOnPage = computed(() => kpis.value?.on_page_seo || null);
const kpisLinks = computed(() => kpis.value?.links || null);
const kpisPerformance = computed(() => kpis.value?.performance || null);
const kpisUsability = computed(() => kpis.value?.usability || null);
const kpisSocial = computed(() => kpis.value?.social || null);
const kpisLocal = computed(() => kpis.value?.local_seo || null);
const kpisTechEmail = computed(() => kpis.value?.tech_email || null);

const pagespeedData = computed(() => pagespeedLocal.value || googleData.value.pagespeed || pagespeedFromAudit.value);
const cruxData = computed(() => cruxLocal.value || googleData.value.crux || cruxFromAudit.value);
const gscData = computed(() => googleData.value.gsc);

const pagespeedKpis = computed(() => {
    if (!pagespeedData.value) return null;
    const preferred = pagespeedData.value[pagespeedMode.value]?.kpis;
    if (preferred) return preferred;
    return pagespeedData.value.mobile?.kpis || pagespeedData.value.desktop?.kpis || null;
});
const cruxEntry = computed(() => {
    if (!cruxData.value) return null;
    return cruxMode.value === 'mobile' ? cruxData.value.mobile : cruxData.value.desktop;
});
const cruxKpis = computed(() => cruxEntry.value?.kpis || null);
const cruxLevel = computed(() => cruxEntry.value?.target_type || cruxData.value?.level_used || null);

const pagespeedStatus = computed(() => {
    if (!pagespeedData.value) return null;
    if (pagespeedData.value.status) return pagespeedData.value.status;
    const mobileStatus = pagespeedData.value.mobile?.status;
    const desktopStatus = pagespeedData.value.desktop?.status;
    return mobileStatus || desktopStatus || null;
});
const cruxStatus = computed(() => {
    if (!cruxData.value) return null;
    if (cruxData.value.status) return cruxData.value.status;
    const mobileStatus = cruxData.value.mobile?.status;
    const desktopStatus = cruxData.value.desktop?.status;
    return mobileStatus || desktopStatus || null;
});

const pagespeedLoading = ref(false);
const cruxLoading = ref(false);

const pagespeedError = computed(() => {
    if (!pagespeedData.value) return null;
    if (pagespeedData.value.error) return pagespeedData.value.error;
    return pagespeedData.value.mobile?.error || pagespeedData.value.desktop?.error || null;
});
const cruxError = computed(() => {
    if (!cruxData.value) return null;
    if (cruxData.value.error) return cruxData.value.error;
    return cruxData.value.mobile?.error || cruxData.value.desktop?.error || null;
});

const pagespeedStatusLabel = computed(() => {
    if (!pagespeedConfigured.value) return 'Not configured';
    if (pagespeedStatus.value === 'limit_exceeded') return 'Limit exceeded';
    if (pagespeedStatus.value === 'failed') return 'Failed';
    if (pagespeedData.value) return 'Ready';
    return 'Pending';
});
const cruxStatusLabel = computed(() => {
    if (!cruxConfigured.value) return 'Not configured';
    if (cruxStatus.value === 'failed') return 'Failed';
    if (cruxStatus.value === 'no_data') return 'No data';
    if (cruxData.value) return 'Ready';
    return 'Pending';
});

const pagespeedStatusMessage = computed(() => {
    if (!pagespeedConfigured.value) {
        return 'Set GOOGLE_PAGESPEED_API_KEY to enable PSI.';
    }
    if (pagespeedStatus.value === 'limit_exceeded') {
        return 'Daily PageSpeed limit exceeded.';
    }
    if (pagespeedStatus.value === 'failed') {
        return pagespeedError.value || 'PageSpeed failed.';
    }
    if (!pagespeedData.value) {
        return 'Waiting for PageSpeed job to complete.';
    }
    return 'PageSpeed data is available.';
});
const cruxStatusMessage = computed(() => {
    if (!cruxConfigured.value) {
        return 'Set GOOGLE_CRUX_API_KEY to enable CrUX.';
    }
    if (cruxStatus.value === 'failed') {
        return cruxError.value || 'CrUX failed.';
    }
    if (cruxStatus.value === 'no_data') {
        return 'Not enough real-user data yet.';
    }
    if (!cruxData.value) {
        return 'Waiting for CrUX job to complete.';
    }
    return 'CrUX data is available.';
});

const pagespeedScoreCards = computed(() => {
    const categories = pagespeedKpis.value?.categories || {};
    return [
        { label: 'Performance', value: display(categories.performance_score) },
        { label: 'SEO', value: display(categories.seo_score) },
        { label: 'Accessibility', value: display(categories.accessibility_score) },
        { label: 'Best Practices', value: display(categories.best_practices_score) },
    ];
});

const pagespeedLabCards = computed(() => {
    const metrics = pagespeedKpis.value?.lab_metrics || {};
    return [
        { label: 'LCP (s)', value: formatSecondsFromMs(metrics.lcp_ms) },
        { label: 'CLS', value: display(metrics.cls) },
        { label: 'TBT (s)', value: formatSecondsFromMs(metrics.tbt_ms) },
        { label: 'FCP (s)', value: formatSecondsFromMs(metrics.fcp_ms) },
        { label: 'Speed Index (s)', value: formatSecondsFromMs(metrics.speed_index_ms) },
        { label: 'TTI (s)', value: formatSecondsFromMs(metrics.tti_ms) },
    ];
});

const pagespeedOpportunities = computed(() => pagespeedKpis.value?.opportunities || []);

const cruxCards = computed(() => {
    const kpis = cruxKpis.value || {};
    return [
        { label: 'LCP p75 (s)', value: formatSecondsFromMs(kpis.lcp_p75_ms), status: kpis.lcp_status },
        { label: 'INP p75 (s)', value: formatSecondsFromMs(kpis.inp_p75_ms), status: kpis.inp_status },
        { label: 'CLS p75', value: formatCls(kpis.cls_p75), status: kpis.cls_status },
        { label: 'TTFB p75 (s)', value: formatSecondsFromMs(kpis.ttfb_p75_ms) },
        { label: 'RTT p75 (s)', value: formatSecondsFromMs(kpis.rtt_p75_ms) },
    ];
});

const cruxDistributions = computed(() => {
    const kpis = cruxKpis.value || {};
    return [
        { label: 'LCP', data: kpis.lcp_distribution, status: kpis.lcp_status },
        { label: 'INP', data: kpis.inp_distribution, status: kpis.inp_status },
        { label: 'CLS', data: kpis.cls_distribution, status: kpis.cls_status },
    ];
});

const ga4Integration = computed(() => props.ga4Integration || {});
const ga4Connected = computed(() => ga4Integration.value.connected);
const ga4CanConnect = computed(() => ga4Integration.value.can_connect);
const ga4ConnectUrl = computed(() => `/auth/google/redirect?return_url=/audit/${props.audit.id}`);

const ga4Summary = ref(null);
const ga4Properties = ref([]);
const ga4SelectedPropertyId = ref(ga4Integration.value.property_id || '');
const ga4ManualPropertyId = ref(ga4Integration.value.property_id || '');
const ga4Loading = ref(false);
const ga4Error = ref(null);

const ga4Cards = computed(() => {
    const current = ga4Summary.value?.current || {};
    return [
        { label: 'Active Users', value: display(current.active_users) },
        { label: 'Sessions', value: display(current.sessions) },
        { label: 'Engagement Rate', value: formatPercent(current.engagement_rate) },
        { label: 'Avg Session (s)', value: display(roundValue(current.avg_session_duration)) },
        { label: 'Conversions', value: display(current.conversions) },
    ];
});

const ga4TopPages = computed(() => ga4Summary.value?.top_pages || []);
const ga4TopSources = computed(() => ga4Summary.value?.top_sources || []);

const ga4StatusLabel = computed(() => {
    if (!ga4CanConnect.value) return 'Login required';
    if (!ga4Connected.value) return 'Not connected';
    if (ga4Error.value) return 'Error';
    if (!ga4SelectedPropertyId.value && !ga4ManualPropertyId.value) return 'Property not selected';
    if (!ga4Summary.value) return 'Pending';
    return 'Ready';
});

const ga4StatusMessage = computed(() => {
    if (!ga4CanConnect.value) return 'Log in to connect GA4.';
    if (!ga4Connected.value) return 'Connect Google to load GA4 KPIs.';
    if (ga4Error.value) return ga4Error.value;
    if (!ga4SelectedPropertyId.value && !ga4ManualPropertyId.value) return 'Select a GA4 property.';
    if (!ga4Summary.value) return 'Waiting for GA4 data.';
    return 'GA4 data is available.';
});

const gscCards = computed(() => {
    const current = gscData.value?.current || {};
    return [
        { label: 'Clicks', value: display(current.clicks) },
        { label: 'Impressions', value: display(current.impressions) },
        { label: 'CTR', value: display(current.ctr) },
        { label: 'Position', value: display(current.position) },
    ];
});

const gscTopQueries = computed(() => gscData.value?.top_queries || []);
const gscTopPages = computed(() => gscData.value?.top_pages || []);
const ga4Channels = computed(() => {
    const rows = ga4Summary.value?.channels || ga4Summary.value?.traffic_channels || [];
    return rows.map((row) => ({
        channel: row.channel || row.label || row.name || 'N/A',
        sessions: row.sessions || row.value || 0,
    }));
});

const rankingScore = computed(() => kpisOverview.value?.overall_score ?? props.audit?.overall_score ?? null);
const rankingDelta = computed(() => kpisOverview.value?.rank_change ?? null);
const rankingCategories = ['1-3', '4-10', '11-20', '21-50', '51+'];
const rankingSeries = computed(() => {
    const buckets = kpisOverview.value?.rankings_buckets || kpisOverview.value?.rankings || null;
    if (!buckets) return [];
    const values = rankingCategories.map((key) => Number(buckets[key] ?? 0));
    return [{ name: 'Rankings', data: values }];
});

const ga4Donut = computed(() => {
    const channels = ga4Summary.value?.channels || ga4Summary.value?.traffic_channels || [];
    if (!channels.length) return { series: [], labels: [], colors: [] };
    const labels = channels.map((row) => row.channel || row.label || row.name || 'N/A');
    const series = channels.map((row) => Number(row.sessions || row.value || 0));
    const colors = ['#2563eb', '#22c55e', '#f97316', '#eab308', '#a855f7', '#0ea5e9', '#64748b'];
    return { series, labels, colors: colors.slice(0, labels.length) };
});

const citationFlow = computed(() => {
    const value = kpisLinks.value?.citation_flow ?? kpisLinks.value?.authority_score ?? null;
    return value === null || value === undefined ? null : Number(value);
});

const backlinkCategories = computed(() => {
    const trend = kpisLinks.value?.new_lost_trend || [];
    if (!trend.length) return [];
    return trend.map((row) => row.date || row.label || row.period || '');
});
const backlinkSeries = computed(() => {
    const trend = kpisLinks.value?.new_lost_trend || [];
    if (!trend.length) return [];
    return [
        { name: 'New', data: trend.map((row) => Number(row.new_links || row.new || 0)) },
        { name: 'Lost', data: trend.map((row) => Number(row.lost_links || row.lost || 0)) },
    ];
});

const gscTrend = computed(() => {
    const trend = gscData.value?.impressions_trend || gscData.value?.trend || [];
    if (!trend.length) return [];
    return trend.map((row) => Number(row.value || row.impressions || 0));
});

const categoryCards = computed(() => {
    const scores = props.audit.category_scores || {};
    return [
        { label: 'Onpage', value: display(scores.onpage) },
        { label: 'Technical', value: display(scores.technical) },
        { label: 'Performance', value: display(scores.performance) },
        { label: 'Links', value: display(scores.links) },
        { label: 'Social', value: display(scores.social) },
        { label: 'Usability', value: display(scores.usability) },
        { label: 'Local', value: display(scores.local) },
        { label: 'Security', value: display(scores.security) },
    ];
});


const overviewGradeCards = computed(() => {
    const grades = kpisOverview.value?.category_grades || {};
    return [
        { label: 'On-Page', value: display(grades.on_page_seo_grade) },
        { label: 'Links', value: display(grades.links_grade) },
        { label: 'Usability', value: display(grades.usability_grade) },
        { label: 'Performance', value: display(grades.performance_grade) },
        { label: 'Social', value: display(grades.social_grade) },
    ];
});

const onPageCards = computed(() => {
    const data = kpisOnPage.value || {};
    return [
        { label: 'Title Length', value: display(data.title_length) },
        { label: 'Meta Desc Length', value: display(data.meta_description_length) },
        { label: 'H1 Present', value: displayBool(data.h1_present) },
        { label: 'Word Count', value: display(data.content_word_count) },
        { label: 'Images Missing Alt', value: display(data.images_missing_alt_count) },
        { label: 'Canonical Tag', value: displayBool(data.canonical_tag_present) },
        { label: 'Noindex Meta', value: displayBool(data.noindex_meta_present) },
        { label: 'SSL Enabled', value: displayBool(data.ssl_enabled) },
        { label: 'Robots.txt', value: displayBool(data.robots_txt_present) },
        { label: 'Sitemap', value: displayBool(data.xml_sitemap_present) },
        { label: 'Analytics', value: displayBool(data.analytics_detected) },
        { label: 'Schema', value: displayBool(data.schema_detected) },
    ];
});

const onPageTopKeywords = computed(() => kpisOnPage.value?.top_keywords || []);
const onPagePhrases = computed(() => kpisOnPage.value?.phrases || []);

const linksCards = computed(() => {
    const data = kpisLinks.value || {};
    const linkStruct = data.on_page_link_structure || {};
    return [
        { label: 'Total Links', value: display(linkStruct.total_links) },
        { label: 'Internal Links', value: display(linkStruct.internal_links) },
        { label: 'External Follow', value: display(linkStruct.external_links_follow) },
        { label: 'External Nofollow', value: display(linkStruct.external_links_nofollow) },
        { label: 'Backlinks', value: display(data.total_backlinks_count) },
        { label: 'Ref Domains', value: display(data.referring_domains_count) },
        { label: 'Edu Backlinks', value: display(data.edu_backlinks_count) },
        { label: 'Gov Backlinks', value: display(data.gov_backlinks_count) },
    ];
});

const linksTopBacklinks = computed(() => kpisLinks.value?.top_backlinks_table || []);
const linksTopAnchors = computed(() => kpisLinks.value?.top_anchors_by_backlinks || []);

const performanceCards = computed(() => {
    const data = kpisPerformance.value || {};
    const timeline = data.website_load_timeline || {};
    return [
        { label: 'Server Response (s)', value: display(timeline.server_response_sec) },
        { label: 'Content Loaded (s)', value: display(timeline.all_page_content_loaded_sec) },
        { label: 'Scripts Complete (s)', value: display(timeline.all_page_scripts_complete_sec) },
        { label: 'Total Download (MB)', value: display(data.total_download_size_mb) },
        { label: 'AMP Enabled', value: displayBool(data.amp_enabled) },
        { label: 'HTTP/2 Enabled', value: displayBool(data.http2_enabled) },
        { label: 'Minification OK', value: displayBool(data.minification_ok) },
        { label: 'JS Errors', value: displayBool(data.js_errors_detected) },
    ];
});

const performanceBreakdownRows = computed(() => {
    const breakdown = kpisPerformance.value?.download_size_breakdown_mb || {};
    return [
        { label: 'HTML', value: display(breakdown.html_mb) },
        { label: 'CSS', value: display(breakdown.css_mb) },
        { label: 'JS', value: display(breakdown.js_mb) },
        { label: 'Images', value: display(breakdown.images_mb) },
        { label: 'Other', value: display(breakdown.other_mb) },
    ];
});

const performanceResourceRows = computed(() => {
    const resources = kpisPerformance.value?.resources_breakdown || {};
    return [
        { label: 'Total Objects', value: display(resources.total_objects) },
        { label: 'HTML Pages', value: display(resources.html_pages_count) },
        { label: 'JS Resources', value: display(resources.js_resources_count) },
        { label: 'CSS Resources', value: display(resources.css_resources_count) },
        { label: 'Images', value: display(resources.images_count) },
        { label: 'Other', value: display(resources.other_resources_count) },
    ];
});

const usabilityCards = computed(() => {
    const data = kpisUsability.value || {};
    return [
        { label: 'Viewport', value: displayBool(data.viewport_configured) },
        { label: 'Favicon', value: displayBool(data.favicon_present) },
        { label: 'Tap Targets', value: displayBool(data.tap_target_ok) },
        { label: 'Font Legible', value: displayBool(data.font_legible) },
        { label: 'Iframes Used', value: displayBool(data.iframes_used) },
        { label: 'Flash Used', value: displayBool(data.flash_used) },
        { label: 'Mobile Score', value: display(data.pagespeed_mobile_score) },
        { label: 'Desktop Score', value: display(data.pagespeed_desktop_score) },
    ];
});

const usabilityMobileLabRows = computed(() => {
    const metrics = kpisUsability.value?.mobile_lab_metrics || {};
    return [
        { label: 'FCP (s)', value: display(metrics.fcp_sec) },
        { label: 'Speed Index (s)', value: display(metrics.speed_index_sec) },
        { label: 'LCP (s)', value: display(metrics.lcp_sec) },
        { label: 'TTI (s)', value: display(metrics.tti_sec) },
        { label: 'TBT (s)', value: display(metrics.tbt_sec) },
        { label: 'CLS', value: display(metrics.cls) },
    ];
});

const usabilityDesktopLabRows = computed(() => {
    const metrics = kpisUsability.value?.desktop_lab_metrics || {};
    return [
        { label: 'FCP (s)', value: display(metrics.fcp_sec) },
        { label: 'Speed Index (s)', value: display(metrics.speed_index_sec) },
        { label: 'LCP (s)', value: display(metrics.lcp_sec) },
        { label: 'TTI (s)', value: display(metrics.tti_sec) },
        { label: 'TBT (s)', value: display(metrics.tbt_sec) },
        { label: 'CLS', value: display(metrics.cls) },
    ];
});

const socialCards = computed(() => {
    const data = kpisSocial.value || {};
    return [
        { label: 'Facebook Linked', value: displayBool(data.facebook_page_linked) },
        { label: 'OpenGraph Tags', value: displayBool(data.open_graph_tags_present) },
        { label: 'Facebook Pixel', value: displayBool(data.facebook_pixel_present) },
        { label: 'X Profile', value: displayBool(data.x_profile_linked) },
        { label: 'X Cards', value: displayBool(data.x_cards_present) },
        { label: 'Instagram', value: displayBool(data.instagram_linked) },
        { label: 'LinkedIn', value: displayBool(data.linkedin_linked) },
        { label: 'YouTube', value: displayBool(data.youtube_channel_linked) },
    ];
});

const localCards = computed(() => {
    const data = kpisLocal.value || {};
    return [
        { label: 'Address Found', value: displayBool(data.address_found) },
        { label: 'Phone Found', value: displayBool(data.phone_found) },
        { label: 'Local Schema', value: displayBool(data.local_business_schema_present) },
        { label: 'Google Business', value: displayBool(data.google_business_profile_identified) },
    ];
});

const techCards = computed(() => {
    const data = kpisTechEmail.value || {};
    return [
        { label: 'Web Server', value: display(data.web_server) },
        { label: 'Server IP', value: display(data.server_ip) },
        { label: 'Charset', value: display(data.charset) },
        { label: 'DMARC', value: displayBool(data.dmarc_present) },
        { label: 'SPF', value: displayBool(data.spf_present) },
    ];
});

const techList = computed(() => kpisTechEmail.value?.detected_technologies || []);

const crawlStatsCards = computed(() => {
    const stats = props.audit.crawl_stats || {};
    return [
        { label: 'Pages Scanned', value: display(stats.pages_scanned ?? props.audit.pages_scanned) },
        { label: 'Broken Links', value: display(stats.broken_links_count) },
        { label: 'Redirect Chains', value: display(stats.redirect_chain_count) },
        { label: 'Duplicate Titles', value: display(stats.duplicate_titles_groups) },
        { label: 'Duplicate Meta', value: display(stats.duplicate_meta_groups) },
    ];
});

const exportUrl = computed(() => {
    const baseUrl = `/audit/${props.audit.id}/export/pdf`;
    if (props.shareUrl) {
        const token = new URL(props.shareUrl).searchParams.get('token');
        return token ? `${baseUrl}?token=${token}` : baseUrl;
    }
    return baseUrl;
});

const impactBadgeClass = (impact) => {
    if (impact === 'high') return 'bg-red-100 text-red-800';
    if (impact === 'medium') return 'bg-yellow-100 text-yellow-800';
    return 'bg-blue-100 text-blue-800';
};

const display = (value) => {
    if (value === null || value === undefined || value === '') return 'N/A';
    return value;
};

const displayBool = (value) => {
    if (value === null || value === undefined) return 'N/A';
    return value ? 'Yes' : 'No';
};

const formatPercent = (value) => {
    if (value === null || value === undefined || value === '') return 'N/A';
    const numeric = Number(value);
    if (Number.isNaN(numeric)) return 'N/A';
    return `${(numeric * 100).toFixed(2)}%`;
};

const formatMs = (value) => {
    if (value === null || value === undefined || value === '') return 'N/A';
    const numeric = Number(value);
    if (Number.isNaN(numeric)) return 'N/A';
    return Math.round(numeric);
};

const formatSecondsFromMs = (value) => {
    if (value === null || value === undefined || value === '') return 'N/A';
    const numeric = Number(value);
    if (Number.isNaN(numeric)) return 'N/A';
    return (numeric / 1000).toFixed(2);
};

const formatCls = (value) => {
    if (value === null || value === undefined || value === '') return 'N/A';
    const numeric = Number(value);
    if (Number.isNaN(numeric)) return 'N/A';
    return numeric.toFixed(3);
};

const formatDensity = (value) => {
    if (value === null || value === undefined || value === '') return 'N/A';
    const numeric = Number(value);
    if (Number.isNaN(numeric)) return 'N/A';
    return `${(numeric * 100).toFixed(1)}%`;
};

const roundValue = (value) => {
    if (value === null || value === undefined || value === '') return null;
    const numeric = Number(value);
    if (Number.isNaN(numeric)) return null;
    return Math.round(numeric);
};

const statusBadgeClass = (status) => {
    if (status === 'good') return 'bg-emerald-100 text-emerald-800';
    if (status === 'ni') return 'bg-amber-100 text-amber-800';
    if (status === 'poor') return 'bg-rose-100 text-rose-800';
    return 'bg-slate-100 text-slate-600';
};

const issueAffectedCount = (issue) => {
    if (!issue) return 'N/A';
    return issue.affected_pages ?? issue.affected_pages_count ?? issue.pages ?? issue.count ?? 'N/A';
};

const issueActionUrl = (issue) => {
    if (!issue) return null;
    return issue.url || issue.help_url || issue.reference_url || null;
};

const filteredIssues = computed(() => {
    const list = props.issues || [];
    if (issueSeverity.value === 'all') return list;
    return list.filter((issue) => issue.impact === issueSeverity.value);
});

const pagesTableRows = computed(() => {
    const rows = props.pages || [];
    return rows.map((row) => ({
        url: row.url || row.page_url || row.path || row.page || 'N/A',
        status_code: row.status_code ?? row.http_status ?? row.status ?? 'N/A',
        title_len: row.title_len ?? row.title_length ?? row.title_chars ?? 'N/A',
        meta_len: row.meta_len ?? row.meta_description_length ?? row.meta_description_chars ?? 'N/A',
        word_count: row.word_count ?? row.words ?? 'N/A',
        images_missing_alt: row.images_missing_alt ?? row.images_missing_alt_count ?? row.images_missing ?? 'N/A',
        indexability: (row.robots_meta && String(row.robots_meta).toLowerCase().includes('noindex'))
            || (row.x_robots_tag && String(row.x_robots_tag).toLowerCase().includes('noindex'))
            ? 'Noindex'
            : 'Indexable',
        canonical_url: row.canonical_url || 'N/A',
    }));
});

const duplicateTitlesTable = computed(() => onPageKpis.value?.duplicate_titles_table || []);
const missingMetaTable = computed(() => onPageKpis.value?.missing_meta_table || []);
const missingH1Table = computed(() => onPageKpis.value?.missing_h1_table || []);

const brokenLinksTable = computed(() => technicalKpis.value?.broken_links_examples || []);
const redirectChainsTable = computed(() => technicalKpis.value?.redirect_chains_examples || []);
const non200PagesTable = computed(() => technicalKpis.value?.non_200_pages || []);

const heavyAssetsTable = computed(() => performanceKpis.value?.heavy_assets || []);

const linksProviderAvailable = computed(() => {
    return linksKpis.value?.total_backlinks_count !== null && linksKpis.value?.total_backlinks_count !== undefined;
});

const topRefDomains = computed(() => linksKpis.value?.top_referring_tlds || linksKpis.value?.top_referring_domains || []);
const topAnchors = computed(() => linksKpis.value?.top_anchors_by_backlinks || []);
const topLinkedPages = computed(() => linksKpis.value?.top_pages_by_backlinks || []);

const socialLinksTable = computed(() => {
    const rows = [
        { platform: 'Facebook', url: socialKpis.value?.facebook_url },
        { platform: 'Instagram', url: socialKpis.value?.instagram_url },
        { platform: 'X', url: socialKpis.value?.x_url },
        { platform: 'LinkedIn', url: socialKpis.value?.linkedin_url },
        { platform: 'YouTube', url: socialKpis.value?.youtube_url },
    ];
    return rows.filter((row) => row.url);
});

const techListTable = computed(() => techKpis.value?.detected_technologies || []);
const securityHeadersTable = computed(() => technicalKpis.value?.security_headers_list || []);
const securityHeaderCoverage = computed(() => technicalKpis.value?.security_headers || {});

const groupedIssues = computed(() => {
    const items = filteredIssues.value || [];
    const grouped = items.reduce((acc, issue) => {
        const category = issue.category || 'General';
        acc[category] = acc[category] || [];
        acc[category].push(issue);
        return acc;
    }, {});
    return Object.entries(grouped).map(([category, items]) => ({ category, items }));
});

const issueSampleUrls = (issue) => {
    const urls = issue?.sample_urls || [];
    return urls.slice(0, 10);
};

const DashboardCard = defineComponent({
    name: 'DashboardCard',
    props: {
        title: { type: String, default: '' },
        as: { type: String, default: 'div' },
    },
    setup(props, { slots }) {
        return () =>
            h(props.as, { class: 'dashboard-card' }, [
                props.title ? h('div', { class: 'card-header' }, props.title) : null,
                slots.default ? slots.default() : [],
            ]);
    },
});

const MiniStat = defineComponent({
    name: 'MiniStat',
    props: {
        label: { type: String, required: true },
        value: { type: [String, Number], default: 'N/A' },
        trend: { type: [String, Number, null], default: null },
    },
    setup(props) {
        return () =>
            h('div', { class: 'mini-card' }, [
                h('div', { class: 'mini-label' }, props.label),
                h('div', { class: 'mini-value' }, props.value ?? 'N/A'),
                props.trend !== null && props.trend !== undefined
                    ? h('div', { class: ['mini-trend', Number(props.trend) >= 0 ? 'up' : 'down'] }, props.trend)
                    : null,
            ]);
    },
});

const ApexDonutChart = defineComponent({
    name: 'ApexDonutChart',
    props: {
        series: { type: Array, default: () => [] },
        labels: { type: Array, default: () => [] },
        height: { type: [String, Number], default: 220 },
    },
    setup(props) {
        const options = computed(() => ({
            chart: { type: 'donut', sparkline: { enabled: true } },
            labels: props.labels,
            colors: ga4Donut.value.colors,
            legend: { show: false },
            dataLabels: { enabled: false },
            stroke: { width: 2, colors: ['#ffffff'] },
            plotOptions: { pie: { donut: { size: '70%' } } },
        }));
        return () => h(VueApexCharts, { type: 'donut', height: props.height, options: options.value, series: props.series });
    },
});

const ApexRadialGauge = defineComponent({
    name: 'ApexRadialGauge',
    props: {
        value: { type: [Number, String, null], default: null },
        label: { type: String, default: '' },
        size: { type: [Number, String], default: 150 },
    },
    setup(props) {
        const series = computed(() => (props.value === null || props.value === undefined ? [0] : [Number(props.value)]));
        const options = computed(() => ({
            chart: { type: 'radialBar', sparkline: { enabled: true } },
            plotOptions: {
                radialBar: {
                    hollow: { size: '65%' },
                    track: { background: '#e2e8f0' },
                    dataLabels: {
                        name: { show: true, fontSize: '12px', offsetY: 26 },
                        value: { fontSize: '22px', fontWeight: 600, offsetY: -10 },
                    },
                },
            },
            labels: [props.label],
            colors: ['#2563eb'],
        }));
        return () => h(VueApexCharts, { type: 'radialBar', height: props.size, options: options.value, series: series.value });
    },
});

const ApexStackedBar = defineComponent({
    name: 'ApexStackedBar',
    props: {
        series: { type: Array, default: () => [] },
        categories: { type: Array, default: () => [] },
        height: { type: [Number, String], default: 180 },
    },
    setup(props) {
        const options = computed(() => ({
            chart: { type: 'bar', stacked: true, toolbar: { show: false } },
            plotOptions: { bar: { borderRadius: 6, columnWidth: '55%' } },
            dataLabels: { enabled: false },
            xaxis: { categories: props.categories, labels: { style: { fontSize: '11px' } } },
            yaxis: { labels: { style: { fontSize: '11px' } } },
            colors: ['#22c55e', '#ef4444', '#2563eb'],
            grid: { strokeDashArray: 4 },
            legend: { show: false },
        }));
        return () => h(VueApexCharts, { type: 'bar', height: props.height, options: options.value, series: props.series });
    },
});

const ApexSparkline = defineComponent({
    name: 'ApexSparkline',
    props: {
        series: { type: Array, default: () => [] },
        height: { type: [Number, String], default: 120 },
    },
    setup(props) {
        const options = computed(() => ({
            chart: { type: 'line', sparkline: { enabled: true } },
            stroke: { curve: 'smooth', width: 3 },
            colors: ['#2563eb'],
            tooltip: { enabled: false },
        }));
        return () =>
            h(VueApexCharts, {
                type: 'line',
                height: props.height,
                options: options.value,
                series: [{ name: 'trend', data: props.series }],
            });
    },
});

const shareAudit = async () => {
    try {
        const response = await axios.post(`/audit/${props.audit.id}/share`);
        if (response.data.share_url) {
            await navigator.clipboard.writeText(response.data.share_url);
        }
    } catch (error) {
        console.error('Failed to share audit:', error);
    }
};

const pollStatus = async () => {
    if (props.audit.status === 'completed' || props.audit.status === 'failed') {
        return;
    }
    try {
        const url = `/audit/${props.audit.id}/status`;
        const token = props.shareUrl ? new URL(props.shareUrl).searchParams.get('token') : null;
        const params = token ? { token } : {};
        const response = await axios.get(url, { params });
        if (
            response.data.status !== props.audit.status ||
            response.data.pages_scanned !== props.audit.pages_scanned
        ) {
            router.reload({ only: ['audit', 'page', 'issues', 'pages', 'links', 'google'] });
        }
    } catch (error) {
        console.error('Failed to poll status:', error);
    }
};

const fetchPageSpeed = async () => {
    if (!pagespeedConfigured.value) return;
    try {
        const url = `/audit/${props.audit.id}/pagespeed`;
        const token = props.shareUrl ? new URL(props.shareUrl).searchParams.get('token') : null;
        const params = token ? { token } : {};
        const response = await axios.get(url, { params });
        if (response.data.pagespeed) {
            pagespeedLocal.value = response.data.pagespeed;
        }
    } catch (error) {
        console.error('Failed to fetch PageSpeed KPIs:', error);
    }
};

const fetchCrux = async () => {
    if (!cruxConfigured.value) return;
    try {
        const url = `/audit/${props.audit.id}/crux`;
        const token = props.shareUrl ? new URL(props.shareUrl).searchParams.get('token') : null;
        const params = token ? { token } : {};
        const response = await axios.get(url, { params });
        if (response.data.crux) {
            cruxLocal.value = response.data.crux;
        }
    } catch (error) {
        console.error('Failed to fetch CrUX KPIs:', error);
    }
};

const runPagespeed = async () => {
    if (!pagespeedConfigured.value || pagespeedLoading.value) return;
    pagespeedLoading.value = true;
    try {
        const url = `/audit/${props.audit.id}/pagespeed/run`;
        const token = props.shareUrl ? new URL(props.shareUrl).searchParams.get('token') : null;
        const params = token ? { token } : {};
        const response = await axios.post(url, null, { params });
        if (response.data.pagespeed) {
            pagespeedLocal.value = response.data.pagespeed;
        }
    } catch (error) {
        console.error('Failed to run PageSpeed:', error);
    } finally {
        pagespeedLoading.value = false;
    }
};

const runCrux = async () => {
    if (!cruxConfigured.value || cruxLoading.value) return;
    cruxLoading.value = true;
    try {
        const url = `/audit/${props.audit.id}/crux/run`;
        const token = props.shareUrl ? new URL(props.shareUrl).searchParams.get('token') : null;
        const params = token ? { token } : {};
        const response = await axios.post(url, null, { params });
        if (response.data.crux) {
            cruxLocal.value = response.data.crux;
        }
    } catch (error) {
        console.error('Failed to run CrUX:', error);
    } finally {
        cruxLoading.value = false;
    }
};

const loadGa4Properties = async () => {
    if (!ga4Connected.value) return;
    ga4Error.value = null;
    try {
        const params = {};
        if (props.audit?.url) {
            try {
                const host = new URL(props.audit.url).hostname;
                if (host) params.domain = host;
            } catch (e) {
                // Ignore invalid URL
            }
        }
        const response = await axios.get('/ga4/properties', { params });
        ga4Properties.value = response.data.properties || [];
        if (response.data.message) {
            ga4Error.value = response.data.message;
        }
        if (response.data.selected_property_id) {
            ga4SelectedPropertyId.value = response.data.selected_property_id;
            ga4ManualPropertyId.value = response.data.selected_property_id;
            await fetchGa4Summary(response.data.selected_property_id);
        }
    } catch (error) {
        ga4Error.value = error.response?.data?.error || 'Failed to load GA4 properties.';
    }
};

const fetchGa4Summary = async (propertyId = null) => {
    if (!ga4Connected.value) return;
    ga4Loading.value = true;
    ga4Error.value = null;
    try {
        const params = {};
        if (propertyId) {
            params.property_id = propertyId;
        }
        const response = await axios.get('/ga4/summary', { params });
        ga4Summary.value = response.data;
        if (response.data?.property_id) {
            ga4SelectedPropertyId.value = response.data.property_id;
            ga4ManualPropertyId.value = response.data.property_id;
        }
    } catch (error) {
        ga4Error.value = error.response?.data?.error || 'Failed to load GA4 data.';
    } finally {
        ga4Loading.value = false;
    }
};

const saveGa4Property = async (useManual = false) => {
    const propertyId = useManual
        ? ga4ManualPropertyId.value
        : (ga4SelectedPropertyId.value || ga4ManualPropertyId.value);
    if (!propertyId) {
        ga4Error.value = 'Select or enter a GA4 property ID.';
        return;
    }
    await fetchGa4Summary(propertyId);
};

const refreshGa4Summary = async () => {
    const propertyId = ga4SelectedPropertyId.value || ga4ManualPropertyId.value;
    await fetchGa4Summary(propertyId || null);
};

const disconnectGa4 = async () => {
    try {
        await axios.post('/auth/google/disconnect');
        ga4Summary.value = null;
        ga4Properties.value = [];
        ga4SelectedPropertyId.value = '';
        ga4ManualPropertyId.value = '';
        router.reload({ only: ['ga4Integration'] });
    } catch (error) {
        ga4Error.value = error.response?.data?.error || 'Failed to disconnect Google.';
    }
};

onMounted(() => {
    if (props.audit.status === 'queued' || props.audit.status === 'running') {
        pollingInterval.value = setInterval(pollStatus, 2000);
    }
    if (pagespeedConfigured.value) {
        fetchPageSpeed();
        pagespeedInterval.value = setInterval(fetchPageSpeed, 10000);
        if (!pagespeedData.value && !pagespeedLoading.value) {
            runPagespeed();
        }
    }
    if (cruxConfigured.value) {
        fetchCrux();
        cruxInterval.value = setInterval(fetchCrux, 15000);
        if (!cruxData.value && !cruxLoading.value) {
            runCrux();
        }
    }
    if (ga4Connected.value) {
        loadGa4Properties();
        if (ga4SelectedPropertyId.value || ga4ManualPropertyId.value) {
            fetchGa4Summary(ga4SelectedPropertyId.value || ga4ManualPropertyId.value);
        }
    }
});

onUnmounted(() => {
    if (pollingInterval.value) {
        clearInterval(pollingInterval.value);
    }
    if (pagespeedInterval.value) {
        clearInterval(pagespeedInterval.value);
    }
    if (cruxInterval.value) {
        clearInterval(cruxInterval.value);
    }
});

// Self-test checklist:
// - Desktop/tablet/mobile widths render cleanly
// - Re-run Audit + Export PDF buttons work
// - KPI values render (template cards, details tables)
</script>

<style scoped>
.audit-shell {
    background: #f6f8fb;
    min-height: 100vh;
    color: #0f172a;
}

.audit-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 24px;
}

.audit-header {
    position: sticky;
    top: 0;
    z-index: 30;
    background: #ffffff;
    border-bottom: 1px solid #e2e8f0;
}

.header-grid {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    flex-wrap: wrap;
}

.audit-title {
    font-size: 24px;
    font-weight: 600;
}

.audit-subtitle {
    font-size: 13px;
    color: #64748b;
    margin-top: 6px;
}

.muted {
    color: #64748b;
}

.divider {
    margin: 0 6px;
    color: #cbd5f5;
}

.header-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    align-items: center;
}

.range-select {
    display: inline-flex;
    background: #f1f5f9;
    border-radius: 999px;
    padding: 4px;
    gap: 4px;
}

.range-btn {
    font-size: 12px;
    padding: 6px 12px;
    border-radius: 999px;
    color: #64748b;
    font-weight: 600;
}

.range-btn.active {
    background: #ffffff;
    color: #0f172a;
    box-shadow: 0 8px 16px rgba(15, 23, 42, 0.08);
}

.btn-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 16px;
    font-size: 13px;
    font-weight: 600;
    color: #ffffff;
    background: #2563eb;
    border-radius: 10px;
    box-shadow: 0 12px 24px rgba(37, 99, 235, 0.2);
}

.btn-secondary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 16px;
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
}

.ghost-link {
    font-size: 12px;
    color: #2563eb;
    font-weight: 500;
}

.status-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 20px;
}

.status-pill {
    padding: 6px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
}

.status-running {
    background: #fef3c7;
    color: #92400e;
}

.status-success {
    background: #d1fae5;
    color: #065f46;
}

.status-failed {
    background: #fee2e2;
    color: #991b1b;
}

.state-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 20px;
    box-shadow: 0 10px 20px rgba(15, 23, 42, 0.05);
}

.state-card.is-error {
    border-color: #fecaca;
    background: #fff5f5;
}

.progress-track {
    height: 8px;
    border-radius: 999px;
    background: #e2e8f0;
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    background: #2563eb;
    border-radius: 999px;
}

.audit-grid {
    display: grid;
    gap: 16px;
    grid-template-columns: repeat(12, minmax(0, 1fr));
}

.col-span-4 {
    grid-column: span 4 / span 4;
}

.col-span-8 {
    grid-column: span 8 / span 8;
}

.dashboard-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 20px;
    box-shadow: 0 12px 24px rgba(15, 23, 42, 0.04);
    display: grid;
    gap: 16px;
}

.card-header {
    font-size: 16px;
    font-weight: 600;
    color: #0f172a;
}

.section-title {
    font-size: 18px;
    font-weight: 600;
}

.mini-grid {
    display: grid;
    gap: 12px;
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.mini-card {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 12px;
    background: #f8fafc;
}

.mini-label {
    font-size: 12px;
    color: #64748b;
}

.mini-value {
    font-size: 24px;
    font-weight: 700;
    margin-top: 6px;
}

.mini-trend {
    font-size: 12px;
    margin-top: 4px;
    font-weight: 600;
}

.mini-trend.up {
    color: #16a34a;
}

.mini-trend.down {
    color: #dc2626;
}

.chart-wrap {
    min-height: 160px;
}

.chart-skeleton {
    height: 160px;
    border-radius: 12px;
    background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    color: #94a3b8;
}

.ga-layout {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    align-items: center;
}

.legend {
    list-style: none;
    padding: 0;
    margin: 0;
    display: grid;
    gap: 8px;
    font-size: 12px;
    color: #475569;
}

.legend li {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}

.legend-dot {
    width: 10px;
    height: 10px;
    border-radius: 999px;
    display: inline-block;
}

.legend-value {
    font-weight: 600;
}

.ga-controls {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    align-items: center;
}

.radial-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
    align-items: center;
}

.backlink-grid {
    display: grid;
    grid-template-columns: 1fr 1.6fr;
    gap: 16px;
    align-items: center;
}

.gsc-stat {
    display: grid;
    gap: 6px;
}

.kpi-number {
    font-size: 36px;
    font-weight: 700;
}

.cta-row {
    margin-top: 12px;
}

.details-section {
    margin-top: 24px;
    display: grid;
    gap: 16px;
}

.overview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
    align-items: center;
}

.overview-main {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}

.section-label {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: #94a3b8;
}

.kpi-list {
    display: grid;
    gap: 8px;
    margin-top: 12px;
}

.kpi-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 13px;
    color: #0f172a;
}

.issue-groups {
    display: grid;
    gap: 16px;
}

.issue-group {
    display: grid;
    gap: 8px;
}

.issue-details {
    margin-top: 8px;
    font-size: 12px;
    color: #64748b;
}

.issue-list {
    margin-top: 8px;
    padding-left: 18px;
    color: #475569;
}

.filter-row {
    display: flex;
    align-items: center;
    gap: 10px;
}

.control-input {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 8px 12px;
    font-size: 13px;
}

.table-wrap {
    overflow-x: auto;
}

.report-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    font-size: 13px;
}

.report-table thead th {
    background: #f1f5f9;
    text-align: left;
    padding: 10px 12px;
    font-weight: 600;
}

.report-table tbody td {
    padding: 10px 12px;
    border-top: 1px solid #e2e8f0;
}

.report-table tbody tr:nth-child(even) td {
    background: #f8fafc;
}

.badge {
    display: inline-flex;
    align-items: center;
    padding: 3px 8px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 600;
}

.link {
    color: #2563eb;
    font-weight: 600;
}

.truncate {
    max-width: 280px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.skeleton-block {
    height: 120px;
    border-radius: 12px;
    background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
}

@media (max-width: 1100px) {
    .col-span-4,
    .col-span-8 {
        grid-column: span 12 / span 12;
    }

    .backlink-grid {
        grid-template-columns: 1fr;
    }

    .ga-layout {
        grid-template-columns: 1fr;
    }

    .details-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .audit-container {
        padding: 16px;
    }

    .header-actions {
        justify-content: flex-start;
    }

    .radial-grid {
        grid-template-columns: 1fr;
    }
}
</style>


