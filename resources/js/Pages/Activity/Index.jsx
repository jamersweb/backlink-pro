import AppLayout from '../../Components/Layout/AppLayout';
import Card from '../../Components/Shared/Card';

export default function ActivityIndex({ activities, stats }) {
    const getActivityIcon = (type, status) => {
        if (type === 'backlink') {
            const icons = {
                verified: '✅',
                pending: '⏳',
                submitted: '📤',
                error: '❌',
            };
            return icons[status] || '📌';
        }
        return '📝';
    };

    const getActivityColor = (type, status) => {
        if (type === 'backlink') {
            const colors = {
                verified: 'border-green-200 bg-green-50',
                pending: 'border-yellow-200 bg-yellow-50',
                submitted: 'border-blue-200 bg-blue-50',
                error: 'border-red-200 bg-red-50',
            };
            return colors[status] || 'border-gray-200 bg-gray-50';
        }
        return 'border-gray-200 bg-gray-50';
    };

    return (
        <AppLayout header="Activity Feed">
            <div className="space-y-6">
                {/* Stats */}
                <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <div className="text-center">
                            <div className="text-2xl font-bold text-gray-900">{stats.total_backlinks || 0}</div>
                            <div className="text-sm text-gray-500">Total Backlinks</div>
                        </div>
                    </Card>
                    <Card>
                        <div className="text-center">
                            <div className="text-2xl font-bold text-green-600">{stats.verified_backlinks || 0}</div>
                            <div className="text-sm text-gray-500">Verified</div>
                        </div>
                    </Card>
                    <Card>
                        <div className="text-center">
                            <div className="text-2xl font-bold text-yellow-600">{stats.pending_backlinks || 0}</div>
                            <div className="text-sm text-gray-500">Pending</div>
                        </div>
                    </Card>
                    <Card>
                        <div className="text-center">
                            <div className="text-2xl font-bold text-blue-600">{stats.active_campaigns || 0}</div>
                            <div className="text-sm text-gray-500">Active Campaigns</div>
                        </div>
                    </Card>
                </div>

                {/* Activity Feed */}
                <Card title="Recent Activity">
                    {activities && activities.length > 0 ? (
                        <div className="space-y-4">
                            {activities.map((activity, index) => (
                                <div
                                    key={`${activity.type}-${activity.id}-${index}`}
                                    className={`p-4 border rounded-lg ${getActivityColor(activity.type, activity.status || activity.level)}`}
                                >
                                    <div className="flex items-start gap-3">
                                        <div className="text-2xl">
                                            {getActivityIcon(activity.type, activity.status || activity.level)}
                                        </div>
                                        <div className="flex-1">
                                            <p className="text-sm font-medium text-gray-900">
                                                {activity.message}
                                            </p>
                                            <div className="mt-1 text-xs text-gray-500">
                                                <span className="font-medium">{activity.campaign}</span>
                                                {' • '}
                                                {new Date(activity.created_at).toLocaleString()}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <p className="text-gray-500 text-center py-8">No activity yet.</p>
                    )}
                </Card>
            </div>
        </AppLayout>
    );
}

