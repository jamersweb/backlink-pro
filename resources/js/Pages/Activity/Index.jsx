import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import AppLayout from '../../Components/Layout/AppLayout';
import Card from '../../Components/Shared/Card';
import Button from '../../Components/Shared/Button';
import Input from '../../Components/Shared/Input';

export default function ActivityIndex({ activities, stats, actionTypes, filters }) {
    const { flash } = usePage().props;
    const [localFilters, setLocalFilters] = useState(filters || {
        action: '',
        date_from: '',
        date_to: '',
    });

    const handleFilterChange = (key, value) => {
        const newFilters = { ...localFilters, [key]: value };
        setLocalFilters(newFilters);
        router.get('/activity', newFilters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const getActionIcon = (action) => {
        const icons = {
            'campaign.created': '➕',
            'campaign.updated': '✏️',
            'campaign.deleted': '🗑️',
            'campaign.paused': '⏸️',
            'campaign.resumed': '▶️',
            'backlink.created': '🔗',
            'backlink.verified': '✅',
            'backlink.failed': '❌',
            'domain.created': '🌐',
            'domain.updated': '✏️',
            'domain.deleted': '🗑️',
            'user.login': '🔐',
            'user.logout': '🚪',
            'user.registered': '👤',
        };
        return icons[action] || '📝';
    };

    const getActionColor = (action) => {
        if (action?.includes('created') || action?.includes('verified') || action?.includes('resumed')) {
            return 'border-green-200 bg-green-50';
        }
        if (action?.includes('updated')) {
            return 'border-blue-200 bg-blue-50';
        }
        if (action?.includes('deleted') || action?.includes('failed') || action?.includes('paused')) {
            return 'border-red-200 bg-red-50';
        }
        return 'border-gray-200 bg-gray-50';
    };

    const formatActionName = (action) => {
        return actionTypes?.[action] || action?.replace('.', ' ').replace(/\b\w/g, l => l.toUpperCase()) || action;
    };

    return (
        <AppLayout header="Activity Feed">
            <div className="space-y-6">
                {/* Flash Messages */}
                {flash?.success && (
                    <div className="p-4 bg-green-50 border border-green-200 rounded-md">
                        <p className="text-sm text-green-800">{flash.success}</p>
                    </div>
                )}

                {/* Stats */}
                <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <div className="text-center p-4">
                            <div className="text-2xl font-bold text-gray-900">{stats?.total_backlinks || 0}</div>
                            <div className="text-sm text-gray-500">Total Backlinks</div>
                        </div>
                    </Card>
                    <Card className="bg-white border border-green-200 shadow-md">
                        <div className="text-center p-4">
                            <div className="text-2xl font-bold text-green-600">{stats?.verified_backlinks || 0}</div>
                            <div className="text-sm text-gray-500">Verified</div>
                        </div>
                    </Card>
                    <Card className="bg-white border border-yellow-200 shadow-md">
                        <div className="text-center p-4">
                            <div className="text-2xl font-bold text-yellow-600">{stats?.pending_backlinks || 0}</div>
                            <div className="text-sm text-gray-500">Pending</div>
                        </div>
                    </Card>
                    <Card className="bg-white border border-blue-200 shadow-md">
                        <div className="text-center p-4">
                            <div className="text-2xl font-bold text-blue-600">{stats?.active_campaigns || 0}</div>
                            <div className="text-sm text-gray-500">Active Campaigns</div>
                        </div>
                    </Card>
                </div>

                {/* Filters */}
                <Card className="bg-white border border-gray-200 shadow-md">
                    <h3 className="text-lg font-bold text-gray-900 mb-4">Filters</h3>
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Action Type</label>
                            <select
                                value={localFilters.action || ''}
                                onChange={(e) => handleFilterChange('action', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500"
                            >
                                <option value="">All Actions</option>
                                {actionTypes && Object.entries(actionTypes).map(([key, label]) => (
                                    <option key={key} value={key}>{label}</option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                            <Input
                                type="date"
                                value={localFilters.date_from || ''}
                                onChange={(e) => handleFilterChange('date_from', e.target.value)}
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                            <Input
                                type="date"
                                value={localFilters.date_to || ''}
                                onChange={(e) => handleFilterChange('date_to', e.target.value)}
                            />
                        </div>
                        <div className="flex items-end">
                            <Button
                                variant="secondary"
                                onClick={() => {
                                    const emptyFilters = { action: '', date_from: '', date_to: '' };
                                    setLocalFilters(emptyFilters);
                                    router.get('/activity', emptyFilters);
                                }}
                            >
                                Clear Filters
                            </Button>
                        </div>
                    </div>
                </Card>

                {/* Activity Feed */}
                <Card className="bg-white border border-gray-200 shadow-md">
                    <h3 className="text-lg font-bold text-gray-900 mb-4">Activity Log</h3>
                    {activities?.data && activities.data.length > 0 ? (
                        <div className="space-y-3">
                            {activities.data.map((activity) => (
                                <div
                                    key={activity.id}
                                    className={`p-4 border-2 rounded-lg ${getActionColor(activity.action)}`}
                                >
                                    <div className="flex items-start gap-3">
                                        <div className="text-2xl">
                                            {getActionIcon(activity.action)}
                                        </div>
                                        <div className="flex-1">
                                            <p className="text-sm font-medium text-gray-900">
                                                {activity.description || formatActionName(activity.action)}
                                            </p>
                                            <div className="mt-1 text-xs text-gray-500 space-y-1">
                                                <div>
                                                    <span className="font-medium">Action:</span> {formatActionName(activity.action)}
                                                </div>
                                                {activity.properties && Object.keys(activity.properties).length > 0 && (
                                                    <div className="text-xs text-gray-400">
                                                        {JSON.stringify(activity.properties, null, 2)}
                                                    </div>
                                                )}
                                                <div>
                                                    {new Date(activity.created_at).toLocaleString()}
                                                    {activity.ip_address && ` • IP: ${activity.ip_address}`}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <p className="text-gray-500 text-center py-8">No activity logged yet.</p>
                    )}

                    {/* Pagination */}
                    {activities?.links && activities.links.length > 3 && (
                        <div className="mt-6 pt-4 border-t border-gray-200">
                            <div className="flex items-center justify-between">
                                <div className="text-sm text-gray-600">
                                    Showing {activities.from} to {activities.to} of {activities.total} results
                                </div>
                                <div className="flex gap-2">
                                    {activities.links.map((link, index) => (
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
