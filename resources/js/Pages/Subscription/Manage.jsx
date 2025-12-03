import { Link, useForm, usePage } from '@inertiajs/react';
import AppLayout from '../../Components/Layout/AppLayout';
import Card from '../../Components/Shared/Card';
import Button from '../../Components/Shared/Button';

export default function SubscriptionManage({ user, currentPlan, subscription, customer, invoices, allPlans }) {
    const { flash } = usePage().props;
    
    const { post: cancelSubscription, processing: canceling } = useForm();
    const { post: resumeSubscription, processing: resuming } = useForm();

    const handleCancel = () => {
        if (confirm('Are you sure you want to cancel your subscription? It will remain active until the end of the billing period.')) {
            cancelSubscription('/subscription/cancel');
        }
    };

    const handleResume = () => {
        resumeSubscription('/subscription/resume');
    };

    const formatDate = (timestamp) => {
        if (!timestamp) return 'N/A';
        return new Date(timestamp * 1000).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };

    const formatCurrency = (amount, currency = 'USD') => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currency,
        }).format(amount);
    };

    const getStatusBadge = (status) => {
        const statusColors = {
            active: 'bg-green-100 text-green-800',
            canceled: 'bg-red-100 text-red-800',
            past_due: 'bg-yellow-100 text-yellow-800',
            trialing: 'bg-blue-100 text-blue-800',
            incomplete: 'bg-gray-100 text-gray-800',
        };

        return (
            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${statusColors[status] || statusColors.incomplete}`}>
                {status?.replace('_', ' ').toUpperCase()}
            </span>
        );
    };

    return (
        <AppLayout header="Subscription Management">
            <div className="space-y-6">
                {/* Flash Messages */}
                {flash?.success && (
                    <div className="p-4 bg-green-50 border border-green-200 rounded-md">
                        <p className="text-sm text-green-800">{flash.success}</p>
                    </div>
                )}
                {flash?.error && (
                    <div className="p-4 bg-red-50 border border-red-200 rounded-md">
                        <p className="text-sm text-red-800">{flash.error}</p>
                    </div>
                )}

                {/* Current Subscription */}
                <Card title="Current Subscription">
                    {currentPlan ? (
                        <div className="space-y-4">
                            <div className="flex items-center justify-between">
                                <div>
                                    <h3 className="text-lg font-semibold text-gray-900">{currentPlan.name}</h3>
                                    <p className="text-sm text-gray-600">{currentPlan.description}</p>
                                </div>
                                <div className="text-right">
                                    <div className="text-2xl font-bold text-gray-900">
                                        ${currentPlan.price}
                                        <span className="text-sm font-normal text-gray-600">/{currentPlan.billing_interval}</span>
                                    </div>
                                    {getStatusBadge(user.subscription_status || subscription?.status)}
                                </div>
                            </div>

                            {subscription && (
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t">
                                    <div>
                                        <label className="text-sm font-medium text-gray-500">Current Period</label>
                                        <p className="text-gray-900">
                                            {formatDate(subscription.current_period_start)} - {formatDate(subscription.current_period_end)}
                                        </p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-gray-500">Subscription ID</label>
                                        <p className="text-gray-900 font-mono text-sm">{subscription.id}</p>
                                    </div>
                                    {subscription.cancel_at_period_end && (
                                        <div className="md:col-span-2">
                                            <div className="p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                                                <p className="text-sm text-yellow-800">
                                                    ⚠️ Your subscription will be cancelled on {formatDate(subscription.current_period_end)}
                                                </p>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )}

                            <div className="flex gap-4 pt-4 border-t">
                                {subscription?.cancel_at_period_end ? (
                                    <Button
                                        variant="primary"
                                        onClick={handleResume}
                                        disabled={resuming}
                                    >
                                        {resuming ? 'Resuming...' : 'Resume Subscription'}
                                    </Button>
                                ) : (
                                    <Button
                                        variant="outline"
                                        onClick={handleCancel}
                                        disabled={canceling}
                                    >
                                        {canceling ? 'Cancelling...' : 'Cancel Subscription'}
                                    </Button>
                                )}
                                <Link href="/pricing">
                                    <Button variant="secondary">Change Plan</Button>
                                </Link>
                            </div>
                        </div>
                    ) : (
                        <div className="text-center py-8">
                            <p className="text-gray-600 mb-4">You don't have an active subscription.</p>
                            <Link href="/pricing">
                                <Button variant="primary">Choose a Plan</Button>
                            </Link>
                        </div>
                    )}
                </Card>

                {/* Payment History */}
                {invoices && invoices.length > 0 && (
                    <Card title="Payment History">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Date
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Amount
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Invoice
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {invoices.map((invoice) => (
                                        <tr key={invoice.id}>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {formatDate(invoice.created)}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {formatCurrency(invoice.amount_paid, invoice.currency)}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                {getStatusBadge(invoice.status)}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm">
                                                {invoice.hosted_invoice_url ? (
                                                    <a
                                                        href={invoice.hosted_invoice_url}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="text-indigo-600 hover:underline"
                                                    >
                                                        View Invoice
                                                    </a>
                                                ) : (
                                                    <span className="text-gray-400">N/A</span>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </Card>
                )}

                {/* Available Plans */}
                <Card title="Available Plans">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        {allPlans?.map((plan) => (
                            <div
                                key={plan.id}
                                className={`p-4 border rounded-lg ${
                                    currentPlan?.id === plan.id
                                        ? 'border-indigo-500 bg-indigo-50'
                                        : 'border-gray-200'
                                }`}
                            >
                                <h4 className="font-semibold text-gray-900">{plan.name}</h4>
                                <p className="text-2xl font-bold text-gray-900 mt-2">
                                    ${plan.price}
                                    <span className="text-sm font-normal text-gray-600">/{plan.billing_interval}</span>
                                </p>
                                {currentPlan?.id === plan.id ? (
                                    <p className="text-sm text-indigo-600 mt-2">Current Plan</p>
                                ) : (
                                    <Link href={`/subscription/checkout/${plan.id}`}>
                                        <Button variant="outline" className="w-full mt-4">
                                            {plan.price === 0 ? 'Get Started' : 'Upgrade'}
                                        </Button>
                                    </Link>
                                )}
                            </div>
                        ))}
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}

