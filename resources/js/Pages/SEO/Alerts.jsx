import { useState } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Components/Layout/AppLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';

export default function SeoAlerts({ organization, rules, alerts }) {
    const [showCreateRuleModal, setShowCreateRuleModal] = useState(false);

    const ruleForm = useForm({
        name: '',
        type: 'gsc_clicks_drop',
        config: {
            threshold: 30,
            lookback_days: 7,
        },
        notify_emails: [],
    });

    const handleCreateRule = (e) => {
        e.preventDefault();
        ruleForm.post(route('seo.alerts.rules.store', { organization: organization.id }), {
            preserveScroll: true,
            onSuccess: () => {
                setShowCreateRuleModal(false);
                ruleForm.reset();
            },
        });
    };

    const handleToggleRule = (ruleId) => {
        router.post(route('seo.alerts.rules.toggle', {
            organization: organization.id,
            rule: ruleId,
        }), {}, {
            preserveScroll: true,
        });
    };

    const getSeverityBadge = (severity) => {
        const colors = {
            info: 'bg-blue-100 text-blue-800',
            warning: 'bg-yellow-100 text-yellow-800',
            critical: 'bg-red-100 text-red-800',
        };
        return (
            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${colors[severity] || colors.info}`}>
                {severity.toUpperCase()}
            </span>
        );
    };

    const getTypeLabel = (type) => {
        const labels = {
            rank_drop: 'Rank Drop',
            gsc_clicks_drop: 'GSC Clicks Drop',
            ga4_sessions_drop: 'GA4 Sessions Drop',
            conversion_drop: 'Conversion Drop',
        };
        return labels[type] || type;
    };

    return (
        <AppLayout header={`SEO Alerts - ${organization.name}`}>
            <div className="space-y-6">
                {/* Header */}
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">SEO Alerts</h1>
                        <p className="text-sm text-gray-500 mt-1">Monitor and respond to SEO anomalies</p>
                    </div>
                    <div className="flex gap-2">
                        <Link href={route('seo.dashboard', { organization: organization.id })}>
                            <Button variant="outline">← Dashboard</Button>
                        </Link>
                        <Button variant="primary" onClick={() => setShowCreateRuleModal(true)}>
                            + Create Alert Rule
                        </Button>
                    </div>
                </div>

                {/* Alert Rules */}
                <Card>
                    <div className="p-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Alert Rules</h3>
                        {rules && rules.length > 0 ? (
                            <div className="space-y-3">
                                {rules.map((rule) => (
                                    <div key={rule.id} className="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                                        <div className="flex-1">
                                            <div className="flex items-center gap-2 mb-1">
                                                <span className="font-medium text-gray-900">{rule.name}</span>
                                                <span className={`px-2 py-1 text-xs font-semibold rounded-full ${
                                                    rule.is_enabled 
                                                        ? 'bg-green-100 text-green-800' 
                                                        : 'bg-gray-100 text-gray-800'
                                                }`}>
                                                    {rule.is_enabled ? 'Enabled' : 'Disabled'}
                                                </span>
                                            </div>
                                            <p className="text-sm text-gray-600">
                                                {getTypeLabel(rule.type)} • Threshold: {rule.config?.threshold || 30}% • 
                                                Lookback: {rule.config?.lookback_days || 7} days
                                            </p>
                                            {rule.alerts_count > 0 && (
                                                <p className="text-xs text-gray-500 mt-1">
                                                    {rule.alerts_count} alert(s) triggered
                                                </p>
                                            )}
                                        </div>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => handleToggleRule(rule.id)}
                                        >
                                            {rule.is_enabled ? 'Disable' : 'Enable'}
                                        </Button>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-sm text-gray-500">No alert rules configured. Create one to start monitoring.</p>
                        )}
                    </div>
                </Card>

                {/* Recent Alerts */}
                <Card>
                    <div className="p-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Recent Alerts</h3>
                        {alerts && alerts.length > 0 ? (
                            <div className="space-y-3">
                                {alerts.map((alert) => (
                                    <div key={alert.id} className="p-4 border-l-4 border-gray-300 bg-gray-50 rounded-lg">
                                        <div className="flex items-start justify-between mb-2">
                                            <div className="flex items-center gap-2">
                                                {getSeverityBadge(alert.severity)}
                                                <span className="font-medium text-gray-900">{alert.title}</span>
                                            </div>
                                            <span className="text-xs text-gray-500">
                                                {new Date(alert.created_at).toLocaleString()}
                                            </span>
                                        </div>
                                        <p className="text-sm text-gray-600 mb-2">{alert.message}</p>
                                        {alert.diff && (
                                            <div className="mt-2 text-xs text-gray-500">
                                                {alert.diff.baseline_avg && (
                                                    <span>Baseline: {alert.diff.baseline_avg} → </span>
                                                )}
                                                {alert.diff.yesterday && (
                                                    <span>Current: {alert.diff.yesterday}</span>
                                                )}
                                                {alert.diff.drop_percent && (
                                                    <span className="text-red-600 ml-2">
                                                        ({alert.diff.drop_percent}% drop)
                                                    </span>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-sm text-gray-500">No alerts yet. Alerts will appear here when anomalies are detected.</p>
                        )}
                    </div>
                </Card>

                {/* Create Rule Modal */}
                {showCreateRuleModal && (
                    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                        <Card className="w-full max-w-md">
                            <div className="p-6">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">Create Alert Rule</h3>
                                <form onSubmit={handleCreateRule} className="space-y-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Rule Name
                                        </label>
                                        <input
                                            type="text"
                                            value={ruleForm.data.name}
                                            onChange={(e) => ruleForm.setData('name', e.target.value)}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Alert Type
                                        </label>
                                        <select
                                            value={ruleForm.data.type}
                                            onChange={(e) => ruleForm.setData('type', e.target.value)}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                        >
                                            <option value="gsc_clicks_drop">GSC Clicks Drop</option>
                                            <option value="ga4_sessions_drop">GA4 Sessions Drop</option>
                                            <option value="rank_drop">Rank Drop</option>
                                            <option value="conversion_drop">Conversion Drop</option>
                                        </select>
                                    </div>
                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Threshold (%)
                                            </label>
                                            <input
                                                type="number"
                                                value={ruleForm.data.config.threshold}
                                                onChange={(e) => ruleForm.setData('config', {
                                                    ...ruleForm.data.config,
                                                    threshold: parseFloat(e.target.value),
                                                })}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                                min="0"
                                                max="100"
                                                step="0.1"
                                                required
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Lookback Days
                                            </label>
                                            <input
                                                type="number"
                                                value={ruleForm.data.config.lookback_days}
                                                onChange={(e) => ruleForm.setData('config', {
                                                    ...ruleForm.data.config,
                                                    lookback_days: parseInt(e.target.value),
                                                })}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                                min="1"
                                                max="30"
                                                required
                                            />
                                        </div>
                                    </div>
                                    <div className="flex gap-2 justify-end">
                                        <Button 
                                            type="button" 
                                            variant="outline" 
                                            onClick={() => setShowCreateRuleModal(false)}
                                        >
                                            Cancel
                                        </Button>
                                        <Button type="submit" variant="primary" disabled={ruleForm.processing}>
                                            {ruleForm.processing ? 'Creating...' : 'Create Rule'}
                                        </Button>
                                    </div>
                                </form>
                            </div>
                        </Card>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
