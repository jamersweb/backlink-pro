import Card from '@/Components/Shared/Card';

export default function AuditSummaryCards({ audit }) {
    const summary = audit.summary_json || {};

    return (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <Card>
                <div className="p-4">
                    <p className="text-gray-600 text-xs font-medium mb-1">Health Score</p>
                    <div className="flex items-center gap-2">
                        <span className={`text-3xl font-bold ${
                            audit.health_score >= 80 ? 'text-green-600' :
                            audit.health_score >= 60 ? 'text-yellow-600' :
                            'text-red-600'
                        }`}>
                            {audit.health_score !== null ? audit.health_score : '-'}
                        </span>
                        {audit.health_score !== null && (
                            <div className="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div
                                    className={`h-full ${
                                        audit.health_score >= 80 ? 'bg-green-600' :
                                        audit.health_score >= 60 ? 'bg-yellow-600' :
                                        'bg-red-600'
                                    }`}
                                    style={{ width: `${audit.health_score}%` }}
                                />
                            </div>
                        )}
                    </div>
                </div>
            </Card>

            <Card>
                <div className="p-4">
                    <p className="text-gray-600 text-xs font-medium mb-1">Pages Crawled</p>
                    <p className="text-3xl font-bold text-gray-900">{summary.pages_crawled || 0}</p>
                </div>
            </Card>

            <Card>
                <div className="p-4">
                    <p className="text-gray-600 text-xs font-medium mb-1">Critical Issues</p>
                    <p className="text-3xl font-bold text-red-600">{summary.issues_critical || 0}</p>
                </div>
            </Card>

            <Card>
                <div className="p-4">
                    <p className="text-gray-600 text-xs font-medium mb-1">Warnings</p>
                    <p className="text-3xl font-bold text-yellow-600">{summary.issues_warning || 0}</p>
                    <p className="text-xs text-gray-500 mt-1">Info: {summary.issues_info || 0}</p>
                </div>
            </Card>
        </div>
    );
}


