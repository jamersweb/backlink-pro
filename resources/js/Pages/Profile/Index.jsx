import { Link } from '@inertiajs/react';
import AppLayout from '../../Components/Layout/AppLayout';
import Card from '../../Components/Shared/Card';
import Button from '../../Components/Shared/Button';

export default function ProfileIndex({ user, plan, subscription, subscription_status, payment_method }) {
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
                {(plan || subscription) && (
                    <Card title="Subscription Information">
                        <div className="space-y-4">
                            {plan && (
                                <div className="flex items-center justify-between pb-4 border-b">
                                    <div>
                                        <h3 className="text-lg font-semibold text-gray-900">{plan.name}</h3>
                                        <p className="text-sm text-gray-600">Current Plan</p>
                                    </div>
                                    <div className="text-right">
                                        <div className="text-2xl font-bold text-gray-900">
                                            ${plan.price}
                                            <span className="text-sm font-normal text-gray-600">/{plan.billing_interval}</span>
                                        </div>
                                        {subscription_status && getStatusBadge(subscription_status)}
                                    </div>
                                </div>
                            )}
                            
                            {subscription && (
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4">
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
                            
                            <div className="pt-4 border-t">
                                <Link href="/subscription/manage">
                                    <Button variant="primary" className="w-full">Manage Subscription</Button>
                                </Link>
                            </div>
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

