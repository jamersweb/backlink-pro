import Card from '@/Components/Shared/Card';

export default function ContentSection({ data }) {
    if (!data) return null;

    const getStatusBadge = (status) => {
        const colors = {
            draft: 'bg-gray-100 text-gray-800',
            writing: 'bg-yellow-100 text-yellow-800',
            published: 'bg-green-100 text-green-800',
        };
        return (
            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${colors[status] || colors.draft}`}>
                {status}
            </span>
        );
    };

    return (
        <Card>
            <div className="p-6">
                <h2 className="text-xl font-bold text-gray-900 mb-4">Content Plan</h2>
                
                {/* Briefs Summary */}
                {data.briefs && (
                    <div className="mb-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-3">Content Briefs</h3>
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <p className="text-sm text-gray-600">Draft</p>
                                <p className="text-2xl font-bold text-gray-900">{data.briefs.draft || 0}</p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">Writing</p>
                                <p className="text-2xl font-bold text-yellow-600">{data.briefs.writing || 0}</p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">Published</p>
                                <p className="text-2xl font-bold text-green-600">{data.briefs.published || 0}</p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">Total</p>
                                <p className="text-2xl font-bold text-gray-900">{data.briefs.total || 0}</p>
                            </div>
                        </div>
                    </div>
                )}

                {/* Top Opportunities */}
                {data.top_opportunities && data.top_opportunities.length > 0 && (
                    <div>
                        <h3 className="text-lg font-semibold text-gray-900 mb-3">Top Keyword Opportunities</h3>
                        <div className="space-y-2">
                            {data.top_opportunities.map((opp, idx) => (
                                <div key={idx} className="flex items-center justify-between p-3 bg-gray-50 border border-gray-200 rounded-lg">
                                    <div className="flex-1">
                                        <p className="font-medium text-gray-900">{opp.keyword}</p>
                                        <p className="text-xs text-gray-500 mt-1">
                                            Position: {opp.position?.toFixed(1) || 'N/A'}
                                        </p>
                                    </div>
                                    <div className="ml-3">
                                        <span className="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                            Score: {opp.score}
                                        </span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {(!data.briefs && (!data.top_opportunities || data.top_opportunities.length === 0)) && (
                    <p className="text-gray-500 text-sm">No content data available.</p>
                )}
            </div>
        </Card>
    );
}


