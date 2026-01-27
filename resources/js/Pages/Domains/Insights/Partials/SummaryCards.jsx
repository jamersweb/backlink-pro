import Card from '@/Components/Shared/Card';

export default function SummaryCards({ summary }) {
    return (
        <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <Card>
                <div className="p-4">
                    <p className="text-gray-600 text-xs font-medium mb-1">SEO Health</p>
                    <div className="flex items-center gap-2">
                        <span className={`text-2xl font-bold ${
                            (summary.health_score || 0) >= 80 ? 'text-green-600' :
                            (summary.health_score || 0) >= 60 ? 'text-yellow-600' :
                            'text-red-600'
                        }`}>
                            {summary.health_score || 0}
                        </span>
                        <span className="text-xs text-gray-500">/100</span>
                    </div>
                </div>
            </Card>
            <Card>
                <div className="p-4">
                    <p className="text-gray-600 text-xs font-medium mb-1">GSC Clicks (28d)</p>
                    <p className="text-2xl font-bold text-gray-900">
                        {summary.gsc_clicks_28d?.toLocaleString() || 0}
                    </p>
                </div>
            </Card>
            <Card>
                <div className="p-4">
                    <p className="text-gray-600 text-xs font-medium mb-1">GA Sessions (28d)</p>
                    <p className="text-2xl font-bold text-gray-900">
                        {summary.ga_sessions_28d?.toLocaleString() || 0}
                    </p>
                </div>
            </Card>
            <Card>
                <div className="p-4">
                    <p className="text-gray-600 text-xs font-medium mb-1">Backlinks New</p>
                    <p className="text-2xl font-bold text-green-600">
                        +{summary.backlinks_new || 0}
                    </p>
                </div>
            </Card>
            <Card>
                <div className="p-4">
                    <p className="text-gray-600 text-xs font-medium mb-1">Backlinks Lost</p>
                    <p className="text-2xl font-bold text-red-600">
                        -{summary.backlinks_lost || 0}
                    </p>
                </div>
            </Card>
            <Card>
                <div className="p-4">
                    <p className="text-gray-600 text-xs font-medium mb-1">Meta Failed</p>
                    <p className="text-2xl font-bold text-red-600">
                        {summary.meta_failed || 0}
                    </p>
                </div>
            </Card>
        </div>
    );
}


