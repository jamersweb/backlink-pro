import Card from '@/Components/Shared/Card';

const cardConfig = [
    { key: 'total_urls_crawled', label: 'Total URLs Crawled', tone: 'text-white' },
    { key: 'indexable_urls', label: 'Indexable URLs', tone: 'text-emerald-400' },
    { key: 'non_indexable_urls', label: 'Non-Indexable URLs', tone: 'text-amber-300' },
    { key: 'critical_issues', label: 'Critical Issues', tone: 'text-red-400' },
    { key: 'warning_issues', label: 'Warnings', tone: 'text-yellow-300' },
    { key: 'redirect_urls', label: 'Redirect URLs', tone: 'text-sky-300' },
    { key: 'status_4xx_urls', label: '4xx URLs', tone: 'text-red-300' },
    { key: 'status_5xx_urls', label: '5xx URLs', tone: 'text-red-500' },
    { key: 'noindex_urls', label: 'Noindex URLs', tone: 'text-purple-300' },
    { key: 'missing_titles', label: 'Missing Titles', tone: 'text-orange-300' },
    { key: 'missing_meta_descriptions', label: 'Missing Meta Descriptions', tone: 'text-orange-200' },
    { key: 'missing_h1', label: 'Missing H1', tone: 'text-pink-300' },
    { key: 'duplicate_title_candidates', label: 'Duplicate Title Candidates', tone: 'text-fuchsia-300' },
    { key: 'duplicate_meta_candidates', label: 'Duplicate Meta Candidates', tone: 'text-fuchsia-200' },
    { key: 'canonical_issues', label: 'Canonical Issues', tone: 'text-indigo-300' },
    { key: 'blocked_pages', label: 'Blocked Pages', tone: 'text-gray-300' },
];

export default function AuditSummaryCards({ audit, stats = {}, topFixes = [] }) {
    return (
        <div className="space-y-5">
            <div className="grid grid-cols-1 lg:grid-cols-4 gap-4">
                <Card className="lg:col-span-1 border border-[rgba(255,255,255,0.08)] bg-[linear-gradient(180deg,rgba(24,24,24,0.96),rgba(10,10,10,1))]">
                    <p className="text-xs uppercase tracking-[0.18em] text-[rgba(255,255,255,0.52)]">Health Score</p>
                    <div className="mt-3 flex items-end gap-3">
                        <span className={`text-5xl font-bold ${
                            (audit.health_score ?? 0) >= 80 ? 'text-emerald-400' :
                            (audit.health_score ?? 0) >= 60 ? 'text-yellow-300' : 'text-red-400'
                        }`}>
                            {audit.health_score ?? '-'}
                        </span>
                        <span className="text-sm text-[rgba(255,255,255,0.56)] pb-2">/100</span>
                    </div>
                    {typeof stats.avg_response_time_ms === 'number' && (
                        <p className="mt-3 text-xs text-[rgba(255,255,255,0.6)]">Avg response: {stats.avg_response_time_ms}ms</p>
                    )}
                </Card>

                <Card className="lg:col-span-3 border border-[rgba(255,255,255,0.08)] bg-[linear-gradient(180deg,rgba(24,24,24,0.96),rgba(10,10,10,1))]">
                    <p className="text-xs uppercase tracking-[0.18em] text-[rgba(255,255,255,0.52)]">Coverage Metrics</p>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                        <MetricRow label="Title Coverage" value={`${stats.title_coverage_percent ?? 0}%`} />
                        <MetricRow label="Meta Coverage" value={`${stats.meta_coverage_percent ?? 0}%`} />
                        <MetricRow label="Avg Title Length" value={stats.avg_title_length ?? '-'} />
                        <MetricRow label="Avg Meta Length" value={stats.avg_meta_length ?? '-'} />
                        <MetricRow label="Critical / Warning / Info" value={`${stats.critical_issues ?? 0} / ${stats.warning_issues ?? 0} / ${stats.info_issues ?? 0}`} />
                        <MetricRow label="Noindex / Canonical Issues" value={`${stats.noindex_urls ?? 0} / ${stats.canonical_issues ?? 0}`} />
                    </div>
                </Card>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-8 gap-4">
                {cardConfig.map((item) => (
                    <Card
                        key={item.key}
                        className="border border-[rgba(255,255,255,0.08)] bg-[linear-gradient(180deg,rgba(22,22,22,0.96),rgba(10,10,10,1))]"
                    >
                        <p className="text-[11px] uppercase tracking-[0.15em] text-[rgba(255,255,255,0.52)]">{item.label}</p>
                        <p className={`mt-2 text-3xl font-semibold ${item.tone}`}>{stats[item.key] ?? 0}</p>
                    </Card>
                ))}
            </div>

            <Card className="border border-[rgba(255,255,255,0.08)] bg-[linear-gradient(180deg,rgba(22,22,22,0.96),rgba(10,10,10,1))]">
                <p className="text-xs uppercase tracking-[0.18em] text-[rgba(255,255,255,0.52)]">Top Issues To Fix First</p>
                {topFixes.length > 0 ? (
                    <div className="mt-4 grid grid-cols-1 xl:grid-cols-2 gap-3">
                        {topFixes.map((issue) => (
                            <div key={`${issue.type}-${issue.severity}`} className="rounded-xl border border-[rgba(255,255,255,0.09)] bg-[rgba(255,255,255,0.03)] p-4">
                                <div className="flex items-center justify-between gap-3">
                                    <p className="font-semibold text-white">{issue.label}</p>
                                    <span className={`text-xs px-2 py-1 rounded-full ${
                                        issue.severity === 'critical' ? 'bg-red-500/20 text-red-300' :
                                        issue.severity === 'warning' ? 'bg-yellow-500/20 text-yellow-200' :
                                        'bg-blue-500/20 text-blue-200'
                                    }`}>
                                        {issue.severity}
                                    </span>
                                </div>
                                <p className="text-xs text-[rgba(255,255,255,0.64)] mt-2">{issue.explanation}</p>
                                <div className="mt-3 flex items-center justify-between text-xs">
                                    <span className="text-[rgba(255,255,255,0.52)]">Affected URLs: <span className="text-white font-medium">{issue.affected_urls}</span></span>
                                    <span className="text-[rgba(255,255,255,0.52)]">Occurrences: <span className="text-white font-medium">{issue.total}</span></span>
                                </div>
                            </div>
                        ))}
                    </div>
                ) : (
                    <p className="mt-3 text-sm text-[rgba(255,255,255,0.6)]">No major issue clusters found in this audit.</p>
                )}
            </Card>
        </div>
    );
}

function MetricRow({ label, value }) {
    return (
        <div className="rounded-lg border border-[rgba(255,255,255,0.08)] bg-[rgba(255,255,255,0.02)] px-3 py-2">
            <p className="text-[11px] uppercase tracking-[0.12em] text-[rgba(255,255,255,0.5)]">{label}</p>
            <p className="text-base font-semibold text-white mt-1">{value}</p>
        </div>
    );
}
