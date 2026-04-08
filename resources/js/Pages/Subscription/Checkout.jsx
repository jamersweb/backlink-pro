import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '../../Components/Layout/AppLayout';
import Button from '../../Components/Shared/Button';

function formatPrice(value) {
    if (value === null || value === undefined) {
        return 'Custom';
    }

    return `$${Number(value).toFixed(0)}`;
}

export default function Checkout({ plan, local_mode = false }) {
    const [interval, setInterval] = useState('monthly');
    const { post, processing } = useForm({ interval: 'monthly' });

    const hasStripePrice = interval === 'yearly'
        ? !!plan?.stripe_price_id_yearly
        : !!plan?.stripe_price_id_monthly;

    const isPaidPlan = !!plan?.is_paid;
    const canProceed = !isPaidPlan || hasStripePrice;

    const handleCheckout = (e) => {
        e.preventDefault();

        post(`/subscription/checkout/${plan.id}`, {
            data: { interval },
        });
    };

    return (
        <AppLayout header="Confirm Subscription">
            <Head title={`Checkout - ${plan?.name || 'Plan'}`} />

            <div className="mx-auto max-w-3xl">
                <div className="rounded-2xl border border-slate-700/80 bg-slate-900/70 p-7">
                    <div className="mb-6">
                        <p className="text-xs font-semibold tracking-[0.2em] text-indigo-300">PLAN CHECKOUT</p>
                        <h1 className="mt-2 text-3xl font-extrabold text-white">{plan?.name}</h1>
                        <p className="mt-2 text-sm text-slate-300">{plan?.tagline || 'Choose your billing interval and continue to secure checkout.'}</p>
                    </div>

                    {isPaidPlan ? (
                        <div className="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <button
                                type="button"
                                onClick={() => setInterval('monthly')}
                                className={`rounded-xl border px-4 py-3 text-left transition ${interval === 'monthly' ? 'border-indigo-400 bg-indigo-500/20 text-white' : 'border-slate-700 bg-slate-950/60 text-slate-300'}`}
                            >
                                <p className="text-xs uppercase tracking-wide">Monthly</p>
                                <p className="mt-1 text-2xl font-bold">{formatPrice(plan?.price_monthly)}</p>
                            </button>

                            <button
                                type="button"
                                onClick={() => setInterval('yearly')}
                                className={`rounded-xl border px-4 py-3 text-left transition ${interval === 'yearly' ? 'border-indigo-400 bg-indigo-500/20 text-white' : 'border-slate-700 bg-slate-950/60 text-slate-300'}`}
                            >
                                <p className="text-xs uppercase tracking-wide">Yearly</p>
                                <p className="mt-1 text-2xl font-bold">{formatPrice(plan?.price_annual || plan?.price_monthly)}</p>
                                <p className="text-xs text-emerald-300">Billed annually</p>
                            </button>
                        </div>
                    ) : (
                        <div className="mb-6 rounded-xl border border-slate-700 bg-slate-950/60 p-4 text-sm text-slate-200">
                            This plan uses custom pricing. Continue to contact sales.
                        </div>
                    )}

                    <div className="mb-6">
                        <p className="mb-2 text-sm font-semibold text-slate-200">Included Features</p>
                        <ul className="space-y-2 text-sm text-slate-300">
                            {(plan?.features || []).slice(0, 8).map((item, idx) => (
                                <li key={`${idx}-${item}`} className="flex items-start gap-2">
                                    <span className="mt-1 h-2 w-2 rounded-full bg-emerald-400"></span>
                                    <span>{item}</span>
                                </li>
                            ))}
                        </ul>
                    </div>

                    {isPaidPlan && !hasStripePrice && (
                        <div className="mb-5 rounded-xl border border-amber-500/40 bg-amber-500/10 p-4 text-sm text-amber-200">
                            This plan is visible, but Stripe price ID is missing for {interval}. Please contact support/admin to enable checkout.
                        </div>
                    )}

                    {isPaidPlan && local_mode && (
                        <div className="mb-5 rounded-xl border border-slate-600/60 bg-slate-950/70 p-4 text-sm text-slate-300">
                            Local mode is enabled, but paid plans still require Stripe pricing to be configured before checkout can continue.
                        </div>
                    )}

                    <form onSubmit={handleCheckout} className="flex flex-wrap gap-3">
                        {isPaidPlan ? (
                            <Button type="submit" variant="primary" disabled={processing || !canProceed}>
                                {processing ? 'Processing...' : 'Continue To Secure Checkout'}
                            </Button>
                        ) : (
                            <a href="/contact">
                                <Button type="button" variant="primary">Contact Sales</Button>
                            </a>
                        )}

                        <Link href="/plans">
                            <Button type="button" variant="outline">Back To Plans</Button>
                        </Link>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
