import { Link, router } from '@inertiajs/react';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';

export default function AlertsPanel({ alerts, domain }) {
    if (!alerts || alerts.length === 0) {
        return null;
    }

    const getSeverityBadge = (severity) => {
        const colors = {
            critical: 'bg-red-100 text-red-800',
            warning: 'bg-yellow-100 text-yellow-800',
            info: 'bg-blue-100 text-blue-800',
        };
        return (
            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${colors[severity] || colors.info}`}>
                {severity}
            </span>
        );
    };

    const getDeepLink = (alert) => {
        if (alert.related_url) {
            return alert.related_url;
        }

        const entity = alert.related_entity || {};
        switch (alert.type) {
            case 'audit_critical':
                return `/domains/${domain.id}/audits/${entity.audit_id}`;
            case 'gsc_drop':
            case 'ga_drop':
                return `/domains/${domain.id}/integrations/google`;
            case 'backlinks_lost_spike':
                return `/domains/${domain.id}/backlinks/${entity.run_id}`;
            case 'meta_failed':
                return `/domains/${domain.id}/meta`;
            default:
                return null;
        }
    };

    return (
        <Card>
            <div className="p-6">
                <h3 className="text-lg font-semibold text-gray-900 mb-4">Alerts</h3>
                <div className="space-y-3">
                    {alerts.map((alert) => (
                        <div
                            key={alert.id}
                            className={`p-4 rounded-lg border ${
                                alert.is_read
                                    ? 'bg-gray-50 border-gray-200'
                                    : 'bg-white border-gray-300'
                            }`}
                        >
                            <div className="flex justify-between items-start">
                                <div className="flex-1">
                                    <div className="flex items-center gap-2 mb-1">
                                        {getSeverityBadge(alert.severity)}
                                        <span className="font-medium text-gray-900">{alert.title}</span>
                                        {!alert.is_read && (
                                            <span className="w-2 h-2 bg-blue-600 rounded-full"></span>
                                        )}
                                    </div>
                                    {alert.message && (
                                        <p className="text-sm text-gray-600 mb-2">{alert.message}</p>
                                    )}
                                    <div className="flex items-center gap-2">
                                        {getDeepLink(alert) && (
                                            <Link href={getDeepLink(alert)}>
                                                <Button variant="outline" size="sm">View Details</Button>
                                            </Link>
                                        )}
                                        {!alert.is_read && (
                                            <button
                                                onClick={() => router.post(`/domains/${domain.id}/alerts/${alert.id}/read`, {}, { preserveScroll: true })}
                                                className="text-xs text-gray-500 hover:text-gray-700"
                                            >
                                                Mark read
                                            </button>
                                        )}
                                    </div>
                                </div>
                                <span className="text-xs text-gray-500">
                                    {new Date(alert.created_at).toLocaleDateString()}
                                </span>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </Card>
    );
}


