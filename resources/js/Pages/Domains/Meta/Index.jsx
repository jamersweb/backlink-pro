import { Link, router } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import AppLayout from '@/Components/Layout/AppLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';
import ConnectorModal from './Partials/ConnectorModal';
import PagesList from './Partials/PagesList';
import MetaEditor from './Partials/MetaEditor';
import HistoryPanel from './Partials/HistoryPanel';

export default function MetaIndex({ domain, connector, pages, selectedPage, importOptions }) {
    const [showConnectorModal, setShowConnectorModal] = useState(false);
    const [showImportModal, setShowImportModal] = useState(false);

    // Auto-refresh if there are queued changes
    useEffect(() => {
        if (selectedPage?.changes?.some(c => c.status === 'queued')) {
            const interval = setInterval(() => {
                router.reload({ only: ['selectedPage'] });
            }, 6000);
            return () => clearInterval(interval);
        }
    }, [selectedPage]);

    const getStatusBadge = (status) => {
        const colors = {
            connected: 'bg-green-100 text-green-800',
            error: 'bg-red-100 text-red-800',
            disconnected: 'bg-gray-100 text-gray-800',
        };
        return (
            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${colors[status] || colors.disconnected}`}>
                {status}
            </span>
        );
    };

    const handleSaveDraft = (formData) => {
        if (!selectedPage) return;
        router.post(`/domains/${domain.id}/meta/pages/${selectedPage.id}/save`, formData, {
            preserveScroll: true,
        });
    };

    const handlePublish = (formData) => {
        if (!selectedPage) return;
        router.post(`/domains/${domain.id}/meta/pages/${selectedPage.id}/publish`, formData, {
            preserveScroll: true,
        });
    };

    const handleImport = (source) => {
        router.post(`/domains/${domain.id}/meta/pages/import`, {
            source,
            limit: 100,
        }, {
            preserveScroll: true,
        });
        setShowImportModal(false);
    };

    return (
        <AppLayout header="Meta Editor">
            <div className="space-y-6">
                {/* Breadcrumb */}
                <div className="flex items-center gap-2 text-sm text-gray-600">
                    <Link href="/domains" className="hover:text-gray-900">Domains</Link>
                    <span>/</span>
                    <Link href={`/domains/${domain.id}`} className="hover:text-gray-900">{domain.name}</Link>
                    <span>/</span>
                    <span className="text-gray-900">Meta Editor</span>
                </div>

                {/* Connector Status Card */}
                <Card>
                    <div className="p-4 flex justify-between items-center">
                        <div>
                            <h3 className="text-lg font-semibold text-gray-900">Connector</h3>
                            <div className="mt-2 flex items-center gap-3">
                                {connector ? (
                                    <>
                                        {getStatusBadge(connector.status)}
                                        <span className="text-sm text-gray-600 capitalize">{connector.type}</span>
                                        {connector.last_error && (
                                            <span className="text-xs text-red-600">{connector.last_error}</span>
                                        )}
                                    </>
                                ) : (
                                    <span className="text-sm text-gray-500">Not connected</span>
                                )}
                            </div>
                        </div>
                        <div className="flex gap-2">
                            {connector && (
                                <>
                                    <Button variant="outline" onClick={() => router.post(`/domains/${domain.id}/meta/test`)}>
                                        Test
                                    </Button>
                                    <Button variant="outline" onClick={() => router.post(`/domains/${domain.id}/meta/disconnect`)}>
                                        Disconnect
                                    </Button>
                                </>
                            )}
                            <Button variant="primary" onClick={() => setShowConnectorModal(true)}>
                                {connector ? 'Update' : 'Connect'}
                            </Button>
                        </div>
                    </div>
                </Card>

                {/* Main Content - Split View */}
                <div className="grid grid-cols-12 gap-6">
                    {/* Left Panel - Pages List */}
                    <div className="col-span-4">
                        <Card>
                            <div className="p-4">
                                <div className="flex justify-between items-center mb-4">
                                    <h3 className="text-lg font-semibold text-gray-900">Pages</h3>
                                    <div className="relative">
                                        <Button variant="outline" size="sm" onClick={() => setShowImportModal(!showImportModal)}>
                                            Import
                                        </Button>
                                        {showImportModal && (
                                            <div className="absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded-lg shadow-lg z-10">
                                                <div className="p-2">
                                                    {importOptions.auditAvailable && (
                                                        <button
                                                            onClick={() => handleImport('audit')}
                                                            className="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 rounded"
                                                        >
                                                            From Audit
                                                        </button>
                                                    )}
                                                    {importOptions.gscAvailable && (
                                                        <button
                                                            onClick={() => handleImport('gsc')}
                                                            className="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 rounded"
                                                        >
                                                            From GSC
                                                        </button>
                                                    )}
                                                    {importOptions.connectorAvailable && (
                                                        <button
                                                            onClick={() => handleImport('connector')}
                                                            className="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 rounded"
                                                        >
                                                            From Connector
                                                        </button>
                                                    )}
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>
                                <PagesList
                                    pages={pages}
                                    selectedPage={selectedPage}
                                    domain={domain}
                                    onImport={handleImport}
                                />
                            </div>
                        </Card>
                    </div>

                    {/* Right Panel - Editor */}
                    <div className="col-span-8 space-y-6">
                        <MetaEditor
                            page={selectedPage}
                            domain={domain}
                            onSaveDraft={handleSaveDraft}
                            onPublish={handlePublish}
                        />
                        {selectedPage && selectedPage.changes && (
                            <HistoryPanel changes={selectedPage.changes} />
                        )}
                    </div>
                </div>

                {/* Connector Modal */}
                {showConnectorModal && (
                    <ConnectorModal
                        domain={domain}
                        connector={connector}
                        onClose={() => setShowConnectorModal(false)}
                    />
                )}
            </div>
        </AppLayout>
    );
}

