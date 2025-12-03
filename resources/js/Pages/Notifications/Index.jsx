import AppLayout from '../../Components/Layout/AppLayout';
import Card from '../../Components/Shared/Card';

export default function NotificationsIndex({ notifications }) {
    const getNotificationIcon = (type) => {
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️',
        };
        return icons[type] || '📢';
    };

    const getNotificationColor = (type) => {
        const colors = {
            success: 'border-green-200 bg-green-50',
            error: 'border-red-200 bg-red-50',
            warning: 'border-yellow-200 bg-yellow-50',
            info: 'border-blue-200 bg-blue-50',
        };
        return colors[type] || 'border-gray-200 bg-gray-50';
    };

    return (
        <AppLayout header="Notifications">
            <div className="space-y-6">
                <Card title="All Notifications">
                    {notifications && notifications.length > 0 ? (
                        <div className="space-y-4">
                            {notifications.map((notification) => (
                                <div
                                    key={notification.id}
                                    className={`p-4 border rounded-lg ${getNotificationColor(notification.type)}`}
                                >
                                    <div className="flex items-start gap-3">
                                        <div className="text-2xl">
                                            {getNotificationIcon(notification.type)}
                                        </div>
                                        <div className="flex-1">
                                            <div className="flex items-center justify-between mb-1">
                                                <h3 className="font-semibold text-gray-900">
                                                    {notification.title}
                                                </h3>
                                                <span className="text-xs text-gray-500">
                                                    {new Date(notification.created_at).toLocaleString()}
                                                </span>
                                            </div>
                                            <p className="text-sm text-gray-700 mb-1">
                                                {notification.message}
                                            </p>
                                            <p className="text-xs text-gray-500">
                                                Campaign: <span className="font-medium">{notification.campaign}</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="text-center py-12">
                            <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLineCap="round" strokeLineJoin="round" strokeWidth={2} d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <h3 className="mt-2 text-sm font-medium text-gray-900">No notifications</h3>
                            <p className="mt-1 text-sm text-gray-500">You're all caught up! No new notifications.</p>
                        </div>
                    )}
                </Card>
            </div>
        </AppLayout>
    );
}

