import AdminLayout from '../../../Components/Layout/AdminLayout';
import Card from '../../../Components/Shared/Card';
import Button from '../../../Components/Shared/Button';
import { Link, router } from '@inertiajs/react';

export default function AdminCampaignsShow({ campaign, backlinkStats, taskStats }) {
    const handlePause = () => {
        router.post(`/admin/campaigns/${campaign.id}/pause`, {}, {
            preserveScroll: true,
        });
    };

    const handleResume = () => {
        router.post(`/admin/campaigns/${campaign.id}/resume`, {}, {
            preserveScroll: true,
        });
    };

    const handleCreateTasks = () => {
        if (window.confirm('Create automation tasks for this campaign based on the user\'s plan?')) {
            router.post(`/admin/campaigns/${campaign.id}/create-tasks`, {}, {
                preserveScroll: true,
            });
        }
    };

    const handleDelete = () => {
        if (window.confirm(`Are you sure you want to delete "${campaign.name}"? This action cannot be undone.`)) {
            router.delete(`/admin/campaigns/${campaign.id}`);
        }
    };

    return (
        <AdminLayout header={`Campaign: ${campaign.name || 'Untitled'}`}>
            <div className="space-y-6">
                {/* Action Buttons */}
                <div className="flex items-center gap-4">
                    <Link href="/admin/campaigns">
                        <Button variant="secondary">← Back to Campaigns</Button>
                    </Link>
                    <Link href={`/admin/campaigns/${campaign.id}/edit`}>
                        <Button variant="primary">✏️ Edit Campaign</Button>
                    </Link>
                    {campaign.status === 'active' ? (
                        <Button variant="secondary" onClick={handlePause}>⏸️ Pause</Button>
                    ) : campaign.status === 'paused' ? (
                        <Button variant="primary" onClick={handleResume}>▶️ Resume</Button>
                    ) : null}
                    <Button variant="primary" onClick={handleCreateTasks}>⚙️ Create Tasks</Button>
                    <Button variant="danger" onClick={handleDelete}>🗑️ Delete</Button>
                </div>

                {/* Campaign Info */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <h3 className="text-lg font-bold text-gray-900 mb-4">Campaign Details</h3>
                        <div className="space-y-3">
                            <div>
                                <p className="text-sm text-gray-600">Name</p>
                                <p className="text-base font-semibold text-gray-900">{campaign.name || 'N/A'}</p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">Status</p>
                                <span className={`inline-block px-3 py-1 text-sm font-medium rounded-full ${
                                    campaign.status === 'active' ? 'bg-green-100 text-green-800' :
                                    campaign.status === 'paused' ? 'bg-yellow-100 text-yellow-800' :
                                    campaign.status === 'completed' ? 'bg-blue-100 text-blue-800' :
                                    campaign.status === 'error' ? 'bg-red-100 text-red-800' :
                                    'bg-gray-100 text-gray-800'
                                }`}>
                                    {campaign.status}
                                </span>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">User</p>
                                <p className="text-base font-semibold text-gray-900">
                                    {campaign.user?.name || 'N/A'} ({campaign.user?.email || 'N/A'})
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">Domain</p>
                                <p className="text-base font-semibold text-gray-900">{campaign.domain?.name || 'N/A'}</p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">Website URL</p>
                                <p className="text-base font-semibold text-gray-900 break-all">{campaign.web_url || 'N/A'}</p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">Keywords</p>
                                <p className="text-base font-semibold text-gray-900">{campaign.web_keyword || 'N/A'}</p>
                            </div>
                        </div>
                    </Card>

                    <Card className="bg-white border border-gray-200 shadow-md">
                        <h3 className="text-lg font-bold text-gray-900 mb-4">Settings</h3>
                        <div className="space-y-3">
                            <div>
                                <p className="text-sm text-gray-600">Daily Limit</p>
                                <p className="text-base font-semibold text-gray-900">{campaign.daily_limit || 'Not set'}</p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">Total Limit</p>
                                <p className="text-base font-semibold text-gray-900">{campaign.total_limit || 'Not set'}</p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">Start Date</p>
                                <p className="text-base font-semibold text-gray-900">
                                    {campaign.start_date ? new Date(campaign.start_date).toLocaleDateString() : 'Not set'}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">End Date</p>
                                <p className="text-base font-semibold text-gray-900">
                                    {campaign.end_date ? new Date(campaign.end_date).toLocaleDateString() : 'Not set'}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">Gmail Account</p>
                                <p className="text-base font-semibold text-gray-900">
                                    {campaign.gmail_account?.email || 'Not connected'}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">Email Verification</p>
                                <p className="text-base font-semibold text-gray-900">
                                    {campaign.requires_email_verification ? '✅ Enabled' : '❌ Disabled'}
                                </p>
                            </div>
                        </div>
                    </Card>
                </div>

                {/* Statistics */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <h3 className="text-lg font-bold text-gray-900 mb-4">Backlink Statistics</h3>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <p className="text-sm text-gray-600">Total</p>
                                <p className="text-2xl font-bold text-gray-900">{backlinkStats?.total || 0}</p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">Verified</p>
                                <p className="text-2xl font-bold text-green-600">{backlinkStats?.verified || 0}</p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">Pending</p>
                                <p className="text-2xl font-bold text-yellow-600">{backlinkStats?.pending || 0}</p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">Failed</p>
                                <p className="text-2xl font-bold text-red-600">{backlinkStats?.failed || 0}</p>
                            </div>
                            <div className="col-span-2">
                                <p className="text-sm text-gray-600">Today</p>
                                <p className="text-2xl font-bold text-blue-600">{backlinkStats?.today || 0}</p>
                            </div>
                        </div>
                    </Card>

                    <Card className="bg-white border border-gray-200 shadow-md">
                        <h3 className="text-lg font-bold text-gray-900 mb-4">Task Statistics</h3>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <p className="text-sm text-gray-600">Total</p>
                                <p className="text-2xl font-bold text-gray-900">{taskStats?.total || 0}</p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">Pending</p>
                                <p className="text-2xl font-bold text-yellow-600">{taskStats?.pending || 0}</p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">Running</p>
                                <p className="text-2xl font-bold text-blue-600">{taskStats?.running || 0}</p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">Success</p>
                                <p className="text-2xl font-bold text-green-600">{taskStats?.success || 0}</p>
                            </div>
                            <div className="col-span-2">
                                <p className="text-sm text-gray-600">Failed</p>
                                <p className="text-2xl font-bold text-red-600">{taskStats?.failed || 0}</p>
                            </div>
                        </div>
                    </Card>
                </div>

                {/* Recent Backlinks */}
                {campaign.backlinks && campaign.backlinks.length > 0 && (
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <h3 className="text-lg font-bold text-gray-900 mb-4">Recent Backlinks</h3>
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">URL</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Type</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Status</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Created</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {campaign.backlinks.map((backlink) => (
                                        <tr key={backlink.id}>
                                            <td className="px-4 py-3 text-sm text-gray-900 break-all">{backlink.url}</td>
                                            <td className="px-4 py-3 text-sm text-gray-600">{backlink.type}</td>
                                            <td className="px-4 py-3 text-sm">
                                                <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                                                    backlink.status === 'verified' ? 'bg-green-100 text-green-800' :
                                                    backlink.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                                    'bg-red-100 text-red-800'
                                                }`}>
                                                    {backlink.status}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                {new Date(backlink.created_at).toLocaleDateString()}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </Card>
                )}

                {/* Recent Tasks */}
                {campaign.automation_tasks && campaign.automation_tasks.length > 0 && (
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <h3 className="text-lg font-bold text-gray-900 mb-4">Recent Automation Tasks</h3>
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Type</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Status</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Created</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {campaign.automation_tasks.map((task) => (
                                        <tr key={task.id}>
                                            <td className="px-4 py-3 text-sm text-gray-900">{task.type}</td>
                                            <td className="px-4 py-3 text-sm">
                                                <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                                                    task.status === 'success' ? 'bg-green-100 text-green-800' :
                                                    task.status === 'running' ? 'bg-blue-100 text-blue-800' :
                                                    task.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                                    'bg-red-100 text-red-800'
                                                }`}>
                                                    {task.status}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-600">
                                                {new Date(task.created_at).toLocaleDateString()}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </Card>
                )}
            </div>
        </AdminLayout>
    );
}

