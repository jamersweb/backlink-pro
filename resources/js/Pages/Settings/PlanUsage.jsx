import { Link } from '@inertiajs/react';
import AppLayout from '../../Components/Layout/AppLayout';
import Card from '../../Components/Shared/Card';
import Button from '../../Components/Shared/Button';

export default function PlanUsage({ subscription, plan, usage }) {
    const formatDate = (date) => {
        if (!date) return 'N/A';
        return new Date(date).toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
    };

    const getUsagePercent = (used, limit) => {
        if (!limit || limit === 0) return 0;
        return Math.min(100, (used / limit) * 100);
    };

    const getUsageColor = (percent) => {
        if (percent >= 90) return 'bg-red-600';
        if (percent >= 75) return 'bg-yellow-600';
        return 'bg-blue-600';
    };

    const renderUsageBar = (key, label, data) => {
        const percent = getUsagePercent(data.used, data.limit);
        const color = getUsageColor(percent);

        return (
            <div key={key} className="mb-6">
                <div className="flex justify-between items-center mb-2">
                    <div>
                        <h4 className="font-medium text-gray-900">{label}</h4>
                        <p className="text-sm text-gray-500">
                            {data.used.toLocaleString()} / {data.limit ? data.limit.toLocaleString() : 'Unlimited'}
                        </p>
                    </div>
                    <div className="text-right">
                        <span className="text-sm font-semibold text-gray-700">
                            {percent.toFixed(0)}%
                        </span>
                        {data.reset_date && (
                            <p className="text-xs text-gray-500">
                                Resets {formatDate(data.reset_date)}
                            </p>
                        )}
                    </div>
                </div>
                <div className="w-full bg-gray-200 rounded-full h-2.5">
                    <div
                        className={`${color} h-2.5 rounded-full transition-all`}
                        style={{ width: `${percent}%` }}
                    ></div>
                </div>
            </div>
        );
    };

    return (
        <AppLayout header="Plan & Usage">
            <div className="space-y-6">
                {/* Current Plan Card */}
                <Card>
                    <div className="p-6">
                        <div className="flex justify-between items-start mb-4">
                            <div>
                                <h2 className="text-2xl font-bold text-gray-900">{plan.name}</h2>
                                <p className="text-sm text-gray-500 mt-1">
                                    {plan.price_monthly 
                                        ? `$${(plan.price_monthly / 100).toFixed(2)}/month`
                                        : 'Free'
                                    }
                                </p>
                            </div>
                            <div className="text-right">
                                <span className={`px-3 py-1 text-sm font-semibold rounded-full ${
                                    subscription.status === 'active' 
                                        ? 'bg-green-100 text-green-800'
                                        : 'bg-gray-100 text-gray-800'
                                }`}>
                                    {subscription.status}
                                </span>
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <p className="text-gray-500">Period Start</p>
                                <p className="font-medium text-gray-900">
                                    {formatDate(subscription.current_period_start)}
                                </p>
                            </div>
                            <div>
                                <p className="text-gray-500">Period End</p>
                                <p className="font-medium text-gray-900">
                                    {formatDate(subscription.current_period_end)}
                                </p>
                            </div>
                        </div>
                        <div className="mt-4 pt-4 border-t border-gray-200">
                            <Button variant="outline" onClick={() => alert('Upgrade coming soon!')}>
                                Upgrade Plan
                            </Button>
                        </div>
                    </div>
                </Card>

                {/* Usage Bars */}
                <Card>
                    <div className="p-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-6">Usage This Period</h3>
                        
                        {renderUsageBar('domains', 'Active Domains', usage.domains)}
                        {renderUsageBar('audits_runs', 'Audit Runs', usage.audits_runs)}
                        {renderUsageBar('audits_pages', 'Pages Crawled', usage.audits_pages)}
                        {renderUsageBar('backlinks_runs', 'Backlink Runs', usage.backlinks_runs)}
                        {renderUsageBar('backlinks_links', 'Backlinks Fetched', usage.backlinks_links)}
                        {renderUsageBar('google_sync', 'Google Sync (Manual)', usage.google_sync)}
                        {renderUsageBar('meta_publish', 'Meta Publishes', usage.meta_publish)}
                        {renderUsageBar('insights_runs', 'Insights Runs', usage.insights_runs)}
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}


