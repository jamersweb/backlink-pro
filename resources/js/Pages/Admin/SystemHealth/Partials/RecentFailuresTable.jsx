import { router } from '@inertiajs/react';
import Button from '@/Components/Shared/Button';

export default function RecentFailuresTable({ failures }) {
    const getFeatureBadge = (feature) => {
        const colors = {
            audits: 'bg-blue-100 text-blue-800',
            backlinks: 'bg-purple-100 text-purple-800',
            meta: 'bg-green-100 text-green-800',
            google: 'bg-yellow-100 text-yellow-800',
            insights: 'bg-indigo-100 text-indigo-800',
        };
        return (
            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${colors[feature] || 'bg-gray-100 text-gray-800'}`}>
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
        return <p className="text-sm text-gray-500 text-center py-4">No failures in the last 24 hours</p>;
    }

    return (
        <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                    <tr>
                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Feature</th>
                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Run Ref</th>
                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Domain</th>
                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Error</th>
                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                    {failures.map((failure) => (
                        <tr key={failure.id}>
                            <td className="px-4 py-3 text-sm text-gray-500">
                                {new Date(failure.failed_at).toLocaleString()}
                            </td>
                            <td className="px-4 py-3 text-sm">
                                {getFeatureBadge(failure.feature)}
                            </td>
                            <td className="px-4 py-3 text-sm text-gray-900">
                                {failure.run_ref || 'N/A'}
                            </td>
                            <td className="px-4 py-3 text-sm text-gray-500">
                                {failure.domain?.host || 'N/A'}
                            </td>
                            <td className="px-4 py-3 text-sm text-gray-700 max-w-md truncate">
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


