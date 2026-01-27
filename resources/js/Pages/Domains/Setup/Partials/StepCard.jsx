import { Link, router } from '@inertiajs/react';
import Button from '@/Components/Shared/Button';
import Card from '@/Components/Shared/Card';

export default function StepCard({ step, domain }) {
    const getStatusBadge = (status) => {
        const badges = {
            done: { label: 'Done', color: 'bg-green-100 text-green-800' },
            running: { label: 'Running...', color: 'bg-blue-100 text-blue-800' },
            in_progress: { label: 'In Progress', color: 'bg-yellow-100 text-yellow-800' },
            pending: { label: 'Not Started', color: 'bg-gray-100 text-gray-800' },
        };
        const badge = badges[status] || badges.pending;
        return (
            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${badge.color}`}>
                {badge.label}
            </span>
        );
    };

    const isDisabled = step.quotaBlocked?.blocked || false;

    return (
        <Card>
            <div className="p-6">
                <div className="flex items-start justify-between">
                    <div className="flex-1">
                        <div className="flex items-center gap-3 mb-2">
                            <h3 className="text-lg font-semibold text-gray-900">{step.title}</h3>
                            {step.optional && (
                                <span className="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-600">
                                    Optional
                                </span>
                            )}
                            {getStatusBadge(step.status)}
                        </div>
                        <p className="text-sm text-gray-600 mb-4">{step.description}</p>

                        {/* Quota Blocked Message */}
                        {isDisabled && (
                            <div className="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
                                <p className="text-sm text-yellow-800">
                                    <strong>Quota Exceeded:</strong> {step.quotaBlocked.reason}
                                    {step.quotaBlocked.resetDate && (
                                        <span className="block mt-1">
                                            Resets: {new Date(step.quotaBlocked.resetDate).toLocaleDateString()}
                                        </span>
                                    )}
                                </p>
                            </div>
                        )}

                        {/* Running Status */}
                        {step.running && (
                            <div className="mb-4 p-3 bg-blue-50 border border-blue-200 rounded">
                                <p className="text-sm text-blue-800">
                                    This step is currently running. Results will appear automatically when complete.
                                </p>
                            </div>
                        )}

                        {/* Actions */}
                        <div className="flex gap-3">
                            {step.action && (
                                <Button
                                    variant={step.status === 'done' ? 'outline' : 'primary'}
                                    onClick={step.action}
                                    disabled={isDisabled || step.running}
                                >
                                    {step.actionLabel || 'Start'}
                                </Button>
                            )}
                            {step.link && (
                                <Link href={step.link}>
                                    <Button variant="outline">
                                        {step.status === 'done' ? 'View Results' : 'Open Module'}
                                    </Button>
                                </Link>
                            )}
                        </div>
                    </div>

                    {/* Checkmark Icon */}
                    {step.status === 'done' && (
                        <div className="ml-4">
                            <svg className="w-8 h-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLineJoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    )}
                </div>
            </div>
        </Card>
    );
}


