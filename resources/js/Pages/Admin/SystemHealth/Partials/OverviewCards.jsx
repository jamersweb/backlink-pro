import Card from '@/Components/Shared/Card';

export default function OverviewCards({ overview }) {
    return (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Card variant="elevated">
                <div className="p-4">
                    <p className="text-[var(--admin-text-muted)] text-xs font-medium mb-1">Failed Jobs (24h)</p>
                    <p className="text-2xl font-bold text-red-600 dark:text-red-400">
                        {overview.failed_jobs_24h || 0}
                    </p>
                </div>
            </Card>
            <Card variant="elevated">
                <div className="p-4">
                    <p className="text-[var(--admin-text-muted)] text-xs font-medium mb-1">Error Logs (24h)</p>
                    <p className="text-2xl font-bold text-red-600 dark:text-red-400">
                        {overview.error_logs_24h || 0}
                    </p>
                </div>
            </Card>
        </div>
    );
}


