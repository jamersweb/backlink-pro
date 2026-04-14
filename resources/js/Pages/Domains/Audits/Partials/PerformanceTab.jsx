import Card from '@/Components/Shared/Card';

export default function PerformanceTab({ audit, performanceSummary = {}, hasPageSpeedApiKey = false }) {
    const metrics = audit.metrics || [];

    return (
        <div className="space-y-4">
            <Card className="border border-[rgba(255,255,255,0.08)] bg-[linear-gradient(180deg,rgba(22,22,22,0.96),rgba(10,10,10,1))]">
                <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-6 gap-4">
                    <Metric label="Rows" value={performanceSummary.total_rows ?? 0} />
                    <Metric label="Avg Score" value={performanceSummary.avg_score ?? '-'} />
                    <Metric label="Good (90+)" value={performanceSummary.good_scores ?? 0} />
                    <Metric label="Needs Work" value={performanceSummary.needs_improvement_scores ?? 0} />
                    <Metric label="Poor (<50)" value={performanceSummary.poor_scores ?? 0} />
                    <Metric label="Mobile / Desktop" value={`${performanceSummary.mobile_rows ?? 0} / ${performanceSummary.desktop_rows ?? 0}`} />
                </div>
            </Card>

            {!hasPageSpeedApiKey && (
                <Card className="border border-yellow-300/30 bg-yellow-500/10">
                    <h4 className="text-sm font-semibold text-yellow-100">Performance data setup required</h4>
                    <p className="mt-2 text-sm text-yellow-200/90">
                        Core Web Vitals and PageSpeed metrics require `GOOGLE_PAGESPEED_API_KEY` (or org BYOK) configuration.
                        After configuring the key, run a new audit with <strong>Include CWV</strong> enabled.
                    </p>
                </Card>
            )}

            {hasPageSpeedApiKey && metrics.length === 0 && (
                <Card className="border border-[rgba(255,255,255,0.08)] bg-[linear-gradient(180deg,rgba(22,22,22,0.96),rgba(10,10,10,1))]">
                    <p className="text-sm text-[rgba(255,255,255,0.65)]">
                        No performance rows found for this audit yet. Re-run crawl with CWV enabled to collect per-page metrics.
                    </p>
                </Card>
            )}

            {metrics.length > 0 && (
                <Card className="border border-[rgba(255,255,255,0.08)] bg-[linear-gradient(180deg,rgba(22,22,22,0.96),rgba(10,10,10,1))]">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-[rgba(255,255,255,0.08)]">
                            <thead>
                                <tr className="text-xs uppercase text-[rgba(255,255,255,0.54)]">
                                    <th className="px-4 py-3 text-left">URL</th>
                                    <th className="px-4 py-3 text-left">Strategy</th>
                                    <th className="px-4 py-3 text-left">Score</th>
                                    <th className="px-4 py-3 text-left">LCP</th>
                                    <th className="px-4 py-3 text-left">CLS</th>
                                    <th className="px-4 py-3 text-left">INP</th>
                                    <th className="px-4 py-3 text-left">FCP</th>
                                    <th className="px-4 py-3 text-left">TTFB</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-[rgba(255,255,255,0.06)]">
                                {metrics.map((metric) => (
                                    <tr key={metric.id} className="hover:bg-[rgba(255,255,255,0.03)]">
                                        <td className="px-4 py-3 text-sm">
                                            <a href={metric.url} target="_blank" rel="noreferrer" className="text-sky-300 hover:underline break-all">
                                                {metric.url}
                                            </a>
                                        </td>
                                        <td className="px-4 py-3 text-sm text-white capitalize">{metric.strategy}</td>
                                        <td className="px-4 py-3 text-sm">
                                            {metric.performance_score !== null ? (
                                                <span className={
                                                    metric.performance_score >= 90 ? 'text-emerald-300 font-semibold' :
                                                    metric.performance_score >= 50 ? 'text-yellow-200 font-semibold' :
                                                    'text-red-300 font-semibold'
                                                }>
                                                    {metric.performance_score}
                                                </span>
                                            ) : <span className="text-[rgba(255,255,255,0.46)]">-</span>}
                                        </td>
                                        <td className="px-4 py-3 text-sm text-[rgba(255,255,255,0.72)]">{metric.lcp_ms ? `${(metric.lcp_ms / 1000).toFixed(2)}s` : '-'}</td>
                                        <td className="px-4 py-3 text-sm text-[rgba(255,255,255,0.72)]">{metric.cls_x1000 ? (metric.cls_x1000 / 1000).toFixed(3) : '-'}</td>
                                        <td className="px-4 py-3 text-sm text-[rgba(255,255,255,0.72)]">{metric.inp_ms ? `${metric.inp_ms}ms` : '-'}</td>
                                        <td className="px-4 py-3 text-sm text-[rgba(255,255,255,0.72)]">{metric.fcp_ms ? `${(metric.fcp_ms / 1000).toFixed(2)}s` : '-'}</td>
                                        <td className="px-4 py-3 text-sm text-[rgba(255,255,255,0.72)]">{metric.ttfb_ms ? `${metric.ttfb_ms}ms` : '-'}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </Card>
            )}
        </div>
    );
}

function Metric({ label, value }) {
    return (
        <div className="rounded-lg border border-[rgba(255,255,255,0.08)] bg-[rgba(255,255,255,0.02)] p-3">
            <p className="text-[11px] uppercase tracking-[0.1em] text-[rgba(255,255,255,0.52)]">{label}</p>
            <p className="text-xl font-semibold text-white mt-1">{value}</p>
        </div>
    );
}
