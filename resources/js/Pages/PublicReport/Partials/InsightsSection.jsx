import Card from '@/Components/Shared/Card';

export default function InsightsSection({ data }) {
    if (!data) return null;

    const getPriorityBadge = (priority) => {
        const colors = {
            p1: 'bg-red-100 text-red-800',
            p2: 'bg-yellow-100 text-yellow-800',
            p3: 'bg-blue-100 text-blue-800',
        };
        return (
            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${colors[priority] || colors.p3}`}>
                {priority.toUpperCase()}
            </span>
        );
    };

    return (
        <Card>
            <div className="p-6">
                <h2 className="text-xl font-bold text-gray-900 mb-4">Key Recommendations</h2>
                
                {data.top_tasks && data.top_tasks.length > 0 ? (
                    <div className="space-y-3">
                        {data.top_tasks.map((task, idx) => (
                            <div key={idx} className="p-3 bg-gray-50 border border-gray-200 rounded-lg">
                                <div className="flex items-start justify-between">
                                    <div className="flex-1">
                                        <p className="font-medium text-gray-900">{task.title}</p>
                                        <p className="text-xs text-gray-500 mt-1">
                                            Impact Score: {task.impact_score}
                                        </p>
                                    </div>
                                    <div className="ml-3">
                                        {getPriorityBadge(task.priority)}
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                ) : (
                    <p className="text-gray-500 text-sm">No active recommendations at this time.</p>
                )}

                {data.unread_alerts > 0 && (
                    <div className="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <p className="text-sm text-yellow-800">
                            <strong>{data.unread_alerts}</strong> alert{data.unread_alerts !== 1 ? 's' : ''} require attention
                        </p>
                    </div>
                )}
            </div>
        </Card>
    );
}


