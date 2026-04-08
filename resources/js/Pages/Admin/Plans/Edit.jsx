import AdminLayout from '@/Components/Layout/AdminLayout';
import { Link, useForm } from '@inertiajs/react';

export default function AdminPlansEdit({ plan }) {
    const { data, setData, put, processing, errors } = useForm({
        name: plan.name || '',
        code: plan.code || '',
        tagline: plan.tagline || '',
        price_monthly: plan.price_monthly ?? '',
        price_annual: plan.price_annual ?? '',
        stripe_price_id_monthly: plan.stripe_price_id_monthly || '',
        stripe_price_id_yearly: plan.stripe_price_id_yearly || '',
        is_active: !!plan.is_active,
        is_public: !!plan.is_public,
        is_highlighted: !!plan.is_highlighted,
        badge: plan.badge || '',
        sort_order: plan.sort_order ?? 0,
        display_limits: Array.isArray(plan.display_limits) ? plan.display_limits : [],
        includes: Array.isArray(plan.includes) ? plan.includes : [],
        limits_json: plan.limits_json || {},
        features_json: plan.features_json || {},
        cta_primary_label: plan.cta_primary_label || 'Subscribe Now',
        cta_primary_href: plan.cta_primary_href || '/plans',
        cta_secondary_label: plan.cta_secondary_label || '',
        cta_secondary_href: plan.cta_secondary_href || '',
    });

    const submit = (e) => {
        e.preventDefault();
        put(`/admin/plans/${plan.id}`);
    };

    const setDisplayLimit = (index, key, value) => {
        const next = [...data.display_limits];
        next[index] = { ...next[index], [key]: value };
        setData('display_limits', next);
    };

    const setInclude = (index, value) => {
        const next = [...data.includes];
        next[index] = value;
        setData('includes', next);
    };

    const setLimitValue = (key, value) => {
        setData('limits_json', {
            ...data.limits_json,
            [key]: Number(value) || 0,
        });
    };

    return (
        <AdminLayout header={`Edit ${plan.name}`}>
            <form onSubmit={submit} className="mx-auto max-w-5xl space-y-6">
                <section className="rounded-2xl border border-[var(--admin-border)] bg-[var(--admin-surface)] p-6">
                    <div className="flex items-center justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold text-[var(--admin-text)]">Stripe checkout plan</h1>
                            <p className="mt-1 text-sm text-[var(--admin-text-muted)]">Update pricing, Stripe IDs, and customer-facing plan details.</p>
                        </div>
                        <Link href="/admin/plans" className="rounded-lg border border-[var(--admin-border)] px-4 py-2 text-sm text-[var(--admin-text)]">
                            Back
                        </Link>
                    </div>
                </section>

                <section className="grid gap-6 lg:grid-cols-2">
                    <div className="rounded-2xl border border-[var(--admin-border)] bg-[var(--admin-surface)] p-6 space-y-4">
                        <h2 className="text-lg font-semibold text-[var(--admin-text)]">Plan basics</h2>
                        <Field label="Plan name" error={errors.name}>
                            <input className={inputClass(errors.name)} value={data.name} onChange={(e) => setData('name', e.target.value)} />
                        </Field>
                        <Field label="Code" error={errors.code}>
                            <input className={inputClass(errors.code)} value={data.code} onChange={(e) => setData('code', e.target.value.toLowerCase().replace(/\s+/g, '-'))} />
                        </Field>
                        <Field label="Tagline" error={errors.tagline}>
                            <textarea className={inputClass(errors.tagline)} rows="3" value={data.tagline} onChange={(e) => setData('tagline', e.target.value)} />
                        </Field>
                        <div className="grid grid-cols-2 gap-4">
                            <Field label="Monthly price ($)" error={errors.price_monthly}>
                                <input type="number" step="0.01" min="0" className={inputClass(errors.price_monthly)} value={data.price_monthly} onChange={(e) => setData('price_monthly', e.target.value)} />
                            </Field>
                            <Field label="Yearly price ($)" error={errors.price_annual}>
                                <input type="number" step="0.01" min="0" className={inputClass(errors.price_annual)} value={data.price_annual} onChange={(e) => setData('price_annual', e.target.value)} />
                            </Field>
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <Toggle label="Active" checked={data.is_active} onChange={(checked) => setData('is_active', checked)} />
                            <Toggle label="Public" checked={data.is_public} onChange={(checked) => setData('is_public', checked)} />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <Toggle label="Highlighted" checked={data.is_highlighted} onChange={(checked) => setData('is_highlighted', checked)} />
                            <Field label="Badge" error={errors.badge}>
                                <input className={inputClass(errors.badge)} value={data.badge} onChange={(e) => setData('badge', e.target.value)} />
                            </Field>
                        </div>
                    </div>

                    <div className="rounded-2xl border border-[var(--admin-border)] bg-[var(--admin-surface)] p-6 space-y-4">
                        <h2 className="text-lg font-semibold text-[var(--admin-text)]">Stripe mapping</h2>
                        <Field label="Monthly Stripe Price ID" error={errors.stripe_price_id_monthly}>
                            <input className={inputClass(errors.stripe_price_id_monthly)} value={data.stripe_price_id_monthly} onChange={(e) => setData('stripe_price_id_monthly', e.target.value)} />
                        </Field>
                        <Field label="Yearly Stripe Price ID" error={errors.stripe_price_id_yearly}>
                            <input className={inputClass(errors.stripe_price_id_yearly)} value={data.stripe_price_id_yearly} onChange={(e) => setData('stripe_price_id_yearly', e.target.value)} />
                        </Field>
                        <div className="rounded-xl border border-slate-600/50 bg-slate-950/40 p-4 text-sm text-slate-300">
                            Stripe Product ke andar jo recurring monthly aur yearly prices bante hain, un ke `price_...` IDs yahan paste karne hain.
                        </div>
                    </div>
                </section>

                <section className="grid gap-6 lg:grid-cols-2">
                    <div className="rounded-2xl border border-[var(--admin-border)] bg-[var(--admin-surface)] p-6 space-y-4">
                        <h2 className="text-lg font-semibold text-[var(--admin-text)]">Visible limits</h2>
                        {data.display_limits.map((item, index) => (
                            <div key={index} className="grid grid-cols-2 gap-3">
                                <input className={inputClass()} value={item.label} onChange={(e) => setDisplayLimit(index, 'label', e.target.value)} />
                                <input className={inputClass()} value={item.value} onChange={(e) => setDisplayLimit(index, 'value', e.target.value)} />
                            </div>
                        ))}
                        <h3 className="pt-2 text-sm font-semibold text-[var(--admin-text)]">Internal limits</h3>
                        <div className="grid grid-cols-2 gap-3">
                            <NumberField label="Projects" value={data.limits_json.projects} onChange={(v) => setLimitValue('projects', v)} />
                            <NumberField label="Monthly actions" value={data.limits_json.monthly_actions} onChange={(v) => setLimitValue('monthly_actions', v)} />
                            <NumberField label="Team seats" value={data.limits_json.team_seats} onChange={(v) => setLimitValue('team_seats', v)} />
                            <NumberField label="Domains" value={data.limits_json['domains.max_active']} onChange={(v) => setLimitValue('domains.max_active', v)} />
                            <NumberField label="Audit runs" value={data.limits_json['audits.runs_per_month']} onChange={(v) => setLimitValue('audits.runs_per_month', v)} />
                            <NumberField label="Automation jobs" value={data.limits_json['automation.jobs_per_month']} onChange={(v) => setLimitValue('automation.jobs_per_month', v)} />
                        </div>
                    </div>

                    <div className="rounded-2xl border border-[var(--admin-border)] bg-[var(--admin-surface)] p-6 space-y-4">
                        <h2 className="text-lg font-semibold text-[var(--admin-text)]">Checkout copy</h2>
                        {data.includes.map((item, index) => (
                            <Field key={index} label={`Feature ${index + 1}`}>
                                <input className={inputClass()} value={item} onChange={(e) => setInclude(index, e.target.value)} />
                            </Field>
                        ))}
                        <div className="grid grid-cols-2 gap-4">
                            <Field label="Primary CTA" error={errors.cta_primary_label}>
                                <input className={inputClass(errors.cta_primary_label)} value={data.cta_primary_label} onChange={(e) => setData('cta_primary_label', e.target.value)} />
                            </Field>
                            <Field label="Primary link" error={errors.cta_primary_href}>
                                <input className={inputClass(errors.cta_primary_href)} value={data.cta_primary_href} onChange={(e) => setData('cta_primary_href', e.target.value)} />
                            </Field>
                        </div>
                    </div>
                </section>

                <div className="flex items-center justify-end gap-3 pb-6">
                    <Link href="/admin/plans" className="rounded-lg border border-[var(--admin-border)] px-4 py-2 text-sm text-[var(--admin-text)]">
                        Cancel
                    </Link>
                    <button type="submit" disabled={processing} className="rounded-lg bg-[#2F6BFF] px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-60">
                        {processing ? 'Saving...' : 'Update Plan'}
                    </button>
                </div>
            </form>
        </AdminLayout>
    );
}

function Field({ label, error, children }) {
    return (
        <label className="block space-y-1.5">
            <span className="text-sm font-medium text-[var(--admin-text)]">{label}</span>
            {children}
            {error ? <span className="block text-xs text-red-500">{error}</span> : null}
        </label>
    );
}

function Toggle({ label, checked, onChange }) {
    return (
        <label className="flex items-center gap-3 rounded-xl border border-[var(--admin-border)] px-4 py-3">
            <input type="checkbox" checked={checked} onChange={(e) => onChange(e.target.checked)} />
            <span className="text-sm text-[var(--admin-text)]">{label}</span>
        </label>
    );
}

function NumberField({ label, value, onChange }) {
    return (
        <label className="block space-y-1.5">
            <span className="text-xs font-medium text-[var(--admin-text-muted)]">{label}</span>
            <input type="number" className={inputClass()} value={value ?? ''} onChange={(e) => onChange(e.target.value)} />
        </label>
    );
}

function inputClass(hasError) {
    return `w-full rounded-xl border bg-[var(--admin-surface-2,var(--admin-hover-bg))] px-3 py-2.5 text-sm text-[var(--admin-text)] outline-none ${hasError ? 'border-red-500' : 'border-[var(--admin-border)]'}`;
}
