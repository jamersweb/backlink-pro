import { Link, router } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import AppLayout from '@/Components/Layout/AppLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';
import ProgressHeader from './Partials/ProgressHeader';
import StepCard from './Partials/StepCard';

export default function SetupShow({ 
    domain, 
    onboarding, 
    steps, 
    quotaBlocked, 
    latestAudit, 
    latestBacklinkRun, 
    latestInsightRun,
    googleIntegration,
    metaConnector,
    latestReport
}) {
    const [isPolling, setIsPolling] = useState(false);

    // Poll for status updates if any step is running
    useEffect(() => {
        const hasRunningStep = 
            (latestAudit && ['queued', 'running'].includes(latestAudit.status)) ||
            (latestBacklinkRun && ['queued', 'running'].includes(latestBacklinkRun.status)) ||
            (latestInsightRun && ['queued', 'running'].includes(latestInsightRun.status));

        if (hasRunningStep) {
            setIsPolling(true);
            const interval = setInterval(() => {
                router.reload({ only: ['onboarding', 'steps', 'latestAudit', 'latestBacklinkRun', 'latestInsightRun'] });
            }, 8000);

            return () => {
                clearInterval(interval);
                setIsPolling(false);
            };
        }
    }, [latestAudit?.status, latestBacklinkRun?.status, latestInsightRun?.status]);

    const handleStartAudit = () => {
        router.post(`/domains/${domain.id}/setup/audit/start`, {
            crawl_limit: 100,
            max_depth: 3,
            include_sitemap: true,
            include_cwv: false,
        });
    };

    const handleConnectGoogle = () => {
        window.location.href = `/domains/${domain.id}/setup/google/connect`;
    };

    const handleStartBacklinks = () => {
        router.post(`/domains/${domain.id}/setup/backlinks/start`, {
            limit_backlinks: 1000,
            limit_ref_domains: 500,
            limit_anchors: 200,
        });
    };

    const handleRunInsights = () => {
        router.post(`/domains/${domain.id}/setup/insights/run`);
    };

    const handleComplete = () => {
        if (confirm('Mark setup as complete? You can always return to finish remaining steps later.')) {
            router.post(`/domains/${domain.id}/setup/complete`);
        }
    };

    const stepDefinitions = [
        {
            key: 'domain_added',
            title: 'Domain Added',
            description: 'Your domain has been added to BacklinkPro',
            status: steps.domain_added?.done ? 'done' : 'pending',
            action: null,
            link: null,
        },
        {
            key: 'audit',
            title: 'Run Website Analyzer',
            description: 'Crawl your site and identify SEO issues',
            status: steps.audit_completed?.done ? 'done' : 
                   steps.audit_started?.done ? 'running' : 'pending',
            action: handleStartAudit,
            actionLabel: steps.audit_started?.done ? 'View Results' : 'Start Audit',
            link: `/domains/${domain.id}/audits`,
            quotaBlocked: quotaBlocked.audits,
            running: latestAudit && ['queued', 'running'].includes(latestAudit.status),
        },
        {
            key: 'google',
            title: 'Connect Google (GSC + GA4)',
            description: 'Link Search Console and Analytics for traffic insights',
            status: steps.google_selected?.done ? 'done' : 
                   steps.google_connected?.done ? 'in_progress' : 'pending',
            action: handleConnectGoogle,
            actionLabel: steps.google_connected?.done ? 'Select Properties' : 'Connect Google',
            link: `/domains/${domain.id}/integrations/google`,
        },
        {
            key: 'backlinks',
            title: 'Fetch Backlinks',
            description: 'Get your first backlink snapshot',
            status: steps.backlinks_completed?.done ? 'done' : 
                   steps.backlinks_started?.done ? 'running' : 'pending',
            action: handleStartBacklinks,
            actionLabel: steps.backlinks_started?.done ? 'View Results' : 'Fetch Backlinks',
            link: `/domains/${domain.id}/backlinks`,
            quotaBlocked: quotaBlocked.backlinks,
            running: latestBacklinkRun && ['queued', 'running'].includes(latestBacklinkRun.status),
        },
        {
            key: 'meta',
            title: 'Setup Meta Connector',
            description: 'Connect WordPress, Shopify, or add JS snippet for meta editing',
            status: steps.meta_connector?.done ? 'done' : 'pending',
            action: null,
            actionLabel: 'Setup',
            link: `/domains/${domain.id}/meta`,
        },
        {
            key: 'insights',
            title: 'Generate Insights',
            description: 'Create your first insights and action plan',
            status: steps.insights_generated?.done ? 'done' : 
                   latestInsightRun && ['queued', 'running'].includes(latestInsightRun.status) ? 'running' : 'pending',
            action: handleRunInsights,
            actionLabel: latestInsightRun && latestInsightRun.status === 'running' ? 'Running...' : 'Generate Insights',
            link: `/domains/${domain.id}/insights`,
            quotaBlocked: quotaBlocked.insights,
            running: latestInsightRun && ['queued', 'running'].includes(latestInsightRun.status),
        },
        {
            key: 'report',
            title: 'Create Public Report (Optional)',
            description: 'Generate a shareable report link for clients',
            status: steps.report_created?.done ? 'done' : 'pending',
            action: null,
            actionLabel: 'Create Report',
            link: `/domains/${domain.id}/reports`,
            optional: true,
        },
    ];

    const completedCount = stepDefinitions.filter(s => s.status === 'done').length;
    const totalSteps = stepDefinitions.length;

    return (
        <AppLayout header="Domain Setup">
            <div className="space-y-6">
                {/* Breadcrumb */}
                <div className="flex items-center gap-2 text-sm text-gray-600">
                    <Link href="/domains" className="hover:text-gray-900">Domains</Link>
                    <span>/</span>
                    <Link href={`/domains/${domain.id}`} className="hover:text-gray-900">{domain.name}</Link>
                    <span>/</span>
                    <span className="text-gray-900">Setup</span>
                </div>

                {/* Progress Header */}
                <ProgressHeader 
                    domain={domain}
                    completedCount={completedCount}
                    totalSteps={totalSteps}
                    onComplete={handleComplete}
                />

                {/* Steps */}
                <div className="space-y-4">
                    {stepDefinitions.map((step) => (
                        <StepCard
                            key={step.key}
                            step={step}
                            domain={domain}
                        />
                    ))}
                </div>

                {/* Skip Option */}
                <Card>
                    <div className="p-4 text-center">
                        <p className="text-sm text-gray-600 mb-3">
                            You can complete these steps later from the domain dashboard.
                        </p>
                        <Link href={`/domains/${domain.id}`}>
                            <Button variant="outline">
                                Skip Setup & Go to Domain
                            </Button>
                        </Link>
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}


