export default function EvidenceBox({ evidence }) {
    if (!evidence || Object.keys(evidence).length === 0) {
        return null;
    }

    const formatValue = (key, value) => {
        if (typeof value === 'number') {
            if (key.includes('pct') || key.includes('drop') || key.includes('rate')) {
                return `${value > 0 ? '+' : ''}${value.toFixed(1)}%`;
            }
            return value.toLocaleString();
        }
        return String(value);
    };

    return (
        <div className="mt-3 p-3 bg-gray-50 rounded border border-gray-200">
            <h4 className="text-xs font-semibold text-gray-700 mb-2">Evidence:</h4>
            <div className="space-y-1">
                {Object.entries(evidence).map(([key, value]) => {
                    // Skip internal IDs
                    if (key.includes('_id') || key === 'audit_id' || key === 'run_id') {
                        return null;
                    }
                    return (
                        <div key={key} className="flex justify-between text-xs">
                            <span className="text-gray-600 capitalize">
                                {key.replace(/_/g, ' ')}:
                            </span>
                            <span className="text-gray-900 font-medium">
                                {formatValue(key, value)}
                            </span>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}


