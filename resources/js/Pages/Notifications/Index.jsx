import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import AppLayout from '../../Components/Layout/AppLayout';
import Card from '../../Components/Shared/Card';
import Button from '../../Components/Shared/Button';

export default function NotificationsIndex({ notifications, unreadCount, filters }) {
    const { flash } = usePage().props;
    const [localFilters, setLocalFilters] = useState(filters || {
        filter: '',
        type: '',
    });

    const handleFilterChange = (key, value) => {
        const newFilters = { ...localFilters, [key]: value };
        setLocalFilters(newFilters);
        router.get('/notifications', newFilters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleMarkAsRead = (id) => {
        router.post(`/notifications/${id}/read`);
    };

    const handleMarkAllAsRead = () => {
        router.post('/notifications/mark-all-read');
    };

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this notification?')) {
            router.delete(`/notifications/${id}`);
        }
    };

    const getNotificationIcon = (type) => {
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️',
        };
        return icons[type] || '📢';
    };

    const getNotificationColor = (type, read) => {
        const baseColors = {
            success: 'border-green-200',
            error: 'border-red-200',
            warning: 'border-yellow-200',
            info: 'border-blue-200',
        };
        const bgColors = {
            success: read ? 'bg-green-50' : 'bg-green-100',
            error: read ? 'bg-red-50' : 'bg-red-100',
            warning: read ? 'bg-yellow-50' : 'bg-yellow-100',
            info: read ? 'bg-blue-50' : 'bg-blue-100',
        };
        return `${baseColors[type] || 'border-gray-200'} ${bgColors[type] || (read ? 'bg-gray-50' : 'bg-gray-100')}`;
    };

    return (
        <AppLayout header="Notifications">
            <div className="space-y-6">
                {/* Flash Messages */}
                {flash?.success && (
                    <div className="p-4 bg-green-50 border border-green-200 rounded-md">
                        <p className="text-sm text-green-800">{flash.success}</p>
                    </div>
                )}

                {/* Header with Actions */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h1 className="text-3xl font-bold bg-gradient-to-r from-red-600 to-green-600 bg-clip-text text-transparent">
                            Notifications
                        </h1>
                        {unreadCount > 0 && (
                            <p className="text-sm text-gray-600 mt-1">
                                {unreadCount} unread notification{unreadCount !== 1 ? 's' : ''}
                            </p>
                        )}
                    </div>
                    {unreadCount > 0 && (
                        <Button variant="primary" onClick={handleMarkAllAsRead}>
                            Mark All as Read
                        </Button>
                    )}
                </div>

                {/* Filters */}
                <Card className="bg-white border border-gray-200 shadow-md">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Filter</label>
                            <select
                                value={localFilters.filter || ''}
                                onChange={(e) => handleFilterChange('filter', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500"
                            >
                                <option value="">All Notifications</option>
                                <option value="unread">Unread Only</option>
                                <option value="read">Read Only</option>
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Type</label>
                            <select
                                value={localFilters.type || ''}
                                onChange={(e) => handleFilterChange('type', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500"
                            >
                                <option value="">All Types</option>
                                <option value="success">Success</option>
                                <option value="error">Error</option>
                                <option value="warning">Warning</option>
                                <option value="info">Info</option>
                            </select>
                        </div>
                        <div className="flex items-end">
                            <Button
                                variant="secondary"
                                onClick={() => {
                                    const emptyFilters = { filter: '', type: '' };
                                    setLocalFilters(emptyFilters);
                                    router.get('/notifications', emptyFilters);
                                }}
                            >
                                Clear Filters
                            </Button>
                        </div>
                    </div>
                </Card>

                {/* Notifications List */}
                <Card className="bg-white border border-gray-200 shadow-md">
                    {notifications?.data && notifications.data.length > 0 ? (
                        <div className="space-y-3">
                            {notifications.data.map((notification) => (
                                <div
                                    key={notification.id}
                                    className={`p-4 border-2 rounded-lg transition-all ${
                                        getNotificationColor(notification.type, notification.read)
                                    } ${!notification.read ? 'ring-2 ring-blue-200' : ''}`}
                                >
                                    <div className="flex items-start gap-3">
                                        <div className="text-2xl flex-shrink-0">
                                            {getNotificationIcon(notification.type)}
                                        </div>
                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-start justify-between gap-2 mb-1">
                                                <div className="flex-1">
                                                    <div className="flex items-center gap-2">
                                                        <h3 className={`font-semibold text-gray-900 ${
                                                            !notification.read ? 'font-bold' : ''
                                                        }`}>
                                                            {notification.title}
                                                        </h3>
                                                        {!notification.read && (
                                                            <span className="px-2 py-0.5 text-xs font-bold bg-blue-500 text-white rounded-full">
                                                                NEW
                                                            </span>
                                                        )}
                                                    </div>
                                                    <p className="text-sm text-gray-700 mt-1">
                                                        {notification.message}
                                                    </p>
                                                    {notification.data && Object.keys(notification.data).length > 0 && (
                                                        <div className="mt-2 text-xs text-gray-500">
                                                            {notification.data.campaign_name && (
                                                                <span>Campaign: {notification.data.campaign_name}</span>
                                                            )}
                                                            {notification.data.backlink_url && (
                                                                <span className="block truncate">URL: {notification.data.backlink_url}</span>
                                                            )}
                                                        </div>
                                                    )}
                                                </div>
                                                <div className="flex flex-col items-end gap-2 flex-shrink-0">
                                                    <span className="text-xs text-gray-500 whitespace-nowrap">
                                                        {new Date(notification.created_at).toLocaleString()}
                                                    </span>
                                                    <div className="flex gap-1">
                                                        {!notification.read && (
                                                            <button
                                                                onClick={() => handleMarkAsRead(notification.id)}
                                                                className="px-2 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors"
                                                                title="Mark as read"
                                                            >
                                                                ✓
                                                            </button>
                                                        )}
                                                        <button
                                                            onClick={() => handleDelete(notification.id)}
                                                            className="px-2 py-1 text-xs bg-red-500 text-white rounded hover:bg-red-600 transition-colors"
                                                            title="Delete"
                                                        >
                                                            ×
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
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
                            <p className="mt-1 text-sm text-gray-500">
                                {localFilters.filter || localFilters.type 
                                    ? 'No notifications match your filters.' 
                                    : "You're all caught up! No new notifications."}
                            </p>
                        </div>
                    )}

                    {/* Pagination */}
                    {notifications?.links && notifications.links.length > 3 && (
                        <div className="mt-6 pt-4 border-t border-gray-200">
                            <div className="flex items-center justify-between">
                                <div className="text-sm text-gray-600">
                                    Showing {notifications.from} to {notifications.to} of {notifications.total} results
                                </div>
                                <div className="flex gap-2">
                                    {notifications.links.map((link, index) => (
                                        <button
                                            key={index}
                                            onClick={() => link.url && router.get(link.url)}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                            className={`px-3 py-1 text-sm rounded-md ${
                                                link.active 
                                                    ? 'bg-blue-500 text-white' 
                                                    : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'
                                            } ${!link.url ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'}`}
                                            disabled={!link.url}
                                        />
                                    ))}
                                </div>
                            </div>
                        </div>
                    )}
                </Card>
            </div>
        </AppLayout>
    );
}
