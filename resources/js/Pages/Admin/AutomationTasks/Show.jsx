import AdminLayout from '@/Components/Layout/AdminLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';
import { Link, router, usePage } from '@inertiajs/react';

export default function AdminAutomationTaskShow({ task, backlinks }) {
    const { flash } = usePage().props;

    const handleRetry = () => {
        if (window.confirm('Are you sure you want to retry this task?')) {
            router.post(`/admin/automation-tasks/${task.id}/retry`, {}, {
                preserveScroll: true,
            });
        }
    };

    const handleCancel = () => {
        if (window.confirm('Are you sure you want to cancel this task?')) {
            router.post(`/admin/automation-tasks/${task.id}/cancel`, {}, {
                preserveScroll: true,
            });
        }
    };

    const getStatusColor = (status) => {
        switch (status) {
            case 'success':
                return 'bg-green-100 text-green-800';
            case 'running':
                return 'bg-blue-100 text-blue-800';
            case 'pending':
                return 'bg-yellow-100 text-yellow-800';
            case 'failed':
                return 'bg-red-100 text-red-800';
            case 'cancelled':
                return 'bg-gray-100 text-gray-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    };

    const getTypeIcon = (type) => {
        switch (type) {
            case 'comment':
                return 'bi-chat-dots';
            case 'profile':
                return 'bi-person';
            case 'forum':
                return 'bi-people';
            case 'guest':
            case 'guestposting':
                return 'bi-pencil-square';
            default:
                return 'bi-gear';
        }
    };

    return (
        <AdminLayout header="Task Details">
            <div className="space-y-6">
                {/* Success/Error Messages */}
                {flash?.success && (
                    <div className="p-4 bg-green-50 border border-green-200 rounded-md">
                        <p className="text-sm text-green-800">{flash.success}</p>
                    </div>
                )}
                {flash?.error && (
                    <div className="p-4 bg-red-50 border border-red-200 rounded-md">
                        <p className="text-sm text-red-800">{flash.error}</p>
                    </div>
                )}

                {/* Back Button */}
                <div className="flex items-center justify-between">
                    <Link
                        href="/admin/automation-tasks"
                        className="text-gray-600 hover:text-gray-900 flex items-center gap-2"
                    >
                        <i className="bi bi-arrow-left"></i>
                        <span>Back to Tasks</span>
                    </Link>
                </div>

                {/* Task Information */}
                <Card>
                    <div className="p-6">
                        <div className="flex items-center justify-between mb-6">
                            <div className="flex items-center gap-4">
                                <div className={`p-3 rounded-lg ${getStatusColor(task.status)}`}>
                                    <i className={`bi ${getTypeIcon(task.type)} text-2xl`}></i>
                                </div>
                                <div>
                                    <h2 className="text-2xl font-bold text-gray-900">Task #{task.id}</h2>
                                    <p className="text-sm text-gray-600 capitalize">{task.type} Task</p>
                                </div>
                            </div>
                            <div className="flex items-center gap-3">
                                <span className={`px-3 py-1 text-sm font-medium rounded-full ${getStatusColor(task.status)}`}>
                                    {task.status}
                                </span>
                                {task.status === 'failed' && (
                                    <Button variant="primary" onClick={handleRetry}>
                                        <i className="bi bi-arrow-clockwise mr-2"></i>
                                        Retry
                                    </Button>
                                )}
                                {(task.status === 'pending' || task.status === 'running') && (
                                    <Button variant="danger" onClick={handleCancel}>
                                        <i className="bi bi-x-circle mr-2"></i>
                                        Cancel
                                    </Button>
                                )}
                            </div>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                            {/* Campaign Info */}
                            <div>
                                <h3 className="text-sm font-medium text-gray-500 mb-2">Campaign</h3>
                                <Link
                                    href={`/admin/campaigns/${task.campaign_id}`}
                                    className="text-lg font-semibold text-gray-900 hover:text-gray-700"
                                >
                                    {task.campaign?.name || 'N/A'}
                                </Link>
                                {task.campaign?.category && (
                                    <p className="text-sm text-gray-600 mt-1">
                                        Category: {task.campaign.category.name}
                                        {task.campaign.subcategory && ` / ${task.campaign.subcategory.name}`}
                                    </p>
                                )}
                            </div>

                            {/* User Info */}
                            <div>
                                <h3 className="text-sm font-medium text-gray-500 mb-2">User</h3>
                                <p className="text-lg font-semibold text-gray-900">
                                    {task.campaign?.user?.name || 'N/A'}
                                </p>
                                <p className="text-sm text-gray-600 mt-1">
                                    {task.campaign?.user?.email || ''}
                                </p>
                            </div>

                            {/* Task Type */}
                            <div>
                                <h3 className="text-sm font-medium text-gray-500 mb-2">Task Type</h3>
                                <p className="text-lg font-semibold text-gray-900 capitalize">{task.type}</p>
                            </div>

                            {/* Target URL */}
                            {task.payload?.target_urls && task.payload.target_urls.length > 0 && (
                                <div>
                                    <h3 className="text-sm font-medium text-gray-500 mb-2">Target URL</h3>
                                    <a
                                        href={task.payload.target_urls[0]}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="text-lg font-semibold text-blue-600 hover:text-blue-800 hover:underline break-all"
                                    >
                                        {task.payload.target_urls[0]}
                                        <i className="bi bi-box-arrow-up-right ml-2 text-sm"></i>
                                    </a>
                                    {task.payload.target_urls.length > 1 && (
                                        <p className="text-xs text-gray-500 mt-1">
                                            +{task.payload.target_urls.length - 1} more URL(s)
                                        </p>
                                    )}
                                </div>
                            )}

                            {/* Retry Info */}
                            <div>
                                <h3 className="text-sm font-medium text-gray-500 mb-2">Retry Count</h3>
                                <p className="text-lg font-semibold text-gray-900">
                                    {task.retry_count || 0} / {task.max_retries || 3}
                                </p>
                            </div>

                            {/* Created At */}
                            <div>
                                <h3 className="text-sm font-medium text-gray-500 mb-2">Created At</h3>
                                <p className="text-lg font-semibold text-gray-900">
                                    {new Date(task.created_at).toLocaleString()}
                                </p>
                            </div>

                            {/* Started At */}
                            {task.started_at && (
                                <div>
                                    <h3 className="text-sm font-medium text-gray-500 mb-2">Started At</h3>
                                    <p className="text-lg font-semibold text-gray-900">
                                        {new Date(task.started_at).toLocaleString()}
                                    </p>
                                </div>
                            )}

                            {/* Completed At */}
                            {task.completed_at && (
                                <div>
                                    <h3 className="text-sm font-medium text-gray-500 mb-2">Completed At</h3>
                                    <p className="text-lg font-semibold text-gray-900">
                                        {new Date(task.completed_at).toLocaleString()}
                                    </p>
                                </div>
                            )}

                            {/* Locked By */}
                            {task.locked_by && (
                                <div>
                                    <h3 className="text-sm font-medium text-gray-500 mb-2">Worker</h3>
                                    <p className="text-lg font-semibold text-gray-900">{task.locked_by}</p>
                                    {task.locked_at && (
                                        <p className="text-sm text-gray-600 mt-1">
                                            Locked: {new Date(task.locked_at).toLocaleString()}
                                        </p>
                                    )}
                                </div>
                            )}
                        </div>

                        {/* Error Message */}
                        {task.error_message && (
                            <div className="mt-6 p-4 bg-red-50 border border-red-200 rounded-md">
                                <div className="flex items-start justify-between mb-2">
                                    <h3 className="text-sm font-medium text-red-800">Error Message</h3>
                                    {task.retry_count > 0 && (
                                        <span className="text-xs text-red-600 bg-red-100 px-2 py-1 rounded">
                                            Retry {task.retry_count}/{task.max_retries}
                                        </span>
                                    )}
                                </div>
                                <div className="mt-2">
                                    <pre className="text-xs text-red-700 whitespace-pre-wrap break-words font-mono bg-white p-3 rounded border border-red-200 max-h-96 overflow-y-auto">
                                        {task.error_message}
                                    </pre>
                                </div>
                                {task.status === 'failed' && task.retry_count < task.max_retries && (
                                    <p className="text-xs text-red-600 mt-2">
                                        ⚠️ This task will be automatically retried ({task.max_retries - task.retry_count} retries remaining)
                                    </p>
                                )}
                            </div>
                        )}

                        {/* Payload */}
                        {task.payload && (
                            <div className="mt-6">
                                <h3 className="text-sm font-medium text-gray-500 mb-2">Task Payload</h3>
                                <div className="bg-gray-50 p-4 rounded-md">
                                    <pre className="text-xs text-gray-700 overflow-x-auto">
                                        {JSON.stringify(task.payload, null, 2)}
                                    </pre>
                                </div>
                            </div>
                        )}

                        {/* Result */}
                        {task.result && (
                            <div className="mt-6">
                                <h3 className="text-sm font-medium text-gray-500 mb-2">Task Result</h3>
                                <div className="bg-green-50 p-4 rounded-md">
                                    <pre className="text-xs text-gray-700 overflow-x-auto">
                                        {JSON.stringify(task.result, null, 2)}
                                    </pre>
                                </div>
                            </div>
                        )}
                    </div>
                </Card>

                {/* Related Backlinks */}
                {backlinks && backlinks.length > 0 && (
                    <Card>
                        <div className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">Related Backlinks</h3>
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">URL</th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">PA</th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">DA</th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {backlinks.map((backlink) => (
                                            <tr key={backlink.id} className="hover:bg-gray-50">
                                                <td className="px-4 py-3 text-sm text-gray-600">#{backlink.id}</td>
                                                <td className="px-4 py-3 text-sm">
                                                    <a
                                                        href={backlink.url}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="text-blue-600 hover:text-blue-900 truncate max-w-xs block"
                                                    >
                                                        {backlink.url}
                                                    </a>
                                                </td>
                                                <td className="px-4 py-3 text-sm">
                                                    <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                                                        backlink.status === 'verified' ? 'bg-green-100 text-green-800' :
                                                        backlink.status === 'submitted' ? 'bg-blue-100 text-blue-800' :
                                                        backlink.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                                        'bg-red-100 text-red-800'
                                                    }`}>
                                                        {backlink.status}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-3 text-sm text-gray-600">{backlink.pa || '-'}</td>
                                                <td className="px-4 py-3 text-sm text-gray-600">{backlink.da || '-'}</td>
                                                <td className="px-4 py-3 text-sm text-gray-600">
                                                    {new Date(backlink.created_at).toLocaleString()}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </Card>
                )}
            </div>
        </AdminLayout>
    );
}

