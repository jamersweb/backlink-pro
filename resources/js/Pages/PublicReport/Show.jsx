import { Head } from '@inertiajs/react';
import PublicLayout from '../../Components/Layout/PublicLayout';
import Card from '../../Components/Shared/Card';
import SummaryCards from './Partials/SummaryCards';
import AnalyzerSection from './Partials/AnalyzerSection';
import GoogleSection from './Partials/GoogleSection';
import BacklinksSection from './Partials/BacklinksSection';
import MetaSection from './Partials/MetaSection';
import InsightsSection from './Partials/InsightsSection';
import ContentSection from './Partials/ContentSection';

export default function PublicReportShow({ report, snapshot, branding }) {
    return (
        <PublicLayout branding={branding}>
            <Head title={report.title || 'SEO Report'} />
            
            <div className="space-y-8">
                {/* Header */}
                <div className="text-center">
                    <h1 className="text-3xl font-bold text-gray-900 mb-2">
                        {report.title || 'SEO Performance Report'}
                    </h1>
                    <p className="text-gray-600">
                        {snapshot.domain?.name || snapshot.domain?.host}
                    </p>
                    <p className="text-sm text-gray-500 mt-2">
                        Generated: {new Date(snapshot.generated_at).toLocaleDateString()}
                    </p>
                </div>

                {/* Summary Cards */}
                <SummaryCards snapshot={snapshot} />

                {/* Analyzer Section */}
                {snapshot.analyzer && (
                    <AnalyzerSection data={snapshot.analyzer} />
                )}

                {/* Google Section */}
                {snapshot.google && (
                    <GoogleSection data={snapshot.google} />
                )}

                {/* Backlinks Section */}
                {snapshot.backlinks && (
                    <BacklinksSection data={snapshot.backlinks} />
                )}

                {/* Meta Section */}
                {snapshot.meta && (
                    <MetaSection data={snapshot.meta} />
                )}

                {/* Insights Section */}
                {snapshot.insights && (
                    <InsightsSection data={snapshot.insights} />
                )}

                {/* Content Section */}
                {snapshot.content && (
                    <ContentSection data={snapshot.content} />
                )}
            </div>
        </PublicLayout>
    );
}


