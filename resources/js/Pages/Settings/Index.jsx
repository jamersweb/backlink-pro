import { useState } from 'react';
import { useForm, usePage, router } from '@inertiajs/react';
import AppLayout from '../../Components/Layout/AppLayout';
import Card from '../../Components/Shared/Card';
import Button from '../../Components/Shared/Button';
import Input from '../../Components/Shared/Input';
import { Link } from '@inertiajs/react';

export default function SettingsIndex({ user, plan, connectedAccounts }) {
    const { flash } = usePage().props;
    const [activeTab, setActiveTab] = useState('profile');

    const profileForm = useForm({
        name: user.name || '',
        email: user.email || '',
    });

    const passwordForm = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const handleProfileSubmit = (e) => {
        e.preventDefault();
        profileForm.put('/settings/profile');
    };

    const handlePasswordSubmit = (e) => {
        e.preventDefault();
        passwordForm.put('/settings/password', {
            onSuccess: () => {
                passwordForm.reset();
            },
        });
    };

    const handleDisconnectGmail = (id) => {
        if (confirm('Are you sure you want to disconnect this Gmail account?')) {
            router.post(`/gmail/oauth/disconnect/${id}`);
        }
    };

    const tabs = [
        { id: 'profile', label: '👤 Profile', icon: '👤' },
        { id: 'password', label: '🔒 Password', icon: '🔒' },
        { id: 'plan', label: '💳 Plan & Billing', icon: '💳' },
        { id: 'accounts', label: '🔐 Connected Accounts', icon: '🔐' },
    ];

    return (
        <AppLayout header="Settings">
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

                {/* Tabs */}
                <Card className="bg-white border border-gray-200 shadow-md">
                    <div className="border-b border-gray-200">
                        <nav className="flex -mb-px space-x-4 overflow-x-auto">
                            {tabs.map((tab) => (
                                <button
                                    key={tab.id}
                                    onClick={() => setActiveTab(tab.id)}
                                    className={`px-4 py-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors ${
                                        activeTab === tab.id
                                            ? 'border-blue-500 text-blue-600'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    }`}
                                >
                                    <span className="mr-2">{tab.icon}</span>
                                    {tab.label}
                                </button>
                            ))}
                        </nav>
                    </div>

                    {/* Tab Content */}
                    <div className="p-6">
                        {/* Profile Tab */}
                        {activeTab === 'profile' && (
                            <div className="space-y-6">
                                <h3 className="text-lg font-bold text-gray-900">Profile Information</h3>
                                <form onSubmit={handleProfileSubmit} className="space-y-4">
                                    <Input
                                        label="Name"
                                        name="name"
                                        value={profileForm.data.name}
                                        onChange={(e) => profileForm.setData('name', e.target.value)}
                                        error={profileForm.errors.name}
                                        required
                                    />

                                    <Input
                                        label="Email"
                                        name="email"
                                        type="email"
                                        value={profileForm.data.email}
                                        onChange={(e) => profileForm.setData('email', e.target.value)}
                                        error={profileForm.errors.email}
                                        required
                                    />

                                    <div className="pt-4 border-t border-gray-200">
                                        <Button type="submit" variant="primary" disabled={profileForm.processing}>
                                            {profileForm.processing ? 'Updating...' : '💾 Update Profile'}
                                        </Button>
                                    </div>
                                </form>

                                <div className="pt-4 border-t border-gray-200">
                                    <h4 className="text-sm font-medium text-gray-700 mb-2">Account Information</h4>
                                    <div className="space-y-1 text-sm text-gray-600">
                                        <p><strong>Member since:</strong> {new Date(user.created_at).toLocaleDateString()}</p>
                                        <p><strong>Email verified:</strong> {user.email_verified_at ? '✅ Yes' : '❌ No'}</p>
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Password Tab */}
                        {activeTab === 'password' && (
                            <div className="space-y-6">
                                <h3 className="text-lg font-bold text-gray-900">Change Password</h3>
                                <form onSubmit={handlePasswordSubmit} className="space-y-4">
                                    <Input
                                        label="Current Password"
                                        name="current_password"
                                        type="password"
                                        value={passwordForm.data.current_password}
                                        onChange={(e) => passwordForm.setData('current_password', e.target.value)}
                                        error={passwordForm.errors.current_password}
                                        required
                                    />

                                    <Input
                                        label="New Password"
                                        name="password"
                                        type="password"
                                        value={passwordForm.data.password}
                                        onChange={(e) => passwordForm.setData('password', e.target.value)}
                                        error={passwordForm.errors.password}
                                        required
                                    />

                                    <Input
                                        label="Confirm New Password"
                                        name="password_confirmation"
                                        type="password"
                                        value={passwordForm.data.password_confirmation}
                                        onChange={(e) => passwordForm.setData('password_confirmation', e.target.value)}
                                        error={passwordForm.errors.password_confirmation}
                                        required
                                    />

                                    <div className="pt-4 border-t border-gray-200">
                                        <Button type="submit" variant="primary" disabled={passwordForm.processing}>
                                            {passwordForm.processing ? 'Updating...' : '🔒 Update Password'}
                                        </Button>
                                    </div>
                                </form>
                            </div>
                        )}

                        {/* Plan & Billing Tab */}
                        {activeTab === 'plan' && (
                            <div className="space-y-6">
                                <h3 className="text-lg font-bold text-gray-900">Plan & Billing</h3>
                                
                                {plan ? (
                                    <div className="space-y-4">
                                        <div className="p-4 bg-gray-50 rounded-lg">
                                            <div className="flex items-center justify-between mb-2">
                                                <h4 className="text-lg font-semibold text-gray-900">{plan.name}</h4>
                                                <span className={`px-3 py-1 text-sm font-medium rounded-full ${
                                                    user.subscription_status === 'active' ? 'bg-green-100 text-green-800' :
                                                    user.subscription_status === 'trialing' ? 'bg-blue-100 text-blue-800' :
                                                    'bg-gray-100 text-gray-800'
                                                }`}>
                                                    {user.subscription_status || 'No Subscription'}
                                                </span>
                                            </div>
                                            <div className="text-sm text-gray-600 space-y-1">
                                                <p><strong>Price:</strong> ${plan.price} / {plan.billing_interval}</p>
                                                <p><strong>Max Domains:</strong> {plan.max_domains === -1 ? 'Unlimited' : plan.max_domains}</p>
                                                <p><strong>Max Campaigns:</strong> {plan.max_campaigns === -1 ? 'Unlimited' : plan.max_campaigns}</p>
                                                <p><strong>Daily Backlink Limit:</strong> {plan.daily_backlink_limit === -1 ? 'Unlimited' : plan.daily_backlink_limit}</p>
                                            </div>
                                        </div>

                                        <div className="flex gap-4">
                                            <Link href="/subscription/manage">
                                                <Button variant="primary">⚙️ Manage Subscription</Button>
                                            </Link>
                                            <Link href="/plans">
                                                <Button variant="secondary">💳 View Plans</Button>
                                            </Link>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="text-center py-8">
                                        <p className="text-gray-600 mb-4">You don't have an active plan.</p>
                                        <Link href="/plans">
                                            <Button variant="primary">💳 Choose a Plan</Button>
                                        </Link>
                                    </div>
                                )}

                                {user.trial_ends_at && (
                                    <div className="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                        <p className="text-sm text-blue-800">
                                            <strong>Trial ends:</strong> {new Date(user.trial_ends_at).toLocaleDateString()}
                                        </p>
                                    </div>
                                )}
                            </div>
                        )}

                        {/* Connected Accounts Tab */}
                        {activeTab === 'accounts' && (
                            <div className="space-y-6">
                                <div className="flex items-center justify-between">
                                    <h3 className="text-lg font-bold text-gray-900">Connected Gmail Accounts</h3>
                                    <Link href="/gmail/oauth/connect">
                                        <Button variant="primary" size="sm">➕ Connect Gmail</Button>
                                    </Link>
                                </div>

                                {connectedAccounts && connectedAccounts.length > 0 ? (
                                    <div className="space-y-4">
                                        {connectedAccounts.map((account) => (
                                            <div key={account.id} className="p-4 border border-gray-200 rounded-lg">
                                                <div className="flex items-center justify-between">
                                                    <div className="flex-1">
                                                        <div className="flex items-center gap-3 mb-2">
                                                            <h4 className="text-base font-semibold text-gray-900">{account.email}</h4>
                                                            <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                                                                account.status === 'active' ? 'bg-green-100 text-green-800' :
                                                                account.status === 'revoked' ? 'bg-red-100 text-red-800' :
                                                                account.status === 'expired' ? 'bg-yellow-100 text-yellow-800' :
                                                                'bg-gray-100 text-gray-800'
                                                            }`}>
                                                                {account.status}
                                                            </span>
                                                        </div>
                                                        <div className="text-sm text-gray-600 space-y-1">
                                                            <p><strong>Used in campaigns:</strong> {account.campaigns_count || 0}</p>
                                                            {account.expires_at && (
                                                                <p><strong>Expires:</strong> {new Date(account.expires_at).toLocaleDateString()}</p>
                                                            )}
                                                        </div>
                                                    </div>
                                                    <div>
                                                        {account.status === 'active' && (
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() => handleDisconnectGmail(account.id)}
                                                            >
                                                                Disconnect
                                                            </Button>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="text-center py-8">
                                        <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                        <p className="mt-2 text-sm font-medium text-gray-900">No Gmail accounts connected</p>
                                        <p className="mt-1 text-sm text-gray-500">Connect a Gmail account to enable email verification features.</p>
                                        <div className="mt-6">
                                            <Link href="/gmail/oauth/connect">
                                                <Button variant="primary">Connect Gmail Account</Button>
                                            </Link>
                                        </div>
                                    </div>
                                )}

                                <div className="pt-4 border-t border-gray-200">
                                    <Link href="/gmail">
                                        <Button variant="secondary">🔐 Manage All Gmail Accounts</Button>
                                    </Link>
                                </div>
                            </div>
                        )}
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}
