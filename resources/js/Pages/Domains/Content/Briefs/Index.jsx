import { router } from '@inertiajs/react';
import AppLayout from '@/Components/Layout/AppLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';

export default function BriefsIndex({ domain, briefs }) {
    const handleStatusChange = (briefId, status) => {
        router.post(`/domains/${domain.id}/content/briefs/${briefId}`, { status });
    };

    const getStatusBadge = (status) => {
        const colors = {
            draft: 'bg-gray-100 text-gray-800',
            writing: 'bg-yellow-100 text-yellow-800',
            published: 'bg-green-100 text-green-800',
            archived: 'bg-red-100 text-red-800',
        };
        return colors[status] || colors.draft;
    };

    return (
        <AppLayout header="Content Briefs">
            <div className="space-y-6">
                <div className="flex justify-between items-center">
                    <h2 className="text-2xl font-bold">Content Briefs</h2>
                    <Button variant="primary" onClick={() => router.visit(`/domains/${domain.id}/content/briefs/create`)}>
                        Create Brief
                    </Button>
                </div>

                <Card>
                    <div className="p-6">
                        {briefs.data && briefs.data.length > 0 ? (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Keyword</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Target</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {briefs.data.map((brief) => (
                                            <tr key={brief.id}>
                                                <td className="px-6 py-4">
                                                    <a
                                                        href={`/domains/${domain.id}/content/briefs/${brief.id}`}
                                                        className="text-sm font-medium text-blue-600 hover:text-blue-800"
                                                    >
                                                        {brief.title}
                                                    </a>
                                                </td>
                                                <td className="px-6 py-4 text-sm text-gray-900">{brief.primary_keyword}</td>
                                                <td className="px-6 py-4 text-sm">
                                                    {brief.target_type === 'existing_page' ? (
                                                        <a href={brief.target_url} target="_blank" rel="noopener" className="text-blue-600 hover:underline truncate max-w-xs block">
                                                            {brief.target_url}
                                                        </a>
                                                    ) : (
                                                        <span className="text-gray-500">New: /{brief.suggested_slug}</span>
                                                    )}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <span className={`px-2 py-1 text-xs font-semibold rounded-full ${getStatusBadge(brief.status)}`}>
                                                        {brief.status}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 text-sm text-gray-500">
                                                    {new Date(brief.created_at).toLocaleDateString()}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm">
                                                    <div className="flex gap-2">
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => router.visit(`/domains/${domain.id}/content/briefs/${brief.id}/export`)}
                                                        >
                                                            Export
                                                        </Button>
                                                        {brief.status === 'draft' && (
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() => handleStatusChange(brief.id, 'writing')}
                                                            >
                                                                Mark Writing
                                                            </Button>
                                                        )}
                                                        {brief.status === 'writing' && (
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() => handleStatusChange(brief.id, 'published')}
                                                            >
                                                                Mark Published
                                                            </Button>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <p className="text-sm text-gray-500">No briefs yet. Create your first brief from an opportunity.</p>
                        )}

                        {briefs.links && (
                            <div className="mt-4 flex justify-center">
                                <nav className="flex gap-2">
                                    {briefs.links.map((link, idx) => (
                                        <button
                                            key={idx}
                                            onClick={() => link.url && router.visit(link.url)}
                                            disabled={!link.url}
                                            className={`px-3 py-1 rounded ${
                                                link.active ? 'bg-blue-500 text-white' :
                                                link.url ? 'bg-gray-200 hover:bg-gray-300' :
                                                'bg-gray-100 text-gray-400 cursor-not-allowed'
                                            }`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </nav>
                            </div>
                        )}
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}

