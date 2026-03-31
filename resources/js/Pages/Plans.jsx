import { Head, Link, usePage } from '@inertiajs/react';
import AppLayout from '../Components/Layout/AppLayout';
import Button from '../Components/Shared/Button';

function formatMoney(value) {
    if (value === null || value === undefined) {
        return 'Custom';
    }

    if (Number(value) === 0) {
        return '$0';
    }

    return `$${Number(value).toFixed(0)}`;
}

function formatLimit(value) {
    if (value === null || value === undefined) {
        return 'N/A';
    }

    if (Number(value) === -1) {
        return 'Unlimited';
    }

    return Number(value).toLocaleString();
}

function PlanCard({ plan, isAuthenticated, isWide = false }) {
    const highlighted = !!plan.is_highlighted || plan.slug === 'growth';

    return (
        <div
            className={`relative overflow-hidden rounded-2xl border p-6 transition-all duration-300 ${
                highlighted
                    ? 'border-indigo-400/60 bg-gradient-to-b from-indigo-500/20 to-slate-900/80 shadow-[0_20px_60px_-24px_rgba(99,102,241,0.6)]'
                    : 'border-slate-700/80 bg-slate-900/70 hover:border-slate-500'
            } ${isWide ? 'md:col-span-2 xl:col-span-3 xl:grid xl:grid-cols-[minmax(340px,0.9fr)_minmax(0,1.1fr)] xl:gap-8' : ''}`}
        >
            <div>
                {plan.badge && (
                    <span className="absolute right-4 top-4 rounded-full border border-indigo-400/40 bg-indigo-500/20 px-3 py-1 text-xs font-semibold text-indigo-200">
                        {plan.badge}
                    </span>
                )}

                <div className={`mb-5 ${isWide ? 'pr-0 xl:max-w-md' : 'pr-24'}`}>
                    <h3 className="text-2xl font-bold text-white">{plan.name}</h3>
                    <p className="mt-1 text-sm text-slate-300">{plan.tagline || 'SEO growth plan'}</p>
                </div>

                <div className="mb-5 flex items-end gap-2">
                    <span className="text-4xl font-extrabold text-white">{formatMoney(plan.price)}</span>
                    <span className="pb-1 text-sm text-slate-300">{plan.price === null ? '' : '/ month'}</span>
                </div>

                {plan.price_annual && (
                    <p className="mb-5 text-xs text-emerald-300">Annual: {formatMoney(plan.price_annual)} / month billed yearly</p>
                )}

                <div className="mb-5 grid grid-cols-2 gap-3 text-xs">
                    <div className="rounded-xl border border-slate-700 bg-slate-950/60 p-3">
                        <p className="text-slate-400">Domains</p>
                        <p className="mt-1 font-semibold text-slate-100">{formatLimit(plan?.limits?.domains_max_active)}</p>
                    </div>
                    <div className="rounded-xl border border-slate-700 bg-slate-950/60 p-3">
                        <p className="text-slate-400">Projects</p>
                        <p className="mt-1 font-semibold text-slate-100">{formatLimit(plan?.limits?.projects)}</p>
                    </div>
                    <div className="rounded-xl border border-slate-700 bg-slate-950/60 p-3">
                        <p className="text-slate-400">Monthly Actions</p>
                        <p className="mt-1 font-semibold text-slate-100">{formatLimit(plan?.limits?.monthly_actions)}</p>
                    </div>
                    <div className="rounded-xl border border-slate-700 bg-slate-950/60 p-3">
                        <p className="text-slate-400">Team Seats</p>
                        <p className="mt-1 font-semibold text-slate-100">{formatLimit(plan?.limits?.team_seats)}</p>
                    </div>
                </div>
            </div>

            <div className={isWide ? 'xl:flex xl:flex-col xl:justify-between' : ''}>
                <div>
                    <div className="mb-6">
                        <p className="mb-2 text-sm font-semibold text-slate-200">Backlink Types</p>
                        <div className="flex flex-wrap gap-2">
                            {(plan.backlink_types || []).map((type) => (
                                <span key={`${plan.id}-${type}`} className="rounded-full border border-cyan-400/30 bg-cyan-500/10 px-2.5 py-1 text-xs text-cyan-200 capitalize">
                                    {type}
                                </span>
                            ))}
                        </div>
                    </div>

                    <div className="mb-6">
                        <p className="mb-2 text-sm font-semibold text-slate-200">What You Get</p>
                        <ul className={`text-sm text-slate-300 ${isWide ? 'grid gap-x-6 gap-y-2 md:grid-cols-2' : 'space-y-2'}`}>
                            {(plan.features || []).slice(0, 6).map((feature, idx) => (
                                <li key={`${plan.id}-f-${idx}`} className="flex items-start gap-2">
                                    <span className="mt-1 h-2 w-2 rounded-full bg-emerald-400"></span>
                                    <span>{feature}</span>
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>

                <div className="flex gap-2">
                    {isAuthenticated ? (
                        <a href={`/subscription/checkout/${plan.id}`} className="flex-1">
                            <Button variant={highlighted ? 'primary' : 'outline'} className="w-full justify-center">
                                {plan.price === null ? 'Contact Sales' : plan.price === 0 ? 'Start Free' : 'Subscribe Now'}
                            </Button>
                        </a>
                    ) : (
                        <Link href="/register" className="flex-1">
                            <Button variant={highlighted ? 'primary' : 'outline'} className="w-full justify-center">
                                {plan.price === null ? 'Talk to Sales' : plan.price === 0 ? 'Sign Up Free' : 'Get Started'}
                            </Button>
                        </Link>
                    )}

                    {plan.cta_secondary_href && plan.cta_secondary_label && (
                        <a href={plan.cta_secondary_href} className="flex-1">
                            <Button variant="secondary" className="w-full justify-center">
                                {plan.cta_secondary_label}
                            </Button>
                        </a>
                    )}
                </div>
            </div>
        </div>
    );
}

function PlansContent({ plans, isAuthenticated }) {
    return (
        <div className="space-y-8">
            <div className="rounded-2xl border border-slate-700/80 bg-gradient-to-r from-slate-900 via-slate-900 to-indigo-950/70 p-7">
                <p className="text-xs font-semibold tracking-[0.18em] text-indigo-300">PRICING</p>
                <h1 className="mt-2 text-3xl font-extrabold text-white">Choose A Plan That Fits Your Workflow</h1>
                <p className="mt-2 max-w-3xl text-sm text-slate-300">
                    Each plan shows detailed limits, backlink types, and automation capacity so users can compare clearly.
                </p>
            </div>

            {plans.length > 0 ? (
                <div className="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                    {plans.map((plan) => {
                        const isWide = plan.price === null || plan.slug === 'custom';

                        return (
                            <PlanCard
                                key={plan.id}
                                plan={plan}
                                isAuthenticated={isAuthenticated}
                                isWide={isWide}
                            />
                        );
                    })}
                </div>
            ) : (
                <div className="rounded-2xl border border-slate-700 bg-slate-900/70 p-10 text-center">
                    <p className="text-lg font-semibold text-white">No active plans found.</p>
                    <p className="mt-2 text-sm text-slate-300">Please add or activate plans from admin panel.</p>
                </div>
            )}
        </div>
    );
}

export default function Plans({ plans = [], user = null }) {
    const page = usePage();
    const authUser = page?.props?.auth?.user || user;
    const isAuthenticated = !!authUser;

    return (
        <>
            <Head title="Plans - Backlink Pro" />

            {isAuthenticated ? (
                <AppLayout header="Plans">
                    <PlansContent plans={plans || []} isAuthenticated={isAuthenticated} />
                </AppLayout>
            ) : (
                <div className="min-h-screen bg-slate-950 p-6 md:p-10">
                    <div className="mx-auto max-w-7xl">
                        <div className="mb-6 flex items-center justify-between">
                            <Link href="/" className="text-lg font-bold text-white">Backlink Pro</Link>
                            <div className="flex gap-2">
                                <Link href="/login"><Button variant="outline">Login</Button></Link>
                                <Link href="/register"><Button variant="primary">Sign Up</Button></Link>
                            </div>
                        </div>
                        <PlansContent plans={plans || []} isAuthenticated={false} />
                    </div>
                </div>
            )}
        </>
    );
}
