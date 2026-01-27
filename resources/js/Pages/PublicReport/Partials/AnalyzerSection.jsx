import Card from '@/Components/Shared/Card';

export default function AnalyzerSection({ data }) {
    if (!data) return null;

    return (
        <Card>
            <div className="p-6">
                <h2 className="text-xl font-bold text-gray-900 mb-4">Website Analysis</h2>
                
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div>
                        <p className="text-sm text-gray-600">Health Score</p>
                        <p className={`text-3xl font-bold ${
                            data.health_score >= 80 ? 'text-green-600' :
                            data.health_score >= 60 ? 'text-yellow-600' : 'text-red-600'
                        }`}>
                            {data.health_score}/100
                        </p>
                    </div>
                    <div>
                        <p className="text-sm text-gray-600">Pages Crawled</p>
                        <p className="text-3xl font-bold text-gray-900">{data.pages_crawled}</p>
                    </div>
                    <div>
                        <p className="text-sm text-gray-600">Critical Issues</p>
                        <p className="text-3xl font-bold text-red-600">{data.issues?.critical || 0}</p>
                    </div>
                </div>

                {data.top_critical_issues && data.top_critical_issues.length > 0 && (
                    <div>
                        <h3 className="text-lg font-semibold text-gray-900 mb-3">Top Critical Issues</h3>
                        <div className="space-y-2">
                            {data.top_critical_issues.map((issue, idx) => (
                                <div key={idx} className="p-3 bg-red-50 border border-red-200 rounded">
                                    <p className="font-medium text-gray-900">{issue.message}</p>
                                    {issue.url && (
                                        <p className="text-sm text-gray-600 mt-1 truncate">{issue.url}</p>
                                    )}
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </Card>
    );
}


