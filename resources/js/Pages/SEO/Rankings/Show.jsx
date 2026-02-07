import { Link } from '@inertiajs/react';
import AppLayout from '@/Components/Layout/AppLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';

export default function RankingsShow({ organization, project, keywords }) {
    const getPositionChange = (current, previous) => {
        if (current === null || previous === null) return null;
        return previous - current; // Positive = moved up, Negative = moved down
    };

    const getChangeBadge = (change) => {
        if (change === null) return <span className="text-gray-400">—</span>;
        if (change > 0) {
            return <span className="text-green-600 font-semibold">↑ {change}</span>;
        } else if (change < 0) {
            return <span className="text-red-600 font-semibold">↓ {Math.abs(change)}</span>;
        } else {
            return <span className="text-gray-600">→</span>;
        }
    };

    return (
        <AppLayout header={`${project.name} - Rankings`}>
            <div className="space-y-6">
                {/* Header */}
                <div className="flex justify-between items-center">
                    <div>
                        <div className="flex items-center gap-2 text-sm text-gray-600 mb-2">
                            <Link href={route('seo.rankings.index', { organization: organization.id })}>
                                Rankings
                            </Link>
                            <span>/</span>
                            <span className="text-gray-900">{project.name}</span>
                        </div>
                        <h1 className="text-2xl font-bold text-gray-900">{project.name}</h1>
                        <p className="text-sm text-gray-500 mt-1">
                            {project.target_domain} • {project.country_code.toUpperCase()} • {project.language_code.toUpperCase()}
                        </p>
                    </div>
                    <Link href={route('seo.rankings.index', { organization: organization.id })}>
                        <Button variant="outline">← Back to Projects</Button>
                    </Link>
                </div>

                {/* Keywords Table */}
                {keywords && keywords.length > 0 ? (
                    <Card>
                        <div className="p-6">
                            <div className="flex justify-between items-center mb-4">
                                <h3 className="text-lg font-semibold text-gray-900">
                                    Keywords ({keywords.length})
                                </h3>
                            </div>
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Keyword</th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Device</th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Current Position</th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Change</th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">URL</th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Checked</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {keywords.map((keyword) => {
                                            const latest = keyword.latest_result;
                                            const previous = keyword.previous_result;
                                            const change = latest && previous 
                                                ? getPositionChange(latest.position, previous.position)
                                                : null;

                                            return (
                                                <tr key={keyword.id} className="hover:bg-gray-50">
                                                    <td className="px-4 py-3 text-sm font-medium text-gray-900">
                                                        {keyword.keyword}
                                                    </td>
                                                    <td className="px-4 py-3 text-sm text-gray-600">
                                                        <span className="capitalize">{keyword.device}</span>
                                                    </td>
                                                    <td className="px-4 py-3 text-sm text-gray-900">
                                                        {latest?.position ? `#${latest.position}` : 'N/A'}
                                                    </td>
                                                    <td className="px-4 py-3 text-sm">
                                                        {getChangeBadge(change)}
                                                    </td>
                                                    <td className="px-4 py-3 text-sm">
                                                        {latest?.url ? (
                                                            <a 
                                                                href={latest.url} 
                                                                target="_blank" 
                                                                rel="noopener noreferrer" 
                                                                className="text-blue-600 hover:underline truncate block max-w-xs"
                                                            >
                                                                {latest.url}
                                                            </a>
                                                        ) : (
                                                            <span className="text-gray-400">—</span>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-3 text-sm text-gray-500">
                                                        {latest?.last_checked 
                                                            ? new Date(latest.last_checked).toLocaleDateString()
                                                            : 'Never'
                                                        }
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </Card>
                ) : (
                    <Card>
                        <div className="text-center py-12">
                            <div className="text-4xl mb-4">🔍</div>
                            <h3 className="text-lg font-medium text-gray-900 mb-2">No Keywords Yet</h3>
                            <p className="text-sm text-gray-500 mb-6">
                                Add keywords to this project to start tracking rankings.
                            </p>
                            <Link href={route('seo.rankings.index', { organization: organization.id })}>
                                <Button variant="primary">Add Keywords</Button>
                            </Link>
                        </div>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
