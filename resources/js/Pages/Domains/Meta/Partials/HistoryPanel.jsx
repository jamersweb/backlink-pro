import Card from '@/Components/Shared/Card';

export default function HistoryPanel({ changes }) {
    if (!changes || changes.length === 0) {
        return null;
    }

    const getStatusBadge = (status) => {
        const colors = {
            draft: 'bg-yellow-100 text-yellow-800',
            queued: 'bg-blue-100 text-blue-800',
            published: 'bg-green-100 text-green-800',
            failed: 'bg-red-100 text-red-800',
        };
        return (
            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${colors[status] || 'bg-gray-100 text-gray-800'}`}>
                {status}
            </span>
        );
    };

    return (
        <Card>
            <div className="p-4">
                <h3 className="text-lg font-semibold text-gray-900 mb-4">Recent Changes</h3>
                <div className="space-y-3">
                    {changes.map((change) => (
                        <div key={change.id} className="flex justify-between items-start p-3 border-b border-gray-200 last:border-0">
                            <div className="flex-1">
                                <div className="flex items-center gap-2 mb-1">
                                    {getStatusBadge(change.status)}
                                    <span className="text-sm text-gray-600">
                                        {new Date(change.created_at).toLocaleString()}
                                    </span>
                                </div>
                                {change.error_message && (
                                    <p className="text-xs text-red-600 mt-1">{change.error_message}</p>
                                )}
                                {change.status === 'published' && (
                                    <div className="mt-2 text-xs text-gray-500">
                                        <div className="font-medium">Changes:</div>
                                        {change.meta_after_json?.title && (
                                            <div>Title: {change.meta_after_json.title}</div>
                                        )}
                                        {change.meta_after_json?.description && (
                                            <div>Description: {change.meta_after_json.description.substring(0, 50)}...</div>
                                        )}
                                    </div>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </Card>
    );
}


