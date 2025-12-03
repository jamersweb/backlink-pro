import { Link } from '@inertiajs/react';
import AppLayout from '../../Components/Layout/AppLayout';
import Card from '../../Components/Shared/Card';
import Button from '../../Components/Shared/Button';

export default function ProfileIndex({ user, plan, subscription, subscription_status, payment_method, upgradePlans = [] }) {
    const getStatusBadge = (status) => {
        const colors = {
            active: 'bg-green-100 text-green-800',
            canceled: 'bg-red-100 text-red-800',
            past_due: 'bg-yellow-100 text-yellow-800',
            trialing: 'bg-blue-100 text-blue-800',
        };
        return (
            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${colors[status] || 'bg-gray-100 text-gray-800'}`}>
                {status?.replace('_', ' ').toUpperCase()}
            </span>
        );
    };

    const formatDate = (timestamp) => {
        if (!timestamp) return 'N/A';
        return new Date(timestamp * 1000).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };

    const getCardBrandIcon = (brand) => {
        const icons = {
            visa: '💳',
            mastercard: '💳',
            amex: '💳',
            discover: '💳',
        };
        return icons[brand?.toLowerCase()] || '💳';
    };

    return (
        <AppLayout header="My Profile">
            <div className="space-y-6">
                {/* Profile Info */}
                <Card title="Profile Information">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label className="text-sm font-medium text-gray-500">Name</label>
                            <p className="text-lg text-gray-900">{user.name}</p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-500">Email</label>
                            <p className="text-lg text-gray-900">{user.email}</p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-500">Member Since</label>
                            <p className="text-lg text-gray-900">
                                {new Date(user.created_at).toLocaleDateString('en-US', {
                                    year: 'numeric',
                                    month: 'long',
                                    day: 'numeric'
                                })}
                            </p>
                        </div>
                        <div>
                            <Link href="/settings">
                                <Button variant="outline">Edit Profile</Button>
                            </Link>
                        </div>
                    </div>
                </Card>

                {/* Subscription Info */}
                <Card title="Subscription Information">
                    <div className="space-y-4">
                        {plan ? (
                            <>
                                <div className="flex items-center justify-between pb-4 border-b">
                                    <div>
                                        <h3 className="text-lg font-semibold text-gray-900">{plan.name}</h3>
                                        <p className="text-sm text-gray-600">{plan.description || 'Current Plan'}</p>
                                    </div>
                                    <div className="text-right">
                                        <div className="text-2xl font-bold text-gray-900">
                                            ${plan.price}
                                            <span className="text-sm font-normal text-gray-600">/{plan.billing_interval}</span>
                                        </div>
                                        {subscription_status && getStatusBadge(subscription_status)}
                                    </div>
                                </div>
                                
                                {/* Plan Features */}
                                {plan.features && plan.features.length > 0 && (
                                    <div className="pt-4 border-t">
                                        <label className="text-sm font-medium text-gray-500 mb-2 block">Plan Features</label>
                                        <ul className="grid grid-cols-1 md:grid-cols-2 gap-2">
                                            {plan.features.map((feature, index) => (
                                                <li key={index} className="flex items-center text-sm text-gray-700">
                                                    <svg className="h-4 w-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                                    </svg>
                                                    {feature}
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                )}
                                
                                {/* Plan Limits */}
                                <div className="pt-4 border-t">
                                    <label className="text-sm font-medium text-gray-500 mb-2 block">Plan Limits</label>
                                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <p className="text-xs text-gray-500">Max Domains</p>
                                            <p className="text-lg font-semibold text-gray-900">
                                                {plan.max_domains === -1 ? 'Unlimited' : plan.max_domains}
                                            </p>
                                        </div>
                                        <div>
                                            <p className="text-xs text-gray-500">Max Campaigns</p>
                                            <p className="text-lg font-semibold text-gray-900">
                                                {plan.max_campaigns === -1 ? 'Unlimited' : plan.max_campaigns}
                                            </p>
                                        </div>
                                        <div>
                                            <p className="text-xs text-gray-500">Daily Backlinks</p>
                                            <p className="text-lg font-semibold text-gray-900">
                                                {plan.daily_backlink_limit === -1 ? 'Unlimited' : plan.daily_backlink_limit}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </>
                        ) : (
                            <div className="text-center py-4">
                                <p className="text-gray-600 mb-4">You don't have an active subscription plan.</p>
                                <Link href="/plans">
                                    <Button variant="primary">View Plans</Button>
                                </Link>
                            </div>
                        )}
                        
                        {subscription && (
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t">
                                <div>
                                    <label className="text-sm font-medium text-gray-500">Subscription Start</label>
                                    <p className="text-lg text-gray-900 font-semibold">
                                        {formatDate(subscription.current_period_start)}
                                    </p>
                                </div>
                                <div>
                                    <label className="text-sm font-medium text-gray-500">Subscription End</label>
                                    <p className="text-lg text-gray-900 font-semibold">
                                        {formatDate(subscription.current_period_end)}
                                    </p>
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
                        
                        {payment_method && (
                            <div className="pt-4 border-t">
                                <label className="text-sm font-medium text-gray-500">Payment Method</label>
                                <div className="mt-2 flex items-center space-x-3">
                                    <span className="text-2xl">{getCardBrandIcon(payment_method.card?.brand)}</span>
                                    <div>
                                        <p className="text-lg font-semibold text-gray-900">
                                            {payment_method.card?.brand ? payment_method.card.brand.charAt(0).toUpperCase() + payment_method.card.brand.slice(1) : 'Card'} 
                                            {' •••• '}
                                            {payment_method.card?.last4}
                                        </p>
                                        <p className="text-sm text-gray-500">
                                            Expires {payment_method.card?.exp_month}/{payment_method.card?.exp_year}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        )}
                        
                        <div className="pt-4 border-t flex gap-2">
                            <Link href="/subscription/manage" className="flex-1">
                                <Button variant="outline" className="w-full">Manage Subscription</Button>
                            </Link>
                            <Link href="/plans" className="flex-1">
                                <Button variant="primary" className="w-full">Change Plan</Button>
                            </Link>
                        </div>
                    </div>
                </Card>

                {/* Upgrade Plans */}
                {upgradePlans && upgradePlans.length > 0 && (
                    <Card title="Upgrade Your Plan">
                        <p className="text-sm text-gray-600 mb-4">
                            {plan ? `Upgrade from ${plan.name} to unlock more features:` : 'Choose a plan to get started:'}
                        </p>
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            {upgradePlans.map((upgradePlan) => (
                                <div key={upgradePlan.id} className="border-2 border-gray-200 rounded-lg p-4 hover:border-green-500 transition-colors">
                                    <div className="flex items-center justify-between mb-2">
                                        <h4 className="text-lg font-semibold text-gray-900">{upgradePlan.name}</h4>
                                        <div className="text-right">
                                            <div className="text-xl font-bold text-gray-900">
                                                ${upgradePlan.price}
                                                <span className="text-xs font-normal text-gray-600">/{upgradePlan.billing_interval}</span>
                                            </div>
                                        </div>
                                    </div>
                                    {upgradePlan.description && (
                                        <p className="text-sm text-gray-600 mb-3">{upgradePlan.description}</p>
                                    )}
                                    {upgradePlan.features && upgradePlan.features.length > 0 && (
                                        <ul className="text-xs text-gray-600 mb-4 space-y-1">
                                            {upgradePlan.features.slice(0, 3).map((feature, index) => (
                                                <li key={index} className="flex items-start">
                                                    <svg className="h-3 w-3 text-green-500 mr-1 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                                    </svg>
                                                    {feature}
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                    <Link href={`/subscription/checkout/${upgradePlan.id}`} className="block">
                                        <Button variant="primary" className="w-full text-sm">
                                            Upgrade to {upgradePlan.name}
                                        </Button>
                                    </Link>
                                </div>
                            ))}
                        </div>
                    </Card>
                )}

                {/* Quick Stats */}
                <Card title="Account Statistics">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div className="text-center">
                            <div className="text-2xl font-bold text-gray-900">
                                {user.campaigns_count || 0}
                            </div>
                            <div className="text-sm text-gray-500">Total Campaigns</div>
                        </div>
                        <div className="text-center">
                            <div className="text-2xl font-bold text-gray-900">
                                {user.domains_count || 0}
                            </div>
                            <div className="text-sm text-gray-500">Domains</div>
                        </div>
                        <div className="text-center">
                            <div className="text-2xl font-bold text-gray-900">
                                {user.connected_accounts_count || 0}
                            </div>
                            <div className="text-sm text-gray-500">Connected Accounts</div>
                        </div>
                    </div>
                </Card>

                {/* Quick Links */}
                <Card title="Quick Actions">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <Link href="/campaign/create">
                            <Button variant="primary" className="w-full">Create Campaign</Button>
                        </Link>
                        <Link href="/reports">
                            <Button variant="secondary" className="w-full">View Reports</Button>
                        </Link>
                        <Link href="/activity">
                            <Button variant="secondary" className="w-full">Activity Feed</Button>
                        </Link>
                        <Link href="/settings">
                            <Button variant="outline" className="w-full">Account Settings</Button>
                        </Link>
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}

