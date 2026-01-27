import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Components/Layout/AppLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';

export default function AutomationShow({ domain, campaign, stats }) {
    const [showImportModal, setShowImportModal] = useState(false);

    const handleStart = () => {
        router.post(`/domains/${domain.id}/automation/${campaign.id}/start`);
    };

    const handlePause = () => {
        router.post(`/domains/${domain.id}/automation/${campaign.id}/pause`);
    };

    const handleResume = () => {
        router.post(`/domains/${domain.id}/automation/${campaign.id}/resume`);
    };

    const handleStop = () => {
        if (confirm('Stop this campaign?')) {
            router.post(`/domains/${domain.id}/automation/${campaign.id}/stop`);
        }
    };

    return (
        <AppLayout header={`Campaign: ${campaign.name}`}>
            <div className="space-y-6">
                {/* Stats */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <Card>
                        <div className="p-4">
                            <p className="text-sm text-gray-500">Total Targets</p>
                            <p className="text-2xl font-bold text-gray-900">{stats.total_targets}</p>
                        </div>
                    </Card>
                    <Card>
                        <div className="p-4">
                            <p className="text-sm text-gray-500">Success</p>
                            <p className="text-2xl font-bold text-green-600">{stats.success}</p>
                        </div>
                    </Card>
                    <Card>
                        <div className="p-4">
                            <p className="text-sm text-gray-500">Failed</p>
                            <p className="text-2xl font-bold text-red-600">{stats.failed}</p>
                        </div>
                    </Card>
                    <Card>
                        <div className="p-4">
                            <p className="text-sm text-gray-500">Pending</p>
                            <p className="text-2xl font-bold text-yellow-600">{stats.pending}</p>
                        </div>
                    </Card>
                </div>

                {/* Actions */}
                <Card>
                    <div className="p-6 flex justify-between items-center">
                        <div>
                            <span className={`px-3 py-1 text-sm font-semibold rounded-full ${
                                campaign.status === 'running' ? 'bg-green-100 text-green-800' :
                                campaign.status === 'completed' ? 'bg-blue-100 text-blue-800' :
                                campaign.status === 'failed' ? 'bg-red-100 text-red-800' :
                                campaign.status === 'paused' ? 'bg-yellow-100 text-yellow-800' :
                                'bg-gray-100 text-gray-800'
                            }`}>
                                {campaign.status}
                            </span>
                        </div>
                        <div className="flex gap-3">
                            {campaign.status === 'draft' && (
                                <Button variant="primary" onClick={handleStart}>Start Campaign</Button>
                            )}
                            {campaign.status === 'running' && (
                                <Button variant="outline" onClick={handlePause}>Pause</Button>
                            )}
                            {campaign.status === 'paused' && (
                                <Button variant="primary" onClick={handleResume}>Resume</Button>
                            )}
                            {['running', 'paused'].includes(campaign.status) && (
                                <Button variant="outline" onClick={handleStop}>Stop</Button>
                            )}
                            <Button variant="outline" onClick={() => setShowImportModal(true)}>
                                Import Targets
                            </Button>
                        </div>
                    </div>
                </Card>

                {/* Targets & Jobs */}
                <Card>
                    <div className="p-6">
                        <h3 className="text-lg font-semibold mb-4">Targets & Jobs</h3>
                        <p className="text-sm text-gray-500">Targets and jobs will be displayed here</p>
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}


