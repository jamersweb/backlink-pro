import { useState } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import AppLayout from '../../Components/Layout/AppLayout';
import Card from '../../Components/Shared/Card';
import Button from '../../Components/Shared/Button';
import Input from '../../Components/Shared/Input';
import { Link } from '@inertiajs/react';

export default function SettingsIndex({ user, plan }) {
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

    const tabs = [
        { id: 'profile', label: 'Profile', icon: 'bi-person', blurb: 'Personal details and account identity' },
        { id: 'password', label: 'Password', icon: 'bi-shield-lock', blurb: 'Security, password strength and login access' },
        { id: 'plan', label: 'Plan & Billing', icon: 'bi-credit-card-2-front', blurb: 'Subscription status, trial and billing actions' },
    ];

    const subscriptionBadgeClass =
        user.subscription_status === 'active'
            ? 'bg-emerald-500/15 text-emerald-300 border-emerald-400/20'
            : user.subscription_status === 'trialing'
                ? 'bg-sky-500/15 text-sky-300 border-sky-400/20'
                : 'bg-slate-500/15 text-slate-300 border-slate-400/20';

    const formatStatus = (status) => {
        if (!status) {
            return 'No Subscription';
        }

        return status.replace('_', ' ').replace(/\b\w/g, (char) => char.toUpperCase());
    };

    return (
        <AppLayout header="Settings" subtitle="Manage your profile, security and billing preferences">
            <div className="space-y-6">
                {flash?.success && (
                    <div className="rounded-2xl border border-emerald-400/20 bg-emerald-500/10 px-5 py-4 text-sm text-emerald-200 shadow-lg shadow-emerald-950/20">
                        {flash.success}
                    </div>
                )}

                {flash?.error && (
                    <div className="rounded-2xl border border-rose-400/20 bg-rose-500/10 px-5 py-4 text-sm text-rose-200 shadow-lg shadow-rose-950/20">
                        {flash.error}
                    </div>
                )}

                <Card className="overflow-hidden border border-[rgba(255,110,64,0.18)] bg-[radial-gradient(circle_at_top,rgba(255,110,64,0.08),transparent_30%),linear-gradient(180deg,rgba(22,18,18,0.94),rgba(10,10,10,0.98))] shadow-[0_24px_60px_rgba(0,0,0,0.24)]">
                    <div className="border-b border-[rgba(255,110,64,0.12)] px-6 py-6">
                        <div className="grid gap-4 lg:grid-cols-[1.2fr,0.8fr] lg:items-center">
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--admin-primary-light)]/80">Workspace Settings</p>
                                <h2 className="mt-2 text-2xl font-semibold text-[#fff7f2]">Control your account from one place</h2>
                                <p className="mt-2 max-w-2xl text-sm text-[rgba(255,240,232,0.62)]">
                                    Update profile details, tighten password security and manage your current plan without leaving the dashboard.
                                </p>
                            </div>
                            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                {tabs.map((tab) => (
                                    <button
                                        key={tab.id}
                                        onClick={() => setActiveTab(tab.id)}
                                        className={`rounded-2xl border px-4 py-4 text-left transition-all ${
                                            activeTab === tab.id
                                                ? 'border-[rgba(255,110,64,0.3)] bg-[radial-gradient(circle_at_top_left,rgba(255,110,64,0.12),transparent_30%),linear-gradient(180deg,rgba(34,24,22,0.96),rgba(18,14,13,0.98))] shadow-[0_20px_40px_rgba(0,0,0,0.2)]'
                                                : 'border-[rgba(255,110,64,0.14)] bg-[rgba(255,247,242,0.03)] hover:border-[rgba(255,110,64,0.24)] hover:bg-[rgba(255,110,64,0.05)]'
                                        }`}
                                    >
                                        <div className="flex items-center gap-3">
                                            <span className={`flex h-10 w-10 items-center justify-center rounded-xl ${activeTab === tab.id ? 'bg-[rgba(255,110,64,0.14)] text-[#ffcfb9]' : 'bg-[rgba(255,247,242,0.05)] text-[rgba(255,240,232,0.68)]'}`}>
                                                <i className={`bi ${tab.icon}`}></i>
                                            </span>
                                            <div>
                                                <div className={`text-sm font-semibold ${activeTab === tab.id ? 'text-[#fff7f2]' : 'text-[rgba(255,240,232,0.82)]'}`}>{tab.label}</div>
                                                <div className="mt-1 text-xs leading-5 text-[rgba(255,240,232,0.58)]">{tab.blurb}</div>
                                            </div>
                                        </div>
                                    </button>
                                ))}
                            </div>
                        </div>
                    </div>

                    <div className="p-6 lg:p-8">
                        {activeTab === 'profile' && (
                            <div className="grid gap-6 xl:grid-cols-[minmax(0,1.3fr),minmax(320px,0.7fr)]">
                                <Card className="border border-[rgba(255,110,64,0.18)] bg-[linear-gradient(180deg,rgba(22,18,18,0.94),rgba(10,10,10,0.98))]" variant="ghost">
                                    <div className="mb-6 flex items-start justify-between gap-4">
                                        <div>
                                            <p className="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--admin-primary-light)]/80">Profile</p>
                                            <h3 className="mt-2 text-2xl font-semibold text-[#fff7f2]">Profile Information</h3>
                                            <p className="mt-2 text-sm text-[rgba(255,240,232,0.62)]">Keep your account name and email updated for billing and notifications.</p>
                                        </div>
                                        <div className="hidden rounded-2xl border border-[rgba(255,110,64,0.14)] bg-[rgba(255,247,242,0.03)] px-4 py-3 text-right sm:block">
                                            <div className="text-xs uppercase tracking-[0.18em] text-[rgba(255,240,232,0.46)]">Status</div>
                                            <div className="mt-2 text-sm font-semibold text-emerald-300">Account Ready</div>
                                        </div>
                                    </div>

                                    <form onSubmit={handleProfileSubmit} className="space-y-5">
                                        <Input
                                            label="Full Name"
                                            name="name"
                                            value={profileForm.data.name}
                                            onChange={(e) => profileForm.setData('name', e.target.value)}
                                            error={profileForm.errors.name}
                                            required
                                            helpText="This name appears across your workspace and client-facing areas."
                                            icon={<i className="bi bi-person text-[rgba(255,240,232,0.46)]"></i>}
                                        />

                                        <Input
                                            label="Email Address"
                                            name="email"
                                            type="email"
                                            value={profileForm.data.email}
                                            onChange={(e) => profileForm.setData('email', e.target.value)}
                                            error={profileForm.errors.email}
                                            required
                                            helpText="Billing receipts and account alerts are sent to this address."
                                            icon={<i className="bi bi-envelope text-[rgba(255,240,232,0.46)]"></i>}
                                        />

                                        <div className="flex flex-wrap items-center gap-3 border-t border-[rgba(255,110,64,0.12)] pt-6">
                                            <Button type="submit" variant="primary" size="lg" disabled={profileForm.processing} className="min-w-[190px] rounded-2xl">
                                                {profileForm.processing ? 'Saving Changes...' : <><i className="bi bi-stars mr-2"></i>Update Profile</>}
                                            </Button>
                                            <p className="text-sm text-[rgba(255,240,232,0.58)]">Changes apply instantly across your dashboard.</p>
                                        </div>
                                    </form>
                                </Card>

                                <div className="space-y-6">
                                    <Card className="border border-[rgba(255,110,64,0.18)] bg-[linear-gradient(180deg,rgba(22,18,18,0.94),rgba(10,10,10,0.98))]" variant="ghost">
                                        <p className="text-xs font-semibold uppercase tracking-[0.24em] text-[rgba(255,240,232,0.58)]">Account Snapshot</p>
                                        <div className="mt-5 space-y-4">
                                            <div className="rounded-2xl border border-[rgba(255,110,64,0.14)] bg-[rgba(255,247,242,0.03)] p-4">
                                                <div className="text-xs uppercase tracking-[0.18em] text-[rgba(255,240,232,0.46)]">Member Since</div>
                                                <div className="mt-2 text-lg font-semibold text-[#fff7f2]">{new Date(user.created_at).toLocaleDateString()}</div>
                                            </div>
                                            <div className="rounded-2xl border border-[rgba(255,110,64,0.14)] bg-[rgba(255,247,242,0.03)] p-4">
                                                <div className="text-xs uppercase tracking-[0.18em] text-[rgba(255,240,232,0.46)]">Email Verification</div>
                                                <div className="mt-2 flex items-center gap-2 text-sm font-medium text-[#fff7f2]">
                                                    {user.email_verified_at ? (
                                                        <><i className="bi bi-patch-check-fill text-emerald-400"></i>Verified</>
                                                    ) : (
                                                        <><i className="bi bi-exclamation-diamond-fill text-amber-400"></i>Pending Verification</>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    </Card>

                                    <Card className="border border-[rgba(255,110,64,0.18)] bg-[rgba(255,110,64,0.06)]" variant="ghost">
                                        <p className="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--admin-primary-light)]/80">Quick Tip</p>
                                        <p className="mt-3 text-sm leading-6 text-[rgba(255,240,232,0.72)]">
                                            Use a business email here so invoices, campaign updates and trial reminders all arrive in one inbox.
                                        </p>
                                    </Card>
                                </div>
                            </div>
                        )}

                        {activeTab === 'password' && (
                            <div className="grid gap-6 xl:grid-cols-[minmax(0,1.15fr),minmax(320px,0.85fr)]">
                                <Card className="border border-[rgba(255,110,64,0.18)] bg-[linear-gradient(180deg,rgba(22,18,18,0.94),rgba(10,10,10,0.98))]" variant="ghost">
                                    <div className="mb-6">
                                        <p className="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--admin-primary-light)]/80">Security</p>
                                        <h3 className="mt-2 text-2xl font-semibold text-[#fff7f2]">Change Password</h3>
                                        <p className="mt-2 text-sm text-[rgba(255,240,232,0.62)]">Choose a strong password to keep your workspace and billing secure.</p>
                                    </div>

                                    <form onSubmit={handlePasswordSubmit} className="space-y-5">
                                        <Input
                                            label="Current Password"
                                            name="current_password"
                                            type="password"
                                            value={passwordForm.data.current_password}
                                            onChange={(e) => passwordForm.setData('current_password', e.target.value)}
                                            error={passwordForm.errors.current_password}
                                            required
                                            icon={<i className="bi bi-key text-[rgba(255,240,232,0.46)]"></i>}
                                        />

                                        <Input
                                            label="New Password"
                                            name="password"
                                            type="password"
                                            value={passwordForm.data.password}
                                            onChange={(e) => passwordForm.setData('password', e.target.value)}
                                            error={passwordForm.errors.password}
                                            required
                                            helpText="Use at least 8 characters with a strong mix of letters, numbers and symbols."
                                            icon={<i className="bi bi-shield-lock text-[rgba(255,240,232,0.46)]"></i>}
                                        />

                                        <Input
                                            label="Confirm New Password"
                                            name="password_confirmation"
                                            type="password"
                                            value={passwordForm.data.password_confirmation}
                                            onChange={(e) => passwordForm.setData('password_confirmation', e.target.value)}
                                            error={passwordForm.errors.password_confirmation}
                                            required
                                            icon={<i className="bi bi-check2-circle text-[rgba(255,240,232,0.46)]"></i>}
                                        />

                                        <div className="flex flex-wrap items-center gap-3 border-t border-[rgba(255,110,64,0.12)] pt-6">
                                            <Button type="submit" variant="primary" size="lg" disabled={passwordForm.processing} className="min-w-[210px] rounded-2xl">
                                                {passwordForm.processing ? 'Updating Password...' : <><i className="bi bi-shield-check mr-2"></i>Update Password</>}
                                            </Button>
                                            <p className="text-sm text-[rgba(255,240,232,0.58)]">You will stay logged in after the password update.</p>
                                        </div>
                                    </form>
                                </Card>

                                <div className="space-y-6">
                                    <Card className="border border-[rgba(255,110,64,0.18)] bg-[linear-gradient(180deg,rgba(22,18,18,0.94),rgba(10,10,10,0.98))]" variant="ghost">
                                        <p className="text-xs font-semibold uppercase tracking-[0.24em] text-[rgba(255,240,232,0.58)]">Security Checklist</p>
                                        <div className="mt-5 space-y-3 text-sm text-[rgba(255,240,232,0.72)]">
                                            <div className="flex items-start gap-3 rounded-2xl border border-[rgba(255,110,64,0.14)] bg-[rgba(255,247,242,0.03)] p-4">
                                                <i className="bi bi-check-circle-fill mt-0.5 text-emerald-400"></i>
                                                <span>Avoid reusing passwords from other apps or websites.</span>
                                            </div>
                                            <div className="flex items-start gap-3 rounded-2xl border border-[rgba(255,110,64,0.14)] bg-[rgba(255,247,242,0.03)] p-4">
                                                <i className="bi bi-check-circle-fill mt-0.5 text-emerald-400"></i>
                                                <span>Keep your password manager updated with the new credentials.</span>
                                            </div>
                                            <div className="flex items-start gap-3 rounded-2xl border border-[rgba(255,110,64,0.14)] bg-[rgba(255,247,242,0.03)] p-4">
                                                <i className="bi bi-check-circle-fill mt-0.5 text-emerald-400"></i>
                                                <span>Use a longer passphrase if this account controls billing or campaigns.</span>
                                            </div>
                                        </div>
                                    </Card>
                                </div>
                            </div>
                        )}

                        {activeTab === 'plan' && (
                            <div className="space-y-6">
                                <div className="grid gap-6 xl:grid-cols-[minmax(0,1.15fr),minmax(320px,0.85fr)]">
                                    <Card className="border border-[rgba(255,110,64,0.18)] bg-[linear-gradient(180deg,rgba(22,18,18,0.94),rgba(10,10,10,0.98))]" variant="ghost">
                                        <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
                                            <div>
                                                <p className="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--admin-primary-light)]/80">Billing</p>
                                                <h3 className="mt-2 text-2xl font-semibold text-[#fff7f2]">Plan & Billing</h3>
                                                <p className="mt-2 text-sm text-[rgba(255,240,232,0.62)]">Review your subscription status and jump straight to plan management.</p>
                                            </div>
                                            <span className={`inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] ${subscriptionBadgeClass}`}>
                                                {formatStatus(user.subscription_status)}
                                            </span>
                                        </div>

                                        {plan ? (
                                            <div className="space-y-5">
                                                <div className="rounded-3xl border border-[rgba(255,110,64,0.18)] bg-[radial-gradient(circle_at_top_left,rgba(255,110,64,0.1),transparent_30%),linear-gradient(180deg,rgba(22,18,18,0.94),rgba(10,10,10,0.98))] p-6">
                                                    <div className="flex flex-wrap items-start justify-between gap-4">
                                                        <div>
                                                            <div className="text-xs uppercase tracking-[0.18em] text-[var(--admin-primary-light)]/80">Current Plan</div>
                                                            <h4 className="mt-2 text-3xl font-semibold text-[#fff7f2]">{plan.name}</h4>
                                                            <p className="mt-2 text-sm text-[rgba(255,240,232,0.68)]">Designed for your current backlink operations and billing setup.</p>
                                                        </div>
                                                        <div className="rounded-2xl border border-[rgba(255,110,64,0.14)] bg-[rgba(255,247,242,0.03)] px-5 py-4 text-right">
                                                            <div className="text-xs uppercase tracking-[0.18em] text-[rgba(255,240,232,0.46)]">Price</div>
                                                            <div className="mt-2 text-2xl font-semibold text-[#fff7f2]">${plan.price}</div>
                                                            <div className="text-sm text-[rgba(255,240,232,0.58)]">per {plan.billing_interval}</div>
                                                        </div>
                                                    </div>

                                                    <div className="mt-5 grid gap-4 md:grid-cols-2">
                                                        <div className="rounded-2xl border border-[rgba(255,110,64,0.14)] bg-[rgba(255,247,242,0.03)] p-4">
                                                            <div className="text-xs uppercase tracking-[0.18em] text-[rgba(255,240,232,0.46)]">Domains Limit</div>
                                                            <div className="mt-2 text-lg font-semibold text-[#fff7f2]">
                                                                {plan.max_domains === null ? 'Unknown' : (plan.max_domains === -1 ? 'Unlimited' : plan.max_domains)}
                                                            </div>
                                                        </div>
                                                        <div className="rounded-2xl border border-[rgba(255,110,64,0.14)] bg-[rgba(255,247,242,0.03)] p-4">
                                                            <div className="text-xs uppercase tracking-[0.18em] text-[rgba(255,240,232,0.46)]">Trial Expiry</div>
                                                            <div className="mt-2 text-lg font-semibold text-[#fff7f2]">
                                                                {user.trial_ends_at ? new Date(user.trial_ends_at).toLocaleDateString() : 'No active trial'}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div className="flex flex-wrap gap-3 border-t border-[rgba(255,110,64,0.12)] pt-6">
                                                    <Link href="/subscription/manage">
                                                        <Button variant="primary" size="lg" className="rounded-2xl px-6">
                                                            <i className="bi bi-gear-wide-connected mr-2"></i>Manage Subscription
                                                        </Button>
                                                    </Link>
                                                    <Link href="/plans">
                                                        <Button variant="secondary" size="lg" className="rounded-2xl px-6">
                                                            <i className="bi bi-grid mr-2"></i>Browse Plans
                                                        </Button>
                                                    </Link>
                                                </div>
                                            </div>
                                        ) : (
                                            <div className="rounded-3xl border border-dashed border-[rgba(255,110,64,0.16)] bg-[rgba(255,247,242,0.03)] p-8 text-center">
                                                <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-[rgba(242,140,56,0.15)] text-[var(--admin-primary-light)]">
                                                    <i className="bi bi-credit-card-2-front text-2xl"></i>
                                                </div>
                                                <h4 className="mt-5 text-xl font-semibold text-[#fff7f2]">No active plan selected</h4>
                                                <p className="mt-2 text-sm text-[rgba(255,240,232,0.62)]">Pick a plan to unlock more limits and billing tools.</p>
                                                <div className="mt-6">
                                                    <Link href="/plans">
                                                        <Button variant="primary" size="lg" className="rounded-2xl px-6">
                                                            <i className="bi bi-stars mr-2"></i>Choose a Plan
                                                        </Button>
                                                    </Link>
                                                </div>
                                            </div>
                                        )}
                                    </Card>

                                    <div className="space-y-6">
                                        <Card className="border border-[rgba(255,110,64,0.18)] bg-[linear-gradient(180deg,rgba(22,18,18,0.94),rgba(10,10,10,0.98))]" variant="ghost">
                                            <p className="text-xs font-semibold uppercase tracking-[0.24em] text-[rgba(255,240,232,0.58)]">Billing Notes</p>
                                            <div className="mt-5 space-y-3 text-sm text-[rgba(255,240,232,0.72)]">
                                                <div className="rounded-2xl border border-[rgba(255,110,64,0.14)] bg-[rgba(255,247,242,0.03)] p-4">Open <span className="font-semibold text-[#fff7f2]">Manage Subscription</span> to update payment or cancel billing.</div>
                                                <div className="rounded-2xl border border-[rgba(255,110,64,0.14)] bg-[rgba(255,247,242,0.03)] p-4">Use <span className="font-semibold text-[#fff7f2]">Browse Plans</span> to compare upgrades before checkout.</div>
                                                <div className="rounded-2xl border border-[rgba(255,110,64,0.14)] bg-[rgba(255,247,242,0.03)] p-4">Trial users can move to a paid plan at any time without losing dashboard access.</div>
                                            </div>
                                        </Card>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}
