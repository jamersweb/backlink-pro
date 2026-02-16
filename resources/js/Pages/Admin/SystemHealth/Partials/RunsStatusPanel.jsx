import Card from '@/Components/Shared/Card';

export default function RunsStatusPanel({ runStatus }) {
    return (
        <Card variant="elevated">
            <div className="p-6">
                <h3 className="text-lg font-semibold text-[var(--admin-text)] mb-4">Feature Run Status (Last 24h)</h3>
                <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <div>
                        <p className="text-sm text-[var(--admin-text-muted)] mb-1">Audits</p>
                        <div className="space-y-1">
                            <p className="text-sm text-[var(--admin-text)]">
                                <span className="text-blue-600 dark:text-blue-400 font-semibold">{runStatus.audits?.running || 0}</span> running
                            </p>
                            <p className="text-sm text-[var(--admin-text)]">
                                <span className="text-red-600 dark:text-red-400 font-semibold">{runStatus.audits?.failed || 0}</span> failed
                            </p>
                        </div>
                    </div>
                    <div>
                        <p className="text-sm text-[var(--admin-text-muted)] mb-1">Backlinks</p>
                        <div className="space-y-1">
                            <p className="text-sm text-[var(--admin-text)]">
                                <span className="text-blue-600 dark:text-blue-400 font-semibold">{runStatus.backlinks?.running || 0}</span> running
                            </p>
                            <p className="text-sm text-[var(--admin-text)]">
                                <span className="text-red-600 dark:text-red-400 font-semibold">{runStatus.backlinks?.failed || 0}</span> failed
                            </p>
                        </div>
                    </div>
                    <div>
                        <p className="text-sm text-[var(--admin-text-muted)] mb-1">Meta</p>
                        <div className="space-y-1">
                            <p className="text-sm text-[var(--admin-text)]">
                                <span className="text-amber-600 dark:text-amber-400 font-semibold">{runStatus.meta?.queued || 0}</span> queued
                            </p>
                            <p className="text-sm text-[var(--admin-text)]">
                                <span className="text-red-600 dark:text-red-400 font-semibold">{runStatus.meta?.failed || 0}</span> failed
                            </p>
                        </div>
                    </div>
                    <div>
                        <p className="text-sm text-[var(--admin-text-muted)] mb-1">Google Sync</p>
                        <div className="space-y-1">
                            <p className="text-sm text-[var(--admin-text)]">
                                <span className="text-red-600 dark:text-red-400 font-semibold">{runStatus.google?.failed || 0}</span> failed
                            </p>
                        </div>
                    </div>
                    <div>
                        <p className="text-sm text-[var(--admin-text-muted)] mb-1">Insights</p>
                        <div className="space-y-1">
                            <p className="text-sm text-[var(--admin-text)]">
                                <span className="text-red-600 dark:text-red-400 font-semibold">{runStatus.insights?.failed || 0}</span> failed
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </Card>
    );
}


