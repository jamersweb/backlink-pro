import Button from '@/Components/Shared/Button';

export default function ReportsTable({ reports, onCopyLink, onRefresh, onRevoke }) {
    const getStatusBadge = (status) => {
        const colors = {
            active: 'bg-green-100 text-green-800',
            revoked: 'bg-red-100 text-red-800',
            expired: 'bg-gray-100 text-gray-800',
        };
        return (
            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${colors[status] || colors.active}`}>
                {status}
            </span>
        );
    };

    if (!reports || reports.length === 0) {
        return (
            <div className="text-center py-8">
                <p className="text-gray-500">No reports created yet. Create your first share link to get started.</p>
            </div>
        );
    }

    return (
        <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                    <tr>
                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expires</th>
                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sections</th>
                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Views</th>
                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                    {reports.map((report) => (
                        <tr key={report.id}>
                            <td className="px-4 py-3 text-sm text-gray-900">{report.title}</td>
                            <td className="px-4 py-3 text-sm">{getStatusBadge(report.status)}</td>
                            <td className="px-4 py-3 text-sm text-gray-500">
                                {new Date(report.created_at).toLocaleDateString()}
                            </td>
                            <td className="px-4 py-3 text-sm text-gray-500">
                                {report.expires_at ? new Date(report.expires_at).toLocaleDateString() : 'Never'}
                            </td>
                            <td className="px-4 py-3 text-sm text-gray-500">
                                {report.sections.join(', ')}
                            </td>
                            <td className="px-4 py-3 text-sm text-gray-500">
                                {report.views_count || 0}
                            </td>
                            <td className="px-4 py-3 text-sm">
                                <div className="flex gap-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => onCopyLink(report.public_url)}
                                    >
                                        Copy Link
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => onRefresh(report.id)}
                                    >
                                        Refresh
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => onRevoke(report.id)}
                                    >
                                        Revoke
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}


