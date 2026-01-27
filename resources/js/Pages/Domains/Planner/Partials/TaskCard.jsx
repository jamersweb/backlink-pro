import { Link } from '@inertiajs/react';
import Checklist from './Checklist';
import EvidenceBox from './EvidenceBox';

export default function TaskCard({ item, domain, isFromPlan = false }) {
    const getPriorityBadge = (score) => {
        if (score >= 80) return { label: 'P1', color: 'bg-red-100 text-red-800' };
        if (score >= 55) return { label: 'P2', color: 'bg-yellow-100 text-yellow-800' };
        return { label: 'P3', color: 'bg-blue-100 text-blue-800' };
    };

    const getEffortBadge = (effort) => {
        const colors = {
            low: 'bg-green-100 text-green-800',
            medium: 'bg-yellow-100 text-yellow-800',
            high: 'bg-red-100 text-red-800',
        };
        return colors[effort] || colors.medium;
    };

    const priority = getPriorityBadge(item.priority_score || item.impact_score || 0);
    const title = item.title || item.type?.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());

    return (
        <div className="border border-gray-200 rounded-lg p-4 bg-white hover:shadow-md transition-shadow">
            {/* Header */}
            <div className="flex items-start justify-between mb-3">
                <div className="flex-1">
                    <h3 className="font-semibold text-gray-900 mb-2">{title}</h3>
                    <div className="flex gap-2 flex-wrap">
                        <span className={`px-2 py-1 text-xs font-semibold rounded-full ${priority.color}`}>
                            {priority.label}
                        </span>
                        <span className={`px-2 py-1 text-xs font-semibold rounded-full ${getEffortBadge(item.effort)}`}>
                            {item.effort || 'medium'} effort
                        </span>
                        {item.priority_score && (
                            <span className="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                Score: {item.priority_score}
                            </span>
                        )}
                    </div>
                </div>
            </div>

            {/* Why */}
            {item.why && (
                <div className="mb-3">
                    <p className="text-sm text-gray-700">{item.why}</p>
                </div>
            )}

            {/* Evidence */}
            {item.evidence && Object.keys(item.evidence).length > 0 && (
                <EvidenceBox evidence={item.evidence} />
            )}

            {/* Checklist */}
            {item.checklist && item.checklist.length > 0 && (
                <Checklist items={item.checklist} />
            )}

            {/* Links */}
            {item.links && item.links.length > 0 && (
                <div className="mt-3 pt-3 border-t border-gray-200">
                    <div className="flex flex-wrap gap-2">
                        {item.links.map((link, idx) => (
                            <Link
                                key={idx}
                                href={link.url}
                                className="text-xs text-blue-600 hover:text-blue-800 underline"
                            >
                                {link.label}
                            </Link>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}


