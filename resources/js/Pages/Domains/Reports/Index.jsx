import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Components/Layout/AppLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';
import CreateReportModal from './Partials/CreateReportModal';
import ReportsTable from './Partials/ReportsTable';

export default function ReportsIndex({ domain, reports }) {
    const [showCreateModal, setShowCreateModal] = useState(false);

    const handleCopyLink = (url) => {
        navigator.clipboard.writeText(url);
        alert('Link copied to clipboard!');
    };

    const handleRefresh = (reportId) => {
        router.post(`/domains/${domain.id}/reports/${reportId}/refresh`, {}, {
            preserveScroll: true,
        });
    };

    const handleRevoke = (reportId) => {
        if (confirm('Are you sure you want to revoke this report? It will no longer be accessible.')) {
            router.post(`/domains/${domain.id}/reports/${reportId}/revoke`, {}, {
                preserveScroll: true,
            });
        }
    };

    return (
        <AppLayout header="Reports">
            <div className="space-y-6">
                {/* Breadcrumb */}
                <div className="flex items-center gap-2 text-sm text-gray-600">
                    <Link href="/domains" className="hover:text-gray-900">Domains</Link>
                    <span>/</span>
                    <Link href={`/domains/${domain.id}`} className="hover:text-gray-900">{domain.name}</Link>
                    <span>/</span>
                    <span className="text-gray-900">Reports</span>
                </div>

                {/* Header */}
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Public Reports</h1>
                        <p className="text-sm text-gray-500 mt-1">
                            Create shareable links to show clients their SEO performance
                        </p>
                    </div>
                    <Button variant="primary" onClick={() => setShowCreateModal(true)}>
                        Create Share Link
                    </Button>
                </div>

                {/* Reports Table */}
                <Card>
                    <div className="p-6">
                        <ReportsTable
                            reports={reports}
                            onCopyLink={handleCopyLink}
                            onRefresh={handleRefresh}
                            onRevoke={handleRevoke}
                        />
                    </div>
                </Card>

                {/* Create Modal */}
                {showCreateModal && (
                    <CreateReportModal
                        domain={domain}
                        onClose={() => setShowCreateModal(false)}
                    />
                )}
            </div>
        </AppLayout>
    );
}


