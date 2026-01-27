import Card from '@/Components/Shared/Card';

export default function RunsStatusPanel({ runStatus }) {
    return (
        <Card>
            <div className="p-6">
                <h3 className="text-lg font-semibold text-gray-900 mb-4">Feature Run Status (Last 24h)</h3>
                <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <div>
                        <p className="text-sm text-gray-600 mb-1">Audits</p>
                        <div className="space-y-1">
                            <p className="text-sm">
                                <span className="text-blue-600 font-semibold">{runStatus.audits?.running || 0}</span> running
                            </p>
                            <p className="text-sm">
                                <span className="text-red-600 font-semibold">{runStatus.audits?.failed || 0}</span> failed
                            </p>
                        </div>
                    </div>
                    <div>
                        <p className="text-sm text-gray-600 mb-1">Backlinks</p>
                        <div className="space-y-1">
                            <p className="text-sm">
                                <span className="text-blue-600 font-semibold">{runStatus.backlinks?.running || 0}</span> running
                            </p>
                            <p className="text-sm">
                                <span className="text-red-600 font-semibold">{runStatus.backlinks?.failed || 0}</span> failed
                            </p>
                        </div>
                    </div>
                    <div>
                        <p className="text-sm text-gray-600 mb-1">Meta</p>
                        <div className="space-y-1">
                            <p className="text-sm">
                                <span className="text-yellow-600 font-semibold">{runStatus.meta?.queued || 0}</span> queued
                            </p>
                            <p className="text-sm">
                                <span className="text-red-600 font-semibold">{runStatus.meta?.failed || 0}</span> failed
                            </p>
                        </div>
                    </div>
                    <div>
                        <p className="text-sm text-gray-600 mb-1">Google Sync</p>
                        <div className="space-y-1">
                            <p className="text-sm">
                                <span className="text-red-600 font-semibold">{runStatus.google?.failed || 0}</span> failed
                            </p>
                        </div>
                    </div>
                    <div>
                        <p className="text-sm text-gray-600 mb-1">Insights</p>
                        <div className="space-y-1">
                            <p className="text-sm">
                                <span className="text-red-600 font-semibold">{runStatus.insights?.failed || 0}</span> failed
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </Card>
    );
}


