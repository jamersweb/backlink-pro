import { router } from '@inertiajs/react';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';

export default function TasksBoard({ openTasks, doingTasks, doneTasks, domain }) {
    const handleStatusChange = (task, newStatus) => {
        router.post(`/domains/${domain.id}/tasks/${task.id}/status`, {
            status: newStatus,
        }, {
            preserveScroll: true,
        });
    };

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

    const getSourceBadge = (source) => {
        const labels = {
            analyzer: 'Audit',
            gsc: 'GSC',
            ga4: 'GA4',
            backlinks: 'Backlinks',
            meta: 'Meta',
            insights: 'Manual',
        };
        return (
            <span className="px-2 py-0.5 text-xs bg-gray-100 text-gray-600 rounded">
                {labels[source] || source}
            </span>
        );
    };

    const getImpactBadge = (score) => {
        const color = score >= 75 ? 'text-red-600' : score >= 50 ? 'text-yellow-600' : 'text-blue-600';
        return (
            <span className={`text-xs font-semibold ${color}`}>
                Impact: {score}
            </span>
        );
    };

    const renderTask = (task) => (
        <div key={task.id} className="p-3 bg-white border border-gray-200 rounded-lg mb-2">
            <div className="flex justify-between items-start mb-2">
                <div className="flex-1">
                    <h4 className="font-medium text-sm text-gray-900 mb-1">{task.title}</h4>
                    {task.description && (
                        <p className="text-xs text-gray-600 mb-2">{task.description}</p>
                    )}
                    <div className="flex items-center gap-2 flex-wrap">
                        {getPriorityBadge(task.priority)}
                        {getSourceBadge(task.source)}
                        {getImpactBadge(task.impact_score)}
                    </div>
                </div>
            </div>
            {task.related_url && (
                <a
                    href={task.related_url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="text-xs text-blue-600 hover:underline"
                >
                    View related →
                </a>
            )}
            <div className="mt-2 flex gap-2">
                {task.status === 'open' && (
                    <>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => handleStatusChange(task, 'doing')}
                        >
                            Start
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => handleStatusChange(task, 'dismissed')}
                        >
                            Dismiss
                        </Button>
                    </>
                )}
                {task.status === 'doing' && (
                    <>
                        <Button
                            variant="primary"
                            size="sm"
                            onClick={() => handleStatusChange(task, 'done')}
                        >
                            Mark Done
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => handleStatusChange(task, 'open')}
                        >
                            Back to Open
                        </Button>
                    </>
                )}
                {task.status === 'done' && (
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => handleStatusChange(task, 'open')}
                    >
                        Reopen
                    </Button>
                )}
            </div>
        </div>
    );

    return (
        <Card>
            <div className="p-6">
                <h3 className="text-lg font-semibold text-gray-900 mb-4">Tasks</h3>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {/* Open Tasks */}
                    <div>
                        <h4 className="font-medium text-gray-900 mb-3">Open ({openTasks?.length || 0})</h4>
                        <div className="space-y-2 min-h-[200px]">
                            {openTasks && openTasks.length > 0 ? (
                                openTasks.map(renderTask)
                            ) : (
                                <p className="text-sm text-gray-500 text-center py-8">No open tasks</p>
                            )}
                        </div>
                    </div>

                    {/* Doing Tasks */}
                    <div>
                        <h4 className="font-medium text-gray-900 mb-3">Doing ({doingTasks?.length || 0})</h4>
                        <div className="space-y-2 min-h-[200px]">
                            {doingTasks && doingTasks.length > 0 ? (
                                doingTasks.map(renderTask)
                            ) : (
                                <p className="text-sm text-gray-500 text-center py-8">No tasks in progress</p>
                            )}
                        </div>
                    </div>

                    {/* Done Tasks */}
                    <div>
                        <h4 className="font-medium text-gray-900 mb-3">Done ({doneTasks?.length || 0})</h4>
                        <div className="space-y-2 min-h-[200px]">
                            {doneTasks && doneTasks.length > 0 ? (
                                doneTasks.map(renderTask)
                            ) : (
                                <p className="text-sm text-gray-500 text-center py-8">No completed tasks</p>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </Card>
    );
}


