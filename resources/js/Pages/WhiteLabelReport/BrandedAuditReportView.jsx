const formatDateTime = (value) => {
    if (!value) return null;

    try {
        return new Intl.DateTimeFormat('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
        }).format(new Date(value));
    } catch (error) {
        return value;
    }
};

function SectionCard({ title, children, className = '' }) {
    return (
        <section className={`rounded-[28px] border border-white/8 bg-[#181818] p-6 shadow-[0_16px_48px_rgba(0,0,0,0.18)] ${className}`}>
            <div className="mb-4 flex items-center justify-between gap-3">
                <h3 className="text-xl font-semibold tracking-[-0.03em] text-[#fff7f2]">{title}</h3>
            </div>
            {children}
        </section>
    );
}

function normalizeAuditIntoReport(audit) {
    if (!audit?.branding?.enabled) {
        return null;
    }

    return audit.white_label_report || null;
}

export default function BrandedAuditReportView({ report = null, audit = null, exportingPdf = false, onExportPdf = null }) {
    const resolvedReport = report || normalizeAuditIntoReport(audit);

    if (!resolvedReport) {
        return (
            <div className="rounded-[28px] border border-dashed border-white/10 bg-[#141414] p-10 text-center text-[rgba(255,240,232,0.60)]">
                Select a client report profile to preview the generated white-label SEO report.
            </div>
        );
    }

    const { branding, profile, sections, generated_at: generatedAt } = resolvedReport;
    const accent = branding?.primary_color || '#FF5626';
    const secondary = branding?.secondary_color || '#1C1B1B';

    return (
        <div className="space-y-6">
            <section
                className="overflow-hidden rounded-[32px] border border-white/8 p-8 shadow-[0_24px_70px_rgba(0,0,0,0.24)]"
                style={{ background: `linear-gradient(135deg, ${accent}, ${secondary})` }}
            >
                <div className="flex flex-wrap items-start justify-between gap-6">
                    <div className="max-w-3xl">
                        <div className="inline-flex rounded-full bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.24em] text-white/80">
                            Label Report
                        </div>
                        <h2 className="mt-5 text-4xl font-semibold tracking-[-0.05em] text-white">{profile.report_title}</h2>
                        <p className="mt-3 text-base text-white/80">{profile.client_name} | {profile.client_website}</p>
                        <div className="mt-6 grid gap-3 sm:grid-cols-3">
                            <div className="rounded-2xl bg-white/10 px-4 py-4">
                                <div className="text-[11px] uppercase tracking-[0.2em] text-white/60">Reporting Period</div>
                                <div className="mt-2 text-sm font-semibold text-white">{profile.reporting_period_label}</div>
                            </div>
                            <div className="rounded-2xl bg-white/10 px-4 py-4">
                                <div className="text-[11px] uppercase tracking-[0.2em] text-white/60">Generated</div>
                                <div className="mt-2 text-sm font-semibold text-white">{formatDateTime(generatedAt)}</div>
                            </div>
                            <div className="rounded-2xl bg-white/10 px-4 py-4">
                                <div className="text-[11px] uppercase tracking-[0.2em] text-white/60">Brand</div>
                                <div className="mt-2 text-sm font-semibold text-white">{branding.brand_name}</div>
                            </div>
                        </div>
                    </div>

                    <div className="space-y-3">
                        {typeof onExportPdf === 'function' && (
                            <button
                                type="button"
                                onClick={onExportPdf}
                                disabled={exportingPdf}
                                className="w-full rounded-full border border-white/15 bg-white px-4 py-3 text-sm font-semibold text-[#161616] transition hover:bg-white/90 disabled:cursor-not-allowed disabled:opacity-70"
                            >
                                {exportingPdf ? 'Preparing PDF...' : 'Download PDF'}
                            </button>
                        )}
                        <div className="rounded-[24px] border border-white/15 bg-white/8 px-5 py-5 text-white/85">
                            {branding.logo_url ? (
                                <img src={branding.logo_url} alt={`${branding.brand_name} logo`} className="max-h-16 max-w-[200px] object-contain" />
                            ) : (
                                <div className="text-xl font-semibold">{branding.brand_name}</div>
                            )}
                        </div>
                    </div>
                </div>
            </section>

            {sections.executive_summary.available && (
                <SectionCard title="Executive Summary">
                    {sections.executive_summary.custom_summary && (
                        <p className="rounded-2xl border border-white/8 bg-[#111111] px-4 py-4 text-sm leading-7 text-[rgba(255,240,232,0.76)]">
                            {sections.executive_summary.custom_summary}
                        </p>
                    )}
                    {sections.executive_summary.summary_bullets?.length > 0 && (
                        <div className="mt-4 grid gap-3 md:grid-cols-2">
                            {sections.executive_summary.summary_bullets.map((item) => (
                                <div key={item} className="rounded-2xl bg-[#111111] px-4 py-4 text-sm leading-7 text-[rgba(255,240,232,0.72)]">
                                    {item}
                                </div>
                            ))}
                        </div>
                    )}
                </SectionCard>
            )}

            <div className="grid gap-6 xl:grid-cols-2">
                {sections.keyword_overview.available && (
                    <SectionCard title="Keyword Overview">
                        {sections.keyword_overview.tracked_keywords?.length > 0 ? (
                            <div className="space-y-3">
                                {sections.keyword_overview.tracked_keywords.map((item) => (
                                    <div key={item.keyword} className="rounded-2xl bg-[#111111] px-4 py-4">
                                        <div className="flex items-center justify-between gap-4">
                                            <div className="text-sm font-medium text-[#fff7f2]">{item.keyword}</div>
                                            <div className="rounded-full px-3 py-1 text-xs font-semibold" style={{ backgroundColor: `${accent}20`, color: accent }}>
                                                {item.position ? `Position ${item.position}` : 'Not ranked yet'}
                                            </div>
                                        </div>
                                        {item.matched_url && (
                                            <div className="mt-2 text-xs text-[rgba(255,240,232,0.48)]">{item.matched_url}</div>
                                        )}
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="flex flex-wrap gap-2">
                                {sections.keyword_overview.target_keywords.map((item) => (
                                    <span key={item} className="rounded-full border border-white/8 bg-[#111111] px-3 py-2 text-sm text-[rgba(255,240,232,0.72)]">
                                        {item}
                                    </span>
                                ))}
                            </div>
                        )}
                    </SectionCard>
                )}

                {sections.backlink_overview.available && (
                    <SectionCard title="Backlink Overview">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="rounded-2xl bg-[#111111] px-4 py-4">
                                <div className="text-[11px] uppercase tracking-[0.2em] text-[rgba(255,240,232,0.42)]">Total Backlinks</div>
                                <div className="mt-3 text-3xl font-semibold text-[#fff7f2]">{sections.backlink_overview.total_backlinks ?? 'N/A'}</div>
                            </div>
                            <div className="rounded-2xl bg-[#111111] px-4 py-4">
                                <div className="text-[11px] uppercase tracking-[0.2em] text-[rgba(255,240,232,0.42)]">Referring Domains</div>
                                <div className="mt-3 text-3xl font-semibold text-[#fff7f2]">{sections.backlink_overview.referring_domains ?? 'N/A'}</div>
                            </div>
                        </div>

                        {sections.backlink_overview.top_ref_domains?.length > 0 && (
                            <div className="mt-4 space-y-3">
                                {sections.backlink_overview.top_ref_domains.map((item) => (
                                    <div key={item.domain} className="flex items-center justify-between rounded-2xl bg-[#111111] px-4 py-4">
                                        <div>
                                            <div className="text-sm font-medium text-[#fff7f2]">{item.domain}</div>
                                            <div className="mt-1 text-xs text-[rgba(255,240,232,0.44)]">
                                                Risk score: {item.risk_score ?? 'N/A'}
                                            </div>
                                        </div>
                                        <div className="text-sm font-semibold text-[rgba(255,240,232,0.82)]">{item.backlinks_count} links</div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </SectionCard>
                )}
            </div>

            {sections.technical_seo_summary.available && (
                <SectionCard title="Technical SEO Summary">
                    <div className="grid gap-4 md:grid-cols-3">
                        <div className="rounded-2xl bg-[#111111] px-4 py-4">
                            <div className="text-[11px] uppercase tracking-[0.2em] text-[rgba(255,240,232,0.42)]">Health Score</div>
                            <div className="mt-3 text-3xl font-semibold text-[#fff7f2]">{sections.technical_seo_summary.health_score ?? 'N/A'}</div>
                        </div>
                        <div className="rounded-2xl bg-[#111111] px-4 py-4">
                            <div className="text-[11px] uppercase tracking-[0.2em] text-[rgba(255,240,232,0.42)]">Pages Crawled</div>
                            <div className="mt-3 text-3xl font-semibold text-[#fff7f2]">{sections.technical_seo_summary.pages_crawled ?? 'N/A'}</div>
                        </div>
                        <div className="rounded-2xl bg-[#111111] px-4 py-4">
                            <div className="text-[11px] uppercase tracking-[0.2em] text-[rgba(255,240,232,0.42)]">Critical Issues</div>
                            <div className="mt-3 text-3xl font-semibold text-[#fff7f2]">{sections.technical_seo_summary.issue_counts?.critical ?? 0}</div>
                        </div>
                    </div>

                    {sections.technical_seo_summary.top_issues?.length > 0 && (
                        <div className="mt-4 space-y-3">
                            {sections.technical_seo_summary.top_issues.map((issue, index) => (
                                <div key={`${issue.type}-${index}`} className="rounded-2xl bg-[#111111] px-4 py-4">
                                    <div className="flex items-center justify-between gap-4">
                                        <div className="text-sm font-medium text-[#fff7f2]">{issue.type || 'SEO Issue'}</div>
                                        <span className="rounded-full px-3 py-1 text-xs font-semibold capitalize" style={{ backgroundColor: `${accent}1A`, color: accent }}>
                                            {issue.severity}
                                        </span>
                                    </div>
                                    <div className="mt-2 text-sm leading-7 text-[rgba(255,240,232,0.68)]">{issue.message}</div>
                                </div>
                            ))}
                        </div>
                    )}
                </SectionCard>
            )}

            {sections.recommendations.available && (
                <SectionCard title="Recommendations">
                    <div className="grid gap-3">
                        {sections.recommendations.items.map((item) => (
                            <div key={item} className="rounded-2xl bg-[#111111] px-4 py-4 text-sm leading-7 text-[rgba(255,240,232,0.74)]">
                                {item}
                            </div>
                        ))}
                    </div>
                </SectionCard>
            )}

            <SectionCard title="Footer Branding">
                <div className="grid gap-4 lg:grid-cols-[1.2fr,0.8fr]">
                    <div className="rounded-2xl bg-[#111111] px-4 py-4 text-sm leading-7 text-[rgba(255,240,232,0.74)]">
                        {sections.footer_branding.footer_text}
                    </div>
                    <div className="rounded-2xl bg-[#111111] px-4 py-4 text-sm text-[rgba(255,240,232,0.74)]">
                        <div className="font-semibold text-[#fff7f2]">{branding.brand_name}</div>
                        {sections.footer_branding.website && <div className="mt-2">{sections.footer_branding.website}</div>}
                        {sections.footer_branding.support_email && <div className="mt-1">{sections.footer_branding.support_email}</div>}
                        {sections.footer_branding.support_phone && <div className="mt-1">{sections.footer_branding.support_phone}</div>}
                        {sections.footer_branding.company_address && <div className="mt-1 whitespace-pre-line">{sections.footer_branding.company_address}</div>}
                    </div>
                </div>
            </SectionCard>
        </div>
    );
}
