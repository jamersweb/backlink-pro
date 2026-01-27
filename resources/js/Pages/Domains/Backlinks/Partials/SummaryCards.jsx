import Card from '@/Components/Shared/Card';

export default function SummaryCards({ summary }) {
    return (
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            <Card>
                <div className="p-4">
                    <p className="text-gray-600 text-xs font-medium mb-1">Total Backlinks</p>
                    <p className="text-2xl font-bold text-gray-900">{summary.total_backlinks || 0}</p>
                </div>
            </Card>
            <Card>
                <div className="p-4">
                    <p className="text-gray-600 text-xs font-medium mb-1">Ref Domains</p>
                    <p className="text-2xl font-bold text-gray-900">{summary.ref_domains || 0}</p>
                </div>
            </Card>
            <Card>
                <div className="p-4">
                    <p className="text-gray-600 text-xs font-medium mb-1">Follow / Nofollow</p>
                    <p className="text-2xl font-bold text-gray-900">
                        {summary.follow || 0} / {summary.nofollow || 0}
                    </p>
                </div>
            </Card>
            <Card>
                <div className="p-4">
                    <p className="text-gray-600 text-xs font-medium mb-1">Risk Score</p>
                    <div className="flex items-center gap-2">
                        <span className={`text-2xl font-bold ${
                            summary.risk_score >= 80 ? 'text-green-600' :
                            summary.risk_score >= 60 ? 'text-yellow-600' :
                            'text-red-600'
                        }`}>
                            {summary.risk_score || 0}
                        </span>
                        <span className="text-xs text-gray-500">(heuristic)</span>
                    </div>
                </div>
            </Card>
        </div>
    );
}


