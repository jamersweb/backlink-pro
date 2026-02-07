<template>
    <div class="min-h-screen bg-gray-50">
        <Head>
            <title>SEO Audit Report - {{ audit.url }}</title>
        </Head>

        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">SEO Audit Report</h1>
                    <p class="text-sm text-gray-600 mt-1">{{ audit.url }}</p>
                </div>
                <div class="flex gap-4">
                    <button
                        v-if="isOwner"
                        @click="shareAudit"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                    >
                        Share
                    </button>
                    <a
                        :href="exportUrl"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700"
                    >
                        Export PDF
                    </a>
                </div>
            </div>
        </header>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-6">
                <span
                    :class="{
                        'bg-yellow-100 text-yellow-800': audit.status === 'queued' || audit.status === 'running',
                        'bg-green-100 text-green-800': audit.status === 'completed',
                        'bg-red-100 text-red-800': audit.status === 'failed',
                    }"
                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                >
                    {{ statusLabel }}
                </span>
            </div>

            <div v-if="audit.status === 'queued' || audit.status === 'running'" class="text-center py-12">
                <div class="text-2xl text-gray-700 mb-2">Running SEO audit...</div>
                <div class="text-sm text-gray-500">This may take a few moments.</div>
                <div v-if="audit.pages_scanned > 0" class="mt-4">
                    <div class="text-sm text-gray-600">
                        Scanned {{ audit.pages_scanned }} / {{ audit.pages_discovered || '?' }} pages
                    </div>
                    <div class="w-full max-w-md mx-auto bg-gray-200 rounded-full h-2 mt-2">
                        <div
                            :style="{ width: (audit.progress_percent || 0) + '%' }"
                            class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                        ></div>
                    </div>
                </div>
            </div>

            <div v-else-if="audit.status === 'failed'" class="bg-red-50 border border-red-200 rounded-lg p-6 mb-6">
                <div class="text-lg font-semibold text-red-900">Audit Failed</div>
                <div class="text-red-700">{{ audit.error || 'An error occurred while running the audit.' }}</div>
            </div>

            <div v-else-if="audit.status === 'completed'" class="space-y-8">
                <section class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900">Report Search URL</h2>
                            <p class="text-sm text-gray-500">Audit source</p>
                        </div>
                        <div class="text-sm text-gray-900 break-all">{{ audit.url }}</div>
                    </div>

                    <div class="grid md:grid-cols-2 gap-4 mt-6">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-xs text-gray-500 uppercase">PageSpeed Insights</div>
                            <div class="text-sm font-semibold text-gray-900 mt-1">{{ pagespeedStatusLabel }}</div>
                            <div class="text-sm text-gray-600 mt-1">{{ pagespeedStatusMessage }}</div>
                            <div class="mt-3">
                                <button
                                    @click="runPagespeed"
                                    :disabled="!pagespeedConfigured || pagespeedLoading"
                                    class="px-3 py-2 text-xs font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    {{ pagespeedLoading ? 'Running...' : 'Run PSI' }}
                                </button>
                            </div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-xs text-gray-500 uppercase">Google Analytics (GA4)</div>
                            <div class="text-sm font-semibold text-gray-900 mt-1">{{ ga4StatusLabel }}</div>
                            <div class="text-sm text-gray-600 mt-1">{{ ga4StatusMessage }}</div>
                            <div class="mt-2 flex gap-2">
                                <a
                                    v-if="ga4CanConnect && !ga4Connected"
                                    :href="ga4ConnectUrl"
                                    class="px-3 py-2 text-xs font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700"
                                >
                                    Connect Google (GA4)
                                </a>
                                <button
                                    v-if="ga4Connected"
                                    @click="disconnectGa4"
                                    class="px-3 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                                >
                                    Disconnect
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                <section v-if="kpisOverview" class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900">Audit KPI Overview</h2>
                            <p class="text-sm text-gray-500">Key scores and grades</p>
                        </div>
                        <div class="text-right">
                            <div class="text-3xl font-bold text-gray-900">{{ display(kpisOverview.overall_grade) }}</div>
                            <div class="text-sm text-gray-600">{{ display(kpisOverview.recommendations_count) }} recommendations</div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mt-6">
                        <div v-for="card in overviewGradeCards" :key="card.label" class="bg-gray-50 rounded-lg p-4 text-center">
                            <div class="text-xs text-gray-500 uppercase">{{ card.label }}</div>
                            <div class="text-lg font-semibold text-gray-900">{{ card.value }}</div>
                        </div>
                    </div>
                </section>

                <section class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900">Overall Score</h2>
                            <p class="text-sm text-gray-500">Summary of your website SEO health</p>
                        </div>
                        <div class="text-right">
                            <div class="text-3xl font-bold text-green-600">{{ display(audit.overall_score) }}</div>
                            <div class="text-sm text-gray-600">{{ display(audit.overall_grade) }}</div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div
                                class="bg-green-600 h-2 rounded-full"
                                :style="{ width: (audit.overall_score || 0) + '%' }"
                            ></div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
                        <div v-for="card in categoryCards" :key="card.label" class="bg-gray-50 rounded-lg p-4 text-center">
                            <div class="text-xs text-gray-500 uppercase">{{ card.label }}</div>
                            <div class="text-lg font-semibold text-gray-900">{{ card.value }}</div>
                        </div>
                    </div>
                </section>

                <section class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Crawl Statistics</h3>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                        <div v-for="card in crawlStatsCards" :key="card.label" class="bg-gray-50 rounded-lg p-4 text-center">
                            <div class="text-xs text-gray-500">{{ card.label }}</div>
                            <div class="text-lg font-semibold text-gray-900">{{ card.value }}</div>
                        </div>
                    </div>
                </section>

                <section class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Homepage Metrics</h3>
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">Meta Tags</h4>
                            <div class="text-sm text-gray-600 space-y-1">
                                <div><span class="font-medium">Title:</span> {{ display(page?.title) }} ({{ display(page?.title_len) }} chars)</div>
                                <div><span class="font-medium">Meta Desc:</span> {{ display(page?.meta_description) }} ({{ display(page?.meta_len) }} chars)</div>
                                <div><span class="font-medium">Canonical:</span> {{ display(page?.canonical_url) }}</div>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">Content Structure</h4>
                            <div class="text-sm text-gray-600 space-y-1">
                                <div>H1 Tags: {{ display(page?.h1_count) }}</div>
                                <div>H2 Tags: {{ display(page?.h2_count) }}</div>
                                <div>H3 Tags: {{ display(page?.h3_count) }}</div>
                                <div>Word Count: {{ display(page?.word_count) }}</div>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">Images</h4>
                            <div class="text-sm text-gray-600 space-y-1">
                                <div>Total Images: {{ display(page?.images_total) }}</div>
                                <div>Missing Alt Text: {{ display(page?.images_missing_alt) }}</div>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">Links</h4>
                            <div class="text-sm text-gray-600 space-y-1">
                                <div>Internal Links: {{ display(page?.internal_links_count) }}</div>
                                <div>External Links: {{ display(page?.external_links_count) }}</div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Issues Summary</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-gray-50 rounded-lg p-4 text-center">
                            <div class="text-xs text-gray-500">Total Issues</div>
                            <div class="text-lg font-semibold text-gray-900">{{ display(summary.total_issues) }}</div>
                        </div>
                        <div class="bg-red-50 rounded-lg p-4 text-center">
                            <div class="text-xs text-red-600">High Impact</div>
                            <div class="text-lg font-semibold text-red-700">{{ display(summary.high_impact_issues) }}</div>
                        </div>
                        <div class="bg-yellow-50 rounded-lg p-4 text-center">
                            <div class="text-xs text-yellow-700">Medium Impact</div>
                            <div class="text-lg font-semibold text-yellow-800">{{ display(summary.medium_impact_issues) }}</div>
                        </div>
                        <div class="bg-blue-50 rounded-lg p-4 text-center">
                            <div class="text-xs text-blue-700">Low Impact</div>
                            <div class="text-lg font-semibold text-blue-800">{{ display(summary.low_impact_issues) }}</div>
                        </div>
                    </div>

                    <div v-if="issues && issues.length" class="space-y-3">
                        <div
                            v-for="issue in issues"
                            :key="issue.id"
                            class="border border-gray-200 rounded-lg p-4"
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="font-semibold text-gray-900">{{ issue.title }}</div>
                                    <div class="text-xs text-gray-500">{{ issue.code }}</div>
                                </div>
                                <span
                                    :class="impactBadgeClass(issue.impact)"
                                    class="px-2 py-1 text-xs font-medium rounded"
                                >
                                    {{ issue.impact }}
                                </span>
                            </div>
                            <div class="mt-2 text-sm text-gray-700">{{ issue.description }}</div>
                        </div>
                    </div>
                    <div v-else class="text-sm text-gray-500">No issues found.</div>
                </section>

                <section v-if="kpisOnPage" class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">On-Page SEO KPIs</h3>
                    <div class="grid md:grid-cols-4 gap-4">
                        <div v-for="card in onPageCards" :key="card.label" class="bg-gray-50 rounded-lg p-4 text-center">
                            <div class="text-xs text-gray-500">{{ card.label }}</div>
                            <div class="text-lg font-semibold text-gray-900">{{ card.value }}</div>
                        </div>
                    </div>
                    <div class="grid md:grid-cols-2 gap-6 mt-6">
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">Top Keywords</h4>
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-gray-500">
                                        <th class="py-2">Keyword</th>
                                        <th>Frequency</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in onPageTopKeywords" :key="row.keyword" class="border-t">
                                        <td class="py-2">{{ row.keyword }}</td>
                                        <td>{{ row.frequency }}</td>
                                    </tr>
                                    <tr v-if="onPageTopKeywords.length === 0" class="border-t"><td class="py-2" colspan="2">N/A</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">Top Phrases</h4>
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-gray-500">
                                        <th class="py-2">Phrase</th>
                                        <th>Frequency</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in onPagePhrases" :key="row.phrase" class="border-t">
                                        <td class="py-2">{{ row.phrase }}</td>
                                        <td>{{ row.frequency }}</td>
                                    </tr>
                                    <tr v-if="onPagePhrases.length === 0" class="border-t"><td class="py-2" colspan="2">N/A</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <section v-if="kpisLinks" class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Links KPIs</h3>
                    <div class="grid md:grid-cols-4 gap-4">
                        <div v-for="card in linksCards" :key="card.label" class="bg-gray-50 rounded-lg p-4 text-center">
                            <div class="text-xs text-gray-500">{{ card.label }}</div>
                            <div class="text-lg font-semibold text-gray-900">{{ card.value }}</div>
                        </div>
                    </div>
                    <div class="grid md:grid-cols-2 gap-6 mt-6">
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">Top Backlinks</h4>
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-gray-500">
                                        <th class="py-2">URL</th>
                                        <th>Anchor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in linksTopBacklinks" :key="row.referring_page_url" class="border-t">
                                        <td class="py-2">{{ row.referring_page_url }}</td>
                                        <td>{{ row.anchor_text }}</td>
                                    </tr>
                                    <tr v-if="linksTopBacklinks.length === 0" class="border-t"><td class="py-2" colspan="2">N/A</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">Top Anchors</h4>
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-gray-500">
                                        <th class="py-2">Anchor</th>
                                        <th>Backlinks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in linksTopAnchors" :key="row.anchor" class="border-t">
                                        <td class="py-2">{{ row.anchor }}</td>
                                        <td>{{ row.backlinks_count }}</td>
                                    </tr>
                                    <tr v-if="linksTopAnchors.length === 0" class="border-t"><td class="py-2" colspan="2">N/A</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <section v-if="kpisPerformance" class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Performance KPIs</h3>
                    <div class="grid md:grid-cols-4 gap-4">
                        <div v-for="card in performanceCards" :key="card.label" class="bg-gray-50 rounded-lg p-4 text-center">
                            <div class="text-xs text-gray-500">{{ card.label }}</div>
                            <div class="text-lg font-semibold text-gray-900">{{ card.value }}</div>
                        </div>
                    </div>
                    <div class="grid md:grid-cols-2 gap-6 mt-6">
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">Download Size Breakdown (MB)</h4>
                            <table class="min-w-full text-sm">
                                <tbody>
                                    <tr v-for="row in performanceBreakdownRows" :key="row.label" class="border-t">
                                        <td class="py-2">{{ row.label }}</td>
                                        <td>{{ row.value }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">Resources</h4>
                            <table class="min-w-full text-sm">
                                <tbody>
                                    <tr v-for="row in performanceResourceRows" :key="row.label" class="border-t">
                                        <td class="py-2">{{ row.label }}</td>
                                        <td>{{ row.value }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <section v-if="kpisUsability" class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Usability KPIs</h3>
                    <div class="grid md:grid-cols-4 gap-4">
                        <div v-for="card in usabilityCards" :key="card.label" class="bg-gray-50 rounded-lg p-4 text-center">
                            <div class="text-xs text-gray-500">{{ card.label }}</div>
                            <div class="text-lg font-semibold text-gray-900">{{ card.value }}</div>
                        </div>
                    </div>
                    <div class="grid md:grid-cols-2 gap-6 mt-6">
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">Mobile Lab Metrics</h4>
                            <table class="min-w-full text-sm">
                                <tbody>
                                    <tr v-for="row in usabilityMobileLabRows" :key="row.label" class="border-t">
                                        <td class="py-2">{{ row.label }}</td>
                                        <td>{{ row.value }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">Desktop Lab Metrics</h4>
                            <table class="min-w-full text-sm">
                                <tbody>
                                    <tr v-for="row in usabilityDesktopLabRows" :key="row.label" class="border-t">
                                        <td class="py-2">{{ row.label }}</td>
                                        <td>{{ row.value }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <section v-if="kpisSocial" class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Social KPIs</h3>
                    <div class="grid md:grid-cols-4 gap-4">
                        <div v-for="card in socialCards" :key="card.label" class="bg-gray-50 rounded-lg p-4 text-center">
                            <div class="text-xs text-gray-500">{{ card.label }}</div>
                            <div class="text-lg font-semibold text-gray-900">{{ card.value }}</div>
                        </div>
                    </div>
                </section>

                <section v-if="kpisLocal" class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Local SEO KPIs</h3>
                    <div class="grid md:grid-cols-4 gap-4">
                        <div v-for="card in localCards" :key="card.label" class="bg-gray-50 rounded-lg p-4 text-center">
                            <div class="text-xs text-gray-500">{{ card.label }}</div>
                            <div class="text-lg font-semibold text-gray-900">{{ card.value }}</div>
                        </div>
                    </div>
                </section>

                <section v-if="kpisTechEmail" class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Technology + Email Auth KPIs</h3>
                    <div class="grid md:grid-cols-4 gap-4">
                        <div v-for="card in techCards" :key="card.label" class="bg-gray-50 rounded-lg p-4 text-center">
                            <div class="text-xs text-gray-500">{{ card.label }}</div>
                            <div class="text-lg font-semibold text-gray-900">{{ card.value }}</div>
                        </div>
                    </div>
                    <div class="mt-6">
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Detected Technologies</h4>
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500">
                                    <th class="py-2">Name</th>
                                    <th>Version</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in techList" :key="row.name" class="border-t">
                                    <td class="py-2">{{ row.name }}</td>
                                    <td>{{ row.version || 'N/A' }}</td>
                                </tr>
                                <tr v-if="techList.length === 0" class="border-t"><td class="py-2" colspan="2">N/A</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Google KPIs</h3>
                        <a
                            v-if="!googleConnected && audit.organization_id"
                            :href="`/orgs/${audit.organization_id}/integrations/google`"
                            class="text-sm text-blue-600 hover:text-blue-700"
                        >
                            Connect Google
                        </a>
                    </div>

                    <div>
                        <div class="border-b border-gray-200 mb-4">
                            <nav class="-mb-px flex space-x-6">
                                <button
                                    v-for="tab in googleTabs"
                                    :key="tab"
                                    @click="googleTab = tab"
                                    class="py-2 text-sm font-medium border-b-2"
                                    :class="googleTab === tab ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                                >
                                    {{ googleTabLabels[tab] }}
                                </button>
                            </nav>
                        </div>

                        <div v-if="googleTab === 'pagespeed'" class="space-y-4">
                            <div v-if="!pagespeedConfigured" class="text-sm text-gray-500">PageSpeed not configured.</div>
                            <div v-else-if="pagespeedStatus === 'limit_exceeded'" class="text-sm text-gray-500">
                                Daily PageSpeed limit exceeded. Upgrade your plan to run more checks.
                            </div>
                            <div v-else-if="pagespeedStatus === 'failed'" class="text-sm text-gray-500">
                                PageSpeed failed: {{ pagespeedError || 'Unknown error' }}
                            </div>
                            <div v-else-if="!pagespeedData" class="text-sm text-gray-500">PageSpeed data not available yet.</div>
                            <div v-else>
                                <div class="flex gap-2 mb-4">
                                    <button
                                        v-for="mode in pagespeedModes"
                                        :key="mode"
                                        @click="pagespeedMode = mode"
                                        class="px-3 py-1 text-xs font-medium rounded-full border"
                                        :class="pagespeedMode === mode ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-200'"
                                    >
                                        {{ mode === 'mobile' ? 'Mobile' : 'Desktop' }}
                                    </button>
                                </div>
                                <div class="grid md:grid-cols-4 gap-4">
                                    <div v-for="card in pagespeedScoreCards" :key="card.label" class="bg-gray-50 rounded-lg p-4 text-center">
                                        <div class="text-xs text-gray-500">{{ card.label }}</div>
                                        <div class="text-lg font-semibold text-gray-900">{{ card.value }}</div>
                                    </div>
                                </div>

                                <div class="grid md:grid-cols-3 gap-4 mt-4">
                                    <div v-for="card in pagespeedLabCards" :key="card.label" class="bg-gray-50 rounded-lg p-4 text-center">
                                        <div class="text-xs text-gray-500">{{ card.label }}</div>
                                        <div class="text-lg font-semibold text-gray-900">{{ card.value }}</div>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <h4 class="text-sm font-semibold text-gray-700 mb-2">Opportunities</h4>
                                    <table class="min-w-full text-sm">
                                        <thead>
                                            <tr class="text-left text-gray-500">
                                                <th class="py-2">Title</th>
                                                <th>Savings (ms)</th>
                                                <th>Savings (KB)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="row in pagespeedOpportunities" :key="row.id" class="border-t">
                                                <td class="py-2">{{ row.title }}</td>
                                                <td>{{ row.savings_ms ?? 'N/A' }}</td>
                                                <td>{{ row.savings_bytes ? Math.round(row.savings_bytes / 1024) : 'N/A' }}</td>
                                            </tr>
                                            <tr v-if="pagespeedOpportunities.length === 0" class="border-t"><td class="py-2" colspan="3">N/A</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div v-if="googleTab === 'ga4'" class="space-y-4">
                            <div v-if="!ga4CanConnect" class="text-sm text-gray-500">
                                Log in to connect Google Analytics (GA4).
                            </div>
                            <div v-else class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="text-xs text-gray-500">Google account</div>
                                        <div v-if="ga4Connected" class="text-sm text-gray-900">
                                            Connected as {{ ga4Integration.email || 'Google user' }}
                                        </div>
                                        <div v-else class="text-sm text-gray-500">Not connected</div>
                                    </div>
                                    <div class="flex gap-2">
                                        <a
                                            v-if="!ga4Connected"
                                            :href="ga4ConnectUrl"
                                            class="px-3 py-2 text-xs font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700"
                                        >
                                            Connect Google (GA4)
                                        </a>
                                        <button
                                            v-else
                                            @click="disconnectGa4"
                                            class="px-3 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                                        >
                                            Disconnect
                                        </button>
                                    </div>
                                </div>

                                <div v-if="ga4MissingRefreshToken" class="text-sm text-red-600">
                                    Please disconnect and reconnect Google to grant offline access.
                                </div>

                                <div v-if="flash.error" class="text-sm text-red-600">{{ flash.error }}</div>
                                <div v-if="flash.success" class="text-sm text-green-600">{{ flash.success }}</div>

                                <div v-if="ga4Error" class="text-sm text-red-600">{{ ga4Error }}</div>

                                <div v-if="ga4Connected" class="space-y-4">
                                    <div class="grid md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">GA4 Property</label>
                                            <select
                                                v-model="ga4SelectedPropertyId"
                                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm"
                                            >
                                                <option value="">Select a property</option>
                                                <option
                                                    v-for="property in ga4Properties"
                                                    :key="property.property_id"
                                                    :value="property.property_id"
                                                >
                                                    {{ property.display_name }} ({{ property.property_id }})
                                                </option>
                                            </select>
                                        </div>
                                        <div class="flex items-end gap-2">
                                            <button
                                                @click="saveGa4Property(false)"
                                                class="px-3 py-2 text-xs font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700"
                                            >
                                                Save & Load
                                            </button>
                                            <button
                                                @click="refreshGa4Summary"
                                                class="px-3 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                                            >
                                                Refresh
                                            </button>
                                        </div>
                                    </div>

                                    <div v-if="ga4Properties.length === 0" class="text-sm text-gray-500">
                                        You don't have access to any GA4 properties. Please add access in GA4 Admin.
                                    </div>

                                    <div class="grid md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Manual GA4 Property ID</label>
                                            <input
                                                v-model="ga4ManualPropertyId"
                                                type="text"
                                                placeholder="properties/123456789"
                                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm"
                                            />
                                        </div>
                                        <div class="flex items-end">
                                            <button
                                                @click="saveGa4Property(true)"
                                                class="px-3 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                                            >
                                                Use Property ID
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="ga4Loading" class="text-sm text-gray-500">Loading GA4 data...</div>
                            <div v-else-if="ga4Summary" class="space-y-4">
                                <div class="grid md:grid-cols-4 gap-4">
                                    <div v-for="card in ga4Cards" :key="card.label" class="bg-gray-50 rounded-lg p-4 text-center">
                                        <div class="text-xs text-gray-500">{{ card.label }}</div>
                                        <div class="text-lg font-semibold text-gray-900">{{ card.value }}</div>
                                    </div>
                                </div>
                                <div class="grid md:grid-cols-2 gap-6">
                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Top Pages</h4>
                                        <table class="min-w-full text-sm">
                                            <thead>
                                                <tr class="text-left text-gray-500">
                                                    <th class="py-2">Page</th>
                                                    <th>Sessions</th>
                                                    <th>Users</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="row in ga4TopPages" :key="row.page_path" class="border-t">
                                                    <td class="py-2">{{ row.page_path }}</td>
                                                    <td>{{ row.sessions }}</td>
                                                    <td>{{ row.active_users }}</td>
                                                </tr>
                                                <tr v-if="ga4TopPages.length === 0" class="border-t"><td class="py-2" colspan="3">N/A</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Top Sources</h4>
                                        <table class="min-w-full text-sm">
                                            <thead>
                                                <tr class="text-left text-gray-500">
                                                    <th class="py-2">Source / Medium</th>
                                                    <th>Sessions</th>
                                                    <th>Users</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="row in ga4TopSources" :key="row.source_medium" class="border-t">
                                                    <td class="py-2">{{ row.source_medium }}</td>
                                                    <td>{{ row.sessions }}</td>
                                                    <td>{{ row.active_users }}</td>
                                                </tr>
                                                <tr v-if="ga4TopSources.length === 0" class="border-t"><td class="py-2" colspan="3">N/A</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="googleTab === 'gsc'" class="space-y-4">
                            <div v-if="!googleConnected" class="text-sm text-gray-500">Connect Google to view Search Console KPIs.</div>
                            <div v-else-if="!gscData" class="text-sm text-gray-500">Search Console data not available.</div>
                            <div v-else>
                                <div class="grid md:grid-cols-4 gap-4">
                                    <div v-for="card in gscCards" :key="card.label" class="bg-gray-50 rounded-lg p-4 text-center">
                                        <div class="text-xs text-gray-500">{{ card.label }}</div>
                                        <div class="text-lg font-semibold text-gray-900">{{ card.value }}</div>
                                    </div>
                                </div>
                                <div class="grid md:grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Top Queries</h4>
                                        <table class="min-w-full text-sm">
                                            <thead>
                                                <tr class="text-left text-gray-500">
                                                    <th class="py-2">Query</th>
                                                    <th>Clicks</th>
                                                    <th>Impr.</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="row in gscTopQueries" :key="row.query" class="border-t">
                                                    <td class="py-2">{{ row.query }}</td>
                                                    <td>{{ row.clicks }}</td>
                                                    <td>{{ row.impressions }}</td>
                                                </tr>
                                                <tr v-if="gscTopQueries.length === 0" class="border-t"><td class="py-2" colspan="3">N/A</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Top Pages</h4>
                                        <table class="min-w-full text-sm">
                                            <thead>
                                                <tr class="text-left text-gray-500">
                                                    <th class="py-2">Page</th>
                                                    <th>Clicks</th>
                                                    <th>Impr.</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="row in gscTopPages" :key="row.page_url" class="border-t">
                                                    <td class="py-2">{{ row.page_url }}</td>
                                                    <td>{{ row.clicks }}</td>
                                                    <td>{{ row.impressions }}</td>
                                                </tr>
                                                <tr v-if="gscTopPages.length === 0" class="border-t"><td class="py-2" colspan="3">N/A</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
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

const pageProps = usePage();
const flash = computed(() => pageProps.props.flash || {});

const pollingInterval = ref(null);
const pagespeedInterval = ref(null);
const pagespeedLocal = ref(props.google?.pagespeed || null);
const pagespeedMode = ref('mobile');
const pagespeedModes = ['mobile', 'desktop'];
const googleTab = ref('pagespeed');
const googleTabs = ['pagespeed', 'ga4', 'gsc'];
const googleTabLabels = {
    pagespeed: 'PageSpeed',
    ga4: 'Analytics (GA4)',
    gsc: 'Search Console (GSC)',
};

const statusLabel = computed(() => {
    const labels = { queued: 'Queued', running: 'Running', completed: 'Completed', failed: 'Failed' };
    return labels[props.audit.status] || props.audit.status;
});

const summary = computed(() => props.audit.summary || {});
const googleData = computed(() => props.google || {});
const googleConnected = computed(() => googleData.value.connected);
const pagespeedConfigured = computed(() => googleData.value.pagespeed_configured);
const kpis = computed(() => props.audit?.audit_kpis || {});
const kpisOverview = computed(() => kpis.value?.overview || null);
const kpisOnPage = computed(() => kpis.value?.on_page_seo || null);
const kpisLinks = computed(() => kpis.value?.links || null);
const kpisPerformance = computed(() => kpis.value?.performance || null);
const kpisUsability = computed(() => kpis.value?.usability || null);
const kpisSocial = computed(() => kpis.value?.social || null);
const kpisLocal = computed(() => kpis.value?.local_seo || null);
const kpisTechEmail = computed(() => kpis.value?.tech_email || null);

const pagespeedData = computed(() => pagespeedLocal.value || googleData.value.pagespeed);
const gscData = computed(() => googleData.value.gsc);

const pagespeedKpis = computed(() => {
    if (!pagespeedData.value) return null;
    const preferred = pagespeedData.value[pagespeedMode.value]?.kpis;
    if (preferred) return preferred;
    return pagespeedData.value.mobile?.kpis || pagespeedData.value.desktop?.kpis || null;
});

const pagespeedStatus = computed(() => {
    if (!pagespeedData.value) return null;
    if (pagespeedData.value.status) return pagespeedData.value.status;
    const mobileStatus = pagespeedData.value.mobile?.status;
    const desktopStatus = pagespeedData.value.desktop?.status;
    return mobileStatus || desktopStatus || null;
});

const pagespeedLoading = ref(false);

const pagespeedError = computed(() => {
    if (!pagespeedData.value) return null;
    if (pagespeedData.value.error) return pagespeedData.value.error;
    return pagespeedData.value.mobile?.error || pagespeedData.value.desktop?.error || null;
});

const pagespeedStatusLabel = computed(() => {
    if (!pagespeedConfigured.value) return 'Not configured';
    if (pagespeedStatus.value === 'limit_exceeded') return 'Limit exceeded';
    if (pagespeedStatus.value === 'failed') return 'Failed';
    if (pagespeedData.value) return 'Ready';
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
        { label: 'LCP (ms)', value: display(metrics.lcp_ms) },
        { label: 'CLS', value: display(metrics.cls) },
        { label: 'TBT (ms)', value: display(metrics.tbt_ms) },
        { label: 'FCP (ms)', value: display(metrics.fcp_ms) },
        { label: 'Speed Index (ms)', value: display(metrics.speed_index_ms) },
        { label: 'TTI (ms)', value: display(metrics.tti_ms) },
    ];
});

const pagespeedOpportunities = computed(() => pagespeedKpis.value?.opportunities || []);

const ga4Integration = computed(() => props.ga4Integration || {});
const ga4Connected = computed(() => ga4Integration.value.connected);
const ga4CanConnect = computed(() => ga4Integration.value.can_connect);
const ga4MissingRefreshToken = computed(() => ga4Integration.value.missing_refresh_token);
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
    if (ga4MissingRefreshToken.value) return 'Reconnect required';
    if (ga4Error.value) return 'Error';
    if (!ga4SelectedPropertyId.value && !ga4ManualPropertyId.value) return 'Property not selected';
    if (!ga4Summary.value) return 'Pending';
    return 'Ready';
});

const ga4StatusMessage = computed(() => {
    if (!ga4CanConnect.value) return 'Log in to connect GA4.';
    if (!ga4Connected.value) return 'Connect Google to load GA4 KPIs.';
    if (ga4MissingRefreshToken.value) return 'Disconnect and reconnect to grant offline access.';
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

const roundValue = (value) => {
    if (value === null || value === undefined || value === '') return null;
    const numeric = Number(value);
    if (Number.isNaN(numeric)) return null;
    return Math.round(numeric);
};

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

const loadGa4Properties = async () => {
    if (!ga4Connected.value) return;
    ga4Error.value = null;
    try {
        const response = await axios.get('/ga4/properties');
        ga4Properties.value = response.data.properties || [];
        if (response.data.message) {
            ga4Error.value = response.data.message;
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
});
</script>
