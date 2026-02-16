import { router } from '@inertiajs/react';
import Button from '@/Components/Shared/Button';

export default function RecentFailuresTable({ failures }) {
    const getFeatureBadge = (feature) => {
        const classes = {
            audits: 'admin-badge admin-badge-info',
            backlinks: 'admin-badge admin-badge-primary',
            meta: 'admin-badge admin-badge-success',
            google: 'admin-badge admin-badge-warning',
            insights: 'admin-badge admin-badge-primary',
        };
        return (
            <span className={classes[feature] || 'admin-badge admin-badge-neutral'}>
                {feature}
            </span>
        );
    };

    const handleRetry = (failure) => {
        // Determine retry route based on feature and run_ref
        if (!failure.run_ref) {
            alert('Cannot retry: no run reference');
            return;
        }

        const [feature, id] = failure.run_ref.split(':');
        const routes = {
            audits: `/admin/runs/audit/${id}/retry`,
            backlinks: `/admin/runs/backlinks/${id}/retry`,
            meta: `/admin/runs/meta/${id}/retry`,
            insights: `/admin/runs/insights/${id}/retry`,
        };

        const route = routes[feature];
        if (route) {
            router.post(route, {}, {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload({ only: ['recentFailures'] });
                },
            });
        } else {
            alert('Retry not available for this feature');
        }
    };

    if (!failures || failures.length === 0) {
        return <p className="text-sm text-[var(--admin-text-muted)] text-center py-4">No failures in the last 24 hours</p>;
    }

    return (
        <div className="overflow-x-auto">
            <table className="admin-table min-w-full">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Feature</th>
                        <th>Run Ref</th>
                        <th>Domain</th>
                        <th>Error</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {failures.map((failure) => (
                        <tr key={failure.id}>
                            <td className="text-sm text-[var(--admin-text-muted)]">
                                {new Date(failure.failed_at).toLocaleString()}
                            </td>
                            <td className="text-sm">
                                {getFeatureBadge(failure.feature)}
                            </td>
                            <td className="text-sm text-[var(--admin-text)]">
                                {failure.run_ref || 'N/A'}
                            </td>
                            <td className="text-sm text-[var(--admin-text-muted)]">
                                {failure.domain?.host || 'N/A'}
                            </td>
                            <td className="text-sm text-[var(--admin-text)] max-w-md truncate">
                                {failure.exception_message || 'N/A'}
                            </td>
                            <td className="px-4 py-3 text-sm">
                                {failure.run_ref && (
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => handleRetry(failure)}
                                    >
                                        Retry
                                    </Button>
                                )}
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}


