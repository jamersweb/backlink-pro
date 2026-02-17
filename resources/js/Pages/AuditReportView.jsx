import AppLayout from '../Components/Layout/AppLayout';
import Card from '../Components/Shared/Card';
import { useState } from 'react';
import { router } from '@inertiajs/react';

function useExportPdf(auditId) {
    const [exporting, setExporting] = useState(false);
    const exportPdf = async () => {
        if (!auditId) return;
        setExporting(true);
        try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const res = await fetch(`/audit-report/${auditId}/export-pdf`, {
                method: 'POST',
                headers: { Accept: 'application/pdf', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) throw new Error('Export failed');
            const blob = await res.blob();
            const isPdf = res.headers.get('Content-Type')?.includes('pdf');
            if (isPdf) {
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `audit-report-${auditId}-${new Date().toISOString().slice(0, 10)}.pdf`;
                a.click();
                URL.revokeObjectURL(url);
            } else {
                const text = await blob.text();
                const w = window.open('', '_blank');
                w.document.write(text);
                w.document.close();
            }
        } catch (e) {
            console.error(e);
            window.print();
        } finally {
            setExporting(false);
        }
    };
    return [exporting, exportPdf];
}

export default function AuditReportView({ audit }) {
    const [activeTab, setActiveTab] = useState('overview');
    const [exportingPdf, exportPdf] = useExportPdf(audit?.id);

    const tabs = [
        { id: 'overview', label: 'Overview', icon: 'bi-speedometer2' },
        { id: 'onpage', label: 'On-Page SEO', icon: 'bi-file-text' },
        { id: 'technical', label: 'Technical', icon: 'bi-gear' },
        { id: 'performance', label: 'Performance', icon: 'bi-lightning' },
        { id: 'integrations', label: 'Integrations', icon: 'bi-plug' },
    ];

    // Calculate grade color
    const getScoreColor = (score) => {
        if (score >= 90) return { bg: 'bg-[#12B76A]/10', border: 'border-[#12B76A]/30', text: 'text-[#12B76A]' };
        if (score >= 70) return { bg: 'bg-[#F79009]/10', border: 'border-[#F79009]/30', text: 'text-[#F79009]' };
        return { bg: 'bg-[#F04438]/10', border: 'border-[#F04438]/30', text: 'text-[#F04438]' };
    };

    const getGrade = (score) => {
        if (score >= 90) return 'A';
        if (score >= 80) return 'B';
        if (score >= 70) return 'C';
        if (score >= 60) return 'D';
        return 'F';
    };

    const overallScore = audit.overall_score || 0;
    const scoreColors = getScoreColor(overallScore);
    const grade = getGrade(overallScore);

    // Parse category scores
    const categoryScores = audit.category_scores || {};
    const performanceData = audit.performance_summary || {};
    const issues = audit.issues || [];

    // Group issues by severity
    const criticalIssues = issues.filter(i => i.severity === 'critical');
    const warningIssues = issues.filter(i => i.severity === 'warning');
    const infoIssues = issues.filter(i => i.severity === 'info');

    return (
        <AppLayout header="Audit Report">
            <div className="space-y-6 max-w-7xl mx-auto">
                {/* Failed audit error */}
                {audit.status === 'failed' && audit.error && (
                    <div className="rounded-2xl bg-[#F04438]/10 border border-[#F04438]/30 p-4 flex items-start gap-3">
                        <i className="bi bi-x-circle-fill text-[#F04438] text-xl flex-shrink-0"></i>
                        <div>
                            <p className="font-medium text-[#F04438]">Audit failed</p>
                            <p className="text-sm text-[var(--admin-text-muted)] mt-1">{audit.error}</p>
                            <p className="text-xs text-[var(--admin-text-dim)] mt-2">You can run a new audit from the form page.</p>
                        </div>
                    </div>
                )}

                {/* Header Card with Overall Score */}
                <div className="relative overflow-hidden rounded-2xl bg-[var(--admin-surface)] border border-[var(--admin-border)] p-8 shadow-[var(--admin-shadow-md)]">
                    <div className="absolute top-0 right-0 w-64 h-64 bg-[#2F6BFF]/10 rounded-full blur-3xl -mr-32 -mt-32 dark:opacity-100 opacity-0"></div>
                    
                    <div className="relative grid grid-cols-1 lg:grid-cols-3 gap-8">
                        {/* Left: Site Info */}
                        <div className="lg:col-span-2">
                            <div className="flex items-start gap-4">
                                <div className="w-16 h-16 rounded-2xl bg-gradient-to-br from-[#2F6BFF] to-[#2457D6] flex items-center justify-center flex-shrink-0 shadow-lg shadow-[#2F6BFF]/30">
                                    <i className="bi bi-globe text-2xl text-white"></i>
                                </div>
                                <div className="flex-1">
                                    <h2 className="text-2xl font-bold text-[var(--admin-text)] mb-2">{audit.url}</h2>
                                    <div className="flex flex-wrap items-center gap-4 text-sm text-[var(--admin-text-muted)]">
                                        <span className="flex items-center gap-1.5">
                                            <i className="bi bi-calendar3"></i>
                                            {new Date(audit.created_at).toLocaleDateString('en-US', { 
                                                year: 'numeric', 
                                                month: 'long', 
                                                day: 'numeric' 
                                            })}
                                        </span>
                                        <span className="flex items-center gap-1.5">
                                            <i className="bi bi-clock"></i>
                                            {audit.finished_at 
                                                ? `Completed in ${Math.round((new Date(audit.finished_at) - new Date(audit.started_at)) / 1000)}s`
                                                : 'Processing...'
                                            }
                                        </span>
                                        <span className={`px-3 py-1 rounded-full text-xs font-medium ${
                                            audit.status === 'completed' 
                                                ? 'bg-[#12B76A]/10 border border-[#12B76A]/30 text-[#12B76A]'
                                                : 'bg-[#F79009]/10 border border-[#F79009]/30 text-[#F79009]'
                                        }`}>
                                            {audit.status.charAt(0).toUpperCase() + audit.status.slice(1)}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Right: Overall Score Donut */}
                        <div className="flex justify-center lg:justify-end">
                            <div className={`relative w-40 h-40 rounded-full ${scoreColors.bg} border-4 ${scoreColors.border} flex items-center justify-center`}>
                                <div className="text-center">
                                    <div className={`text-4xl font-bold ${scoreColors.text}`}>{overallScore}</div>
                                    <div className="text-xs text-[var(--admin-text-dim)] mt-1">Overall Score</div>
                                    <div className={`text-2xl font-bold ${scoreColors.text} mt-1`}>{grade}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* KPI Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    {[
                        { label: 'Performance', score: categoryScores.performance || 0, icon: 'bi-lightning-charge', color: '#F79009' },
                        { label: 'SEO Health', score: categoryScores.onpage || 0, icon: 'bi-heart-pulse', color: '#12B76A' },
                        { label: 'Technical', score: categoryScores.technical || 0, icon: 'bi-gear', color: '#2F6BFF' },
                        { label: 'Issues Found', score: issues.length, icon: 'bi-exclamation-triangle', color: '#F04438', isCount: true },
                    ].map((kpi, index) => {
                        const colors = kpi.isCount ? { bg: 'bg-[#F04438]/10', text: 'text-[#F04438]' } : getScoreColor(kpi.score);
                        return (
                            <Card key={index} variant="elevated" className="hover:shadow-[var(--admin-shadow-lg)] transition-shadow duration-200">
                                <div className="flex items-start justify-between">
                                    <div>
                                        <p className="text-sm text-[var(--admin-text-muted)] mb-2">{kpi.label}</p>
                                        <p className={`text-3xl font-bold ${colors.text}`}>
                                            {kpi.isCount ? kpi.score : kpi.score}
                                            {!kpi.isCount && <span className="text-lg">/100</span>}
                                        </p>
                                    </div>
                                    <div className={`w-12 h-12 rounded-xl ${colors.bg} flex items-center justify-center`}>
                                        <i className={`bi ${kpi.icon} text-xl`} style={{ color: kpi.color }}></i>
                                    </div>
                                </div>
                            </Card>
                        );
                    })}
                </div>

                {/* Tabs */}
                <Card variant="elevated">
                    <div className="border-b border-[var(--admin-border)]">
                        <div className="flex gap-2 overflow-x-auto pb-2 scrollbar-hide px-6 pt-6">
                            {tabs.map((tab) => (
                                <button
                                    key={tab.id}
                                    onClick={() => setActiveTab(tab.id)}
                                    className={`px-4 py-2.5 rounded-lg font-medium text-sm whitespace-nowrap transition-all duration-150 flex items-center gap-2 ${
                                        activeTab === tab.id
                                            ? 'bg-gradient-to-r from-[#2F6BFF] to-[#2457D6] text-white shadow-md shadow-[#2F6BFF]/20'
                                            : 'bg-[var(--admin-surface-2)] text-[var(--admin-text-muted)] hover:text-[var(--admin-text)] hover:bg-[var(--admin-hover-bg)]'
                                    }`}
                                >
                                    <i className={`bi ${tab.icon}`}></i>
                                    {tab.label}
                                </button>
                            ))}
                        </div>
                    </div>

                    <div className="p-6">
                        {/* Overview Tab */}
                        {activeTab === 'overview' && (
                            <div className="space-y-6">
                                <div>
                                    <h3 className="text-lg font-semibold text-[var(--admin-text)] mb-4">Issues Summary</h3>
                                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div className="p-4 bg-[#F04438]/10 border border-[#F04438]/30 rounded-xl">
                                            <div className="flex items-center gap-3">
                                                <i className="bi bi-x-circle-fill text-2xl text-[#F04438]"></i>
                                                <div>
                                                    <p className="text-2xl font-bold text-[#F04438]">{criticalIssues.length}</p>
                                                    <p className="text-sm text-[var(--admin-text-dim)]">Critical Issues</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div className="p-4 bg-[#F79009]/10 border border-[#F79009]/30 rounded-xl">
                                            <div className="flex items-center gap-3">
                                                <i className="bi bi-exclamation-triangle-fill text-2xl text-[#F79009]"></i>
                                                <div>
                                                    <p className="text-2xl font-bold text-[#F79009]">{warningIssues.length}</p>
                                                    <p className="text-sm text-[var(--admin-text-dim)]">Warnings</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div className="p-4 bg-[#2F6BFF]/10 border border-[#2F6BFF]/30 rounded-xl">
                                            <div className="flex items-center gap-3">
                                                <i className="bi bi-info-circle-fill text-2xl text-[#5B8AFF]"></i>
                                                <div>
                                                    <p className="text-2xl font-bold text-[#5B8AFF]">{infoIssues.length}</p>
                                                    <p className="text-sm text-[var(--admin-text-dim)]">Opportunities</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <h3 className="text-lg font-semibold text-[var(--admin-text)] mb-4">Top Issues</h3>
                                    <div className="space-y-3">
                                        {issues.slice(0, 5).map((issue, index) => (
                                            <div key={index} className="p-4 bg-[var(--admin-surface-2)] border border-[var(--admin-border)] rounded-xl hover:border-[var(--admin-hover-border)] transition-colors">
                                                <div className="flex items-start gap-3">
                                                    <span className={`mt-0.5 px-2 py-1 rounded-md text-xs font-medium ${
                                                        issue.severity === 'critical' 
                                                            ? 'bg-[#F04438]/10 text-[#F04438]'
                                                            : issue.severity === 'warning'
                                                                ? 'bg-[#F79009]/10 text-[#F79009]'
                                                                : 'bg-[#2F6BFF]/10 text-[#5B8AFF]'
                                                    }`}>
                                                        {issue.severity.toUpperCase()}
                                                    </span>
                                                    <div className="flex-1">
                                                        <h4 className="font-semibold text-[var(--admin-text)] mb-1">{issue.title}</h4>
                                                        <p className="text-sm text-[var(--admin-text-dim)]">{issue.description}</p>
                                                        {issue.affected_count && (
                                                            <p className="text-xs text-[var(--admin-text-muted)] mt-2">
                                                                Affects {issue.affected_count} {issue.affected_count === 1 ? 'page' : 'pages'}
                                                            </p>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                        {issues.length === 0 && (
                                            <div className="text-center py-8 text-[var(--admin-text-dim)]">
                                                <i className="bi bi-check-circle text-4xl text-[#12B76A] mb-3"></i>
                                                <p>No issues found! Your site looks great.</p>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* On-Page SEO Tab */}
                        {activeTab === 'onpage' && (
                            <div className="space-y-6">
                                <h3 className="text-lg font-semibold text-[var(--admin-text)]">On-Page SEO Analysis</h3>
                                <div className="text-[var(--admin-text-dim)]">
                                    <p>Detailed on-page SEO metrics will be displayed here.</p>
                                    <p className="mt-2 text-sm">Including: meta tags, headings structure, content analysis, keyword usage, and internal linking.</p>
                                </div>
                            </div>
                        )}

                        {/* Technical Tab */}
                        {activeTab === 'technical' && (
                            <div className="space-y-6">
                                <h3 className="text-lg font-semibold text-[var(--admin-text)]">Technical SEO</h3>
                                <div className="text-[var(--admin-text-dim)]">
                                    <p>Technical SEO checks and recommendations.</p>
                                    <p className="mt-2 text-sm">Including: robots.txt, sitemap.xml, SSL, redirects, canonical tags, and crawlability.</p>
                                </div>
                            </div>
                        )}

                        {/* Performance Tab */}
                        {activeTab === 'performance' && (
                            <div className="space-y-6">
                                <h3 className="text-lg font-semibold text-[var(--admin-text)]">Performance Metrics</h3>
                                {performanceData.psi ? (
                                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                        {Object.entries(performanceData.psi).map(([key, value]) => (
                                            <div key={key} className="p-4 bg-[var(--admin-surface-2)] border border-[var(--admin-border)] rounded-xl">
                                                <p className="text-sm text-[var(--admin-text-muted)] mb-2 capitalize">{key.replace('_', ' ')}</p>
                                                <p className="text-2xl font-bold text-[var(--admin-text)]">{value}</p>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="text-[var(--admin-text-dim)]">
                                        <p>Performance data from PageSpeed Insights will be displayed here.</p>
                                    </div>
                                )}
                            </div>
                        )}

                        {/* Integrations Tab */}
                        {activeTab === 'integrations' && (
                            <div className="space-y-6">
                                <h3 className="text-lg font-semibold text-[var(--admin-text)]">Google Integrations Data</h3>
                                <div className="text-[var(--admin-text-dim)]">
                                    <p>Data from Google Analytics and Search Console will be displayed here.</p>
                                    <p className="mt-2 text-sm">Including: traffic metrics, search queries, click-through rates, and position data.</p>
                                </div>
                            </div>
                        )}
                    </div>
                </Card>

                {/* Actions */}
                <div className="flex gap-3">
                    <button
                        onClick={() => router.visit('/audit-report')}
                        className="px-6 py-2.5 bg-[var(--admin-surface)] hover:bg-[var(--admin-surface-2)] border border-[var(--admin-border)] rounded-lg font-medium text-[var(--admin-text)] transition-colors flex items-center gap-2"
                    >
                        <i className="bi bi-arrow-left"></i>
                        Back to Audits
                    </button>
                    <button
                        onClick={() => exportPdf()}
                        disabled={exportingPdf}
                        className="px-6 py-2.5 bg-gradient-to-r from-[#2F6BFF] to-[#2457D6] hover:from-[#2457D6] hover:to-[#1E4BBD] text-white rounded-lg font-medium transition-all shadow-lg shadow-[#2F6BFF]/20 flex items-center gap-2 disabled:opacity-70"
                    >
                        {exportingPdf ? <i className="bi bi-arrow-repeat animate-spin"></i> : <i className="bi bi-download"></i>}
                        {exportingPdf ? 'Generating PDF…' : 'Export PDF'}
                    </button>
                </div>
            </div>
        </AppLayout>
    );
}
