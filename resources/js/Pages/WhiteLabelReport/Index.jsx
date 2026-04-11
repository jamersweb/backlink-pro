import { useEffect, useMemo, useRef, useState } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import AppLayout from '../../Components/Layout/AppLayout';
import Card from '../../Components/Shared/Card';
import Button from '../../Components/Shared/Button';
import Input from '../../Components/Shared/Input';

const SECTION_GROUPS = [
    {
        key: 'on_page',
        title: 'On Page SEO',
        description: 'Choose the main on-page checkpoints you want included in the report.',
        items: [
            { key: 'title_optimization', label: 'Title Optimization' },
            { key: 'meta_descriptions', label: 'Meta Descriptions' },
            { key: 'heading_structure', label: 'Heading Structure' },
            { key: 'content_quality', label: 'Content Quality' },
            { key: 'internal_linking', label: 'Internal Linking' },
        ],
    },
    {
        key: 'off_page',
        title: 'Off Page SEO',
        description: 'Control the backlink and authority areas clients should receive.',
        items: [
            { key: 'backlink_quality', label: 'Backlink Quality' },
            { key: 'referring_domains', label: 'Referring Domains' },
            { key: 'anchor_text_profile', label: 'Anchor Text Profile' },
            { key: 'link_velocity', label: 'Link Velocity' },
        ],
    },
    {
        key: 'technical_seo',
        title: 'Technical SEO',
        description: 'Add the technical checks that should appear in white label delivery.',
        items: [
            { key: 'crawlability', label: 'Crawlability' },
            { key: 'indexability', label: 'Indexability' },
            { key: 'pagespeed', label: 'PageSpeed' },
            { key: 'structured_data', label: 'Structured Data' },
            { key: 'mobile_usability', label: 'Mobile Usability' },
        ],
    },
];

const cloneReportSections = (sections = {}) => JSON.parse(JSON.stringify(sections));

const DEMO_SCORE_RING = {
    circumference: 282.7,
    offset: 19.7,
};

const DIAGNOSTIC_METRICS = [
    { label: 'Performance', value: 98, tone: 'emerald' },
    { label: 'Accessibility', value: 90, tone: 'emerald' },
    { label: 'Best Practices', value: 75, tone: 'orange' },
    { label: 'SEO Score', value: 100, tone: 'emerald' },
];

const ISSUE_ROWS = [
    { title: 'Duplicate Meta Descriptions', affected: '1,340', severity: 'critical', action: 'Meta refresh' },
    { title: 'Large Image Payloads', affected: '42', severity: 'warning', action: 'Compress' },
    { title: 'Broken Outbound Links', affected: '12', severity: 'medium', action: 'Redirect' },
];

const INSIGHT_CARDS = [
    {
        title: 'Semantic Gap Opportunity',
        body: 'Competitor topics show a 22% faster uplift when supporting entities and FAQ depth are introduced.',
        tone: 'primary',
    },
    {
        title: 'Natural Growth Detected',
        body: 'Branded search demand is trending upward, creating a stronger base for off-page amplification.',
        tone: 'neutral',
    },
];

export default function WhiteLabelReportIndex({
    organization = null,
    canUseWhiteLabel = false,
    upgradeUrl = '/plans',
    settings,
    defaultSettings,
    reportHighlights = [],
}) {
    const { flash } = usePage().props;
    const fileInputRef = useRef(null);
    const [logoPreviewUrl, setLogoPreviewUrl] = useState(settings.logo_url);

    const form = useForm({
        enabled: settings.enabled ?? false,
        company_name: settings.company_name ?? '',
        logo: null,
        remove_logo: false,
        website: settings.website ?? '',
        footer_text: settings.footer_text ?? '',
        report_sections: cloneReportSections(settings.report_sections ?? defaultSettings.report_sections),
        use_custom_cover_title: settings.use_custom_cover_title ?? false,
        custom_cover_title: settings.custom_cover_title ?? '',
    });

    useEffect(() => () => {
        if (logoPreviewUrl && logoPreviewUrl.startsWith('blob:')) {
            URL.revokeObjectURL(logoPreviewUrl);
        }
    }, [logoPreviewUrl]);

    const saveLocked = !organization || !canUseWhiteLabel;
    const controlsLocked = form.processing;
    const previewTitle = form.data.use_custom_cover_title
        ? form.data.custom_cover_title.trim()
        : `${form.data.company_name || organization?.name || 'Your Company'} SEO Report`;
    const previewFooter = form.data.footer_text.trim()
        || `${form.data.company_name || organization?.name || 'Your company'} client reporting`;
    const previewWebsite = form.data.website.trim() || 'Website not set yet';
    const statusHeading = form.data.enabled ? 'White label enabled' : 'Ready for setup';
    const previewFocusSections = SECTION_GROUPS.map((group) => ({
        ...group,
        selectedItems: group.items.filter((item) => form.data.report_sections?.[group.key]?.[item.key]),
    })).filter((group) => group.selectedItems.length > 0);

    const brandingSummary = useMemo(() => (
        form.data.company_name || organization?.name || 'Your Company'
    ), [form.data.company_name, organization?.name]);

    const submit = (event) => {
        event.preventDefault();

        if (saveLocked) {
            return;
        }

        form.transform((data) => ({
            ...data,
            enabled: data.enabled ? 1 : 0,
            remove_logo: data.remove_logo ? 1 : 0,
            use_custom_cover_title: data.use_custom_cover_title ? 1 : 0,
        }));

        form.put('/white-label-report', {
            forceFormData: form.data.logo instanceof File,
            preserveScroll: true,
            onSuccess: () => {
                form.setData('logo', null);
                form.setData('remove_logo', false);
            },
        });
    };

    const onLogoChange = (event) => {
        const file = event.target.files?.[0] ?? null;
        form.setData('logo', file);
        form.setData('remove_logo', false);

        if (logoPreviewUrl && logoPreviewUrl.startsWith('blob:')) {
            URL.revokeObjectURL(logoPreviewUrl);
        }

        setLogoPreviewUrl(file ? URL.createObjectURL(file) : settings.logo_url);
    };

    const resetToDefaults = () => {
        form.setData({
            enabled: defaultSettings.enabled,
            company_name: defaultSettings.company_name,
            logo: null,
            remove_logo: Boolean(settings.logo_url),
            website: defaultSettings.website,
            footer_text: defaultSettings.footer_text,
            report_sections: cloneReportSections(defaultSettings.report_sections),
            use_custom_cover_title: defaultSettings.use_custom_cover_title,
            custom_cover_title: defaultSettings.custom_cover_title,
        });
        form.clearErrors();

        if (logoPreviewUrl && logoPreviewUrl.startsWith('blob:')) {
            URL.revokeObjectURL(logoPreviewUrl);
        }

        setLogoPreviewUrl(null);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    const removeCurrentLogo = () => {
        form.setData('logo', null);
        form.setData('remove_logo', true);

        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }

        if (logoPreviewUrl && logoPreviewUrl.startsWith('blob:')) {
            URL.revokeObjectURL(logoPreviewUrl);
        }

        setLogoPreviewUrl(null);
    };

    const openLogoPicker = () => {
        if (!controlsLocked) {
            fileInputRef.current?.click();
        }
    };

    const renderLogoPreview = () => {
        if (logoPreviewUrl && !form.data.remove_logo) {
            return (
                <img
                    src={logoPreviewUrl}
                    alt="White label logo preview"
                    className="h-16 max-w-[220px] rounded-2xl object-contain"
                />
            );
        }

        return (
            <div className="rounded-2xl border border-[rgba(255,110,64,0.18)] bg-[rgba(255,247,242,0.04)] px-5 py-4 text-lg font-semibold text-[#fff7f2]">
                {brandingSummary}
            </div>
        );
    };

    return (
        <AppLayout
            header="White Label Report"
            subtitle="Manage the branding and section settings that power client-facing SEO report presentation."
        >
            <div className="space-y-6">
                {flash?.success && (
                    <div className="rounded-2xl border border-emerald-400/20 bg-emerald-500/10 px-5 py-4 text-sm text-emerald-200 shadow-lg shadow-emerald-950/20">
                        {flash.success}
                    </div>
                )}

                {(flash?.error || form.errors.organization || form.errors.plan) && (
                    <div className="rounded-2xl border border-rose-400/20 bg-rose-500/10 px-5 py-4 text-sm text-rose-200 shadow-lg shadow-rose-950/20">
                        {flash?.error || form.errors.organization || form.errors.plan}
                    </div>
                )}

                <Card className="overflow-hidden border border-[rgba(255,110,64,0.18)] bg-[radial-gradient(circle_at_top_left,rgba(255,110,64,0.12),transparent_30%),linear-gradient(180deg,rgba(22,18,18,0.94),rgba(10,10,10,0.98))] shadow-[0_24px_60px_rgba(0,0,0,0.24)]">
                    <div className="grid gap-6 lg:grid-cols-[1.2fr,0.8fr] lg:items-center">
                        <div>
                            <p className="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--admin-primary-light)]/80">Client Reporting</p>
                            <h2 className="mt-2 text-3xl font-semibold text-[#fff7f2]">Launch branded reports without breaking your dashboard flow</h2>
                            <p className="mt-3 max-w-2xl text-sm leading-6 text-[rgba(255,240,232,0.68)]">
                                Keep the existing premium reporting experience, but control how your company appears and which SEO sections are included inside branded report delivery.
                            </p>
                            <div className="mt-6 flex flex-wrap gap-3">
                                <Button
                                    variant="primary"
                                    size="lg"
                                    className="rounded-2xl px-6"
                                    onClick={() => document.getElementById('white-label-branding-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' })}
                                >
                                    <i className="bi bi-magic mr-2"></i>Start Branding Setup
                                </Button>
                                <Button
                                    variant="secondary"
                                    size="lg"
                                    className="rounded-2xl px-6"
                                    onClick={() => document.getElementById('white-label-preview-panel')?.scrollIntoView({ behavior: 'smooth', block: 'start' })}
                                >
                                    <i className="bi bi-eye mr-2"></i>Preview Report Theme
                                </Button>
                            </div>
                        </div>

                        <div className="grid gap-4">
                            <div className="rounded-3xl border border-[rgba(255,110,64,0.18)] bg-[rgba(255,247,242,0.04)] p-5">
                                <div className="text-xs uppercase tracking-[0.18em] text-[rgba(255,240,232,0.46)]">Brand Status</div>
                                <div className="mt-3 text-2xl font-semibold text-[#fff7f2]">{statusHeading}</div>
                                <p className="mt-2 text-sm text-[rgba(255,240,232,0.62)]">
                                    {organization ? `Workspace: ${organization.name}` : 'Create or join a workspace to unlock brand-level report settings.'}
                                </p>
                            </div>
                            <div className="rounded-3xl border border-[rgba(255,110,64,0.14)] bg-[linear-gradient(180deg,rgba(255,110,64,0.09),rgba(255,110,64,0.03))] p-5">
                                <div className="text-xs uppercase tracking-[0.18em] text-[rgba(255,240,232,0.46)]">Use Case</div>
                                <div className="mt-3 text-lg font-semibold text-[#fff7f2]">Agency-friendly reporting</div>
                                <p className="mt-2 text-sm text-[rgba(255,240,232,0.62)]">Perfect for sending polished backlinks and SEO updates under your own brand identity.</p>
                            </div>
                        </div>
                    </div>
                </Card>

                {!organization && (
                    <Card className="border border-[rgba(255,110,64,0.18)] bg-[linear-gradient(180deg,rgba(22,18,18,0.94),rgba(10,10,10,0.98))]" variant="ghost">
                        <div className="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--admin-primary-light)]/80">Workspace Required</p>
                                <h3 className="mt-2 text-2xl font-semibold text-[#fff7f2]">Create an organization before saving branding</h3>
                                <p className="mt-2 max-w-2xl text-sm leading-6 text-[rgba(255,240,232,0.64)]">
                                    White label settings are stored at the workspace level in this codebase, so you need an organization before branding can be persisted.
                                </p>
                            </div>
                            <Button href="/orgs/create" variant="primary" size="lg" className="rounded-2xl px-6">
                                <i className="bi bi-building-add mr-2"></i>Create Workspace
                            </Button>
                        </div>
                    </Card>
                )}

                {organization && !canUseWhiteLabel && (
                    <Card className="border border-[rgba(255,110,64,0.18)] bg-[linear-gradient(180deg,rgba(34,24,22,0.96),rgba(18,14,13,0.98))]" variant="ghost">
                        <div className="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--admin-primary-light)]/80">Plan Upgrade</p>
                                <h3 className="mt-2 text-2xl font-semibold text-[#fff7f2]">White label branding is gated on your current plan</h3>
                                <p className="mt-2 max-w-2xl text-sm leading-6 text-[rgba(255,240,232,0.64)]">
                                    This workspace is currently on the `{organization.plan_key || 'free'}` plan. Upgrade to Agency to save custom client branding for SEO reports.
                                </p>
                            </div>
                            <Button href={upgradeUrl} variant="primary" size="lg" className="rounded-2xl px-6">
                                <i className="bi bi-arrow-up-right-circle mr-2"></i>View Upgrade Options
                            </Button>
                        </div>
                    </Card>
                )}

                <div className="grid gap-6 xl:grid-cols-[1.15fr,0.85fr]">
                    <div className="space-y-6">
                        <Card className="border border-[rgba(255,110,64,0.18)] bg-[linear-gradient(180deg,rgba(22,18,18,0.94),rgba(10,10,10,0.98))]" variant="ghost">
                            <div id="white-label-branding-form" className="mb-6">
                                <p className="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--admin-primary-light)]/80">Branding Settings</p>
                                <h3 className="mt-2 text-2xl font-semibold text-[#fff7f2]">Configure your white label identity</h3>
                                <p className="mt-2 text-sm leading-6 text-[rgba(255,240,232,0.64)]">
                                    Everything below saves into workspace report settings and updates the preview panel instantly.
                                </p>
                            </div>

                            <form onSubmit={submit} encType="multipart/form-data" className="space-y-6">
                                <div className="rounded-2xl border border-[rgba(255,110,64,0.14)] bg-[rgba(255,247,242,0.03)] p-5">
                                    <label className="flex cursor-pointer items-start justify-between gap-4">
                                        <div>
                                            <div className="text-sm font-semibold text-[#fff7f2]">Enable White Label</div>
                                            <p className="mt-1 text-sm leading-6 text-[rgba(255,240,232,0.6)]">Turn on branded report presentation for this workspace.</p>
                                        </div>
                                        <span className="relative mt-1 inline-flex h-7 w-12 flex-shrink-0">
                                            <input
                                                type="checkbox"
                                                checked={form.data.enabled}
                                                onChange={(event) => form.setData('enabled', event.target.checked)}
                                                className="peer sr-only"
                                                disabled={controlsLocked}
                                            />
                                            <span className="absolute inset-0 rounded-full bg-[rgba(255,255,255,0.12)] transition peer-checked:bg-[var(--admin-primary)]"></span>
                                            <span className="absolute left-1 top-1 h-5 w-5 rounded-full bg-white transition peer-checked:translate-x-5"></span>
                                        </span>
                                    </label>
                                </div>

                                <div className="grid gap-5 md:grid-cols-2">
                                    <Input
                                        label="Company Name"
                                        name="company_name"
                                        value={form.data.company_name}
                                        onChange={(event) => form.setData('company_name', event.target.value)}
                                        error={form.errors.company_name}
                                        className="rounded-2xl border-[rgba(255,110,64,0.16)] bg-[rgba(255,247,242,0.03)]"
                                        helpText="Shown when no logo is uploaded and used in the preview header."
                                        disabled={controlsLocked}
                                    />
                                    <Input
                                        label="Website"
                                        name="website"
                                        type="url"
                                        value={form.data.website}
                                        onChange={(event) => form.setData('website', event.target.value)}
                                        error={form.errors.website}
                                        className="rounded-2xl border-[rgba(255,110,64,0.16)] bg-[rgba(255,247,242,0.03)]"
                                        helpText="Displayed in the footer branding area."
                                        disabled={controlsLocked}
                                    />
                                    <div className="md:col-span-2">
                                        <label className="mb-2 block text-sm font-medium text-[var(--admin-text)]">Logo Upload</label>
                                        <div className="rounded-2xl border border-dashed border-[rgba(255,110,64,0.18)] bg-[rgba(255,247,242,0.03)] p-4">
                                            <input
                                                ref={fileInputRef}
                                                type="file"
                                                accept="image/*"
                                                onChange={onLogoChange}
                                                disabled={controlsLocked}
                                                className="sr-only"
                                            />
                                            <div className="flex flex-wrap items-center gap-3">
                                                <Button
                                                    type="button"
                                                    variant="primary"
                                                    size="sm"
                                                    className="rounded-2xl px-4"
                                                    onClick={openLogoPicker}
                                                    disabled={controlsLocked}
                                                >
                                                    <i className="bi bi-images mr-2"></i>Choose Logo From Device
                                                </Button>
                                                <span className="text-sm text-[rgba(255,240,232,0.64)]">
                                                    {form.data.logo?.name || 'Gallery/files will open from your device'}
                                                </span>
                                            </div>
                                            <p className="mt-3 text-xs text-[rgba(255,240,232,0.56)]">Accepted: JPG, PNG, WEBP, SVG up to 2MB. Mobile par ye device gallery open karega.</p>
                                            {form.errors.logo && <p className="mt-2 text-sm text-[#F04438]">{form.errors.logo}</p>}
                                            {(logoPreviewUrl || settings.logo_url) && (
                                                <button
                                                    type="button"
                                                    onClick={removeCurrentLogo}
                                                    disabled={controlsLocked}
                                                    className="mt-4 text-sm font-medium text-[#ffcfb9] transition hover:text-[#fff7f2]"
                                                >
                                                    Remove current logo
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                </div>

                                <div className="space-y-4 rounded-3xl border border-[rgba(255,110,64,0.14)] bg-[rgba(255,247,242,0.03)] p-5">
                                    <div>
                                        <p className="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--admin-primary-light)]/80">Report Sections</p>
                                        <h4 className="mt-2 text-xl font-semibold text-[#fff7f2]">Choose what clients should receive</h4>
                                        <p className="mt-2 text-sm leading-6 text-[rgba(255,240,232,0.62)]">
                                            Select the SEO focus areas you want included when branded report delivery is prepared for users.
                                        </p>
                                    </div>

                                    <div className="grid gap-4">
                                        {SECTION_GROUPS.map((group) => (
                                            <div key={group.key} className="rounded-2xl border border-[rgba(255,110,64,0.12)] bg-[rgba(14,11,11,0.72)] p-4">
                                                <div className="flex items-start justify-between gap-4">
                                                    <div>
                                                        <h5 className="text-lg font-semibold text-[#fff7f2]">{group.title}</h5>
                                                        <p className="mt-1 text-sm leading-6 text-[rgba(255,240,232,0.58)]">{group.description}</p>
                                                    </div>
                                                    <span className="inline-flex rounded-full border border-[rgba(255,110,64,0.16)] px-3 py-1 text-xs font-semibold text-[#ffcfb9]">
                                                        {group.items.filter((item) => form.data.report_sections?.[group.key]?.[item.key]).length} selected
                                                    </span>
                                                </div>
                                                <div className="mt-4 grid gap-3 md:grid-cols-2">
                                                    {group.items.map((item) => (
                                                        <label
                                                            key={item.key}
                                                            className="flex cursor-pointer items-center gap-3 rounded-2xl border border-[rgba(255,110,64,0.12)] bg-[rgba(255,247,242,0.02)] px-4 py-3 transition hover:border-[rgba(255,110,64,0.22)]"
                                                        >
                                                            <input
                                                                type="checkbox"
                                                                checked={Boolean(form.data.report_sections?.[group.key]?.[item.key])}
                                                                onChange={(event) => form.setData('report_sections', {
                                                                    ...form.data.report_sections,
                                                                    [group.key]: {
                                                                        ...form.data.report_sections[group.key],
                                                                        [item.key]: event.target.checked,
                                                                    },
                                                                })}
                                                                className="h-4 w-4 rounded border-[rgba(255,110,64,0.3)] bg-transparent text-[var(--admin-primary)] focus:ring-[var(--admin-primary)]"
                                                                disabled={controlsLocked}
                                                            />
                                                            <span className="text-sm font-medium text-[rgba(255,240,232,0.84)]">{item.label}</span>
                                                        </label>
                                                    ))}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>

                                <div className="grid gap-5 lg:grid-cols-[0.8fr,1.2fr]">
                                    <div className="rounded-2xl border border-[rgba(255,110,64,0.14)] bg-[rgba(255,247,242,0.03)] p-5">
                                        <label className="flex cursor-pointer items-start justify-between gap-4">
                                            <div>
                                                <div className="text-sm font-semibold text-[#fff7f2]">Custom Cover Title</div>
                                                <p className="mt-1 text-sm leading-6 text-[rgba(255,240,232,0.6)]">Override the default title shown in the report header preview.</p>
                                            </div>
                                            <span className="relative mt-1 inline-flex h-7 w-12 flex-shrink-0">
                                                <input
                                                    type="checkbox"
                                                    checked={form.data.use_custom_cover_title}
                                                    onChange={(event) => form.setData('use_custom_cover_title', event.target.checked)}
                                                    className="peer sr-only"
                                                    disabled={controlsLocked}
                                                />
                                                <span className="absolute inset-0 rounded-full bg-[rgba(255,255,255,0.12)] transition peer-checked:bg-[var(--admin-primary)]"></span>
                                                <span className="absolute left-1 top-1 h-5 w-5 rounded-full bg-white transition peer-checked:translate-x-5"></span>
                                            </span>
                                        </label>
                                    </div>

                                    <Input
                                        label="Custom Cover Title"
                                        name="custom_cover_title"
                                        value={form.data.custom_cover_title}
                                        onChange={(event) => form.setData('custom_cover_title', event.target.value)}
                                        error={form.errors.custom_cover_title}
                                        className="rounded-2xl border-[rgba(255,110,64,0.16)] bg-[rgba(255,247,242,0.03)]"
                                        helpText="Required when custom cover title is enabled."
                                        disabled={controlsLocked || !form.data.use_custom_cover_title}
                                    />
                                </div>

                                <div>
                                    <label className="mb-2 block text-sm font-medium text-[var(--admin-text)]">Footer Text</label>
                                    <textarea
                                        name="footer_text"
                                        value={form.data.footer_text}
                                        onChange={(event) => form.setData('footer_text', event.target.value)}
                                        rows={4}
                                        disabled={controlsLocked}
                                        className="block min-h-[120px] w-full rounded-2xl border border-[rgba(255,110,64,0.16)] bg-[rgba(255,247,242,0.03)] px-4 py-3 text-base text-[var(--admin-text)] placeholder-[var(--admin-text-dim)] transition-all duration-200 focus:border-[#2F6BFF] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/20 disabled:cursor-not-allowed disabled:opacity-60"
                                        placeholder="Add a branded footer note for clients."
                                    />
                                    {form.errors.footer_text ? (
                                        <p className="mt-2 text-sm text-[#F04438]">{form.errors.footer_text}</p>
                                    ) : (
                                        <p className="mt-2 text-sm text-[var(--admin-text-dim)]">Used in the client branding footer area of the preview.</p>
                                    )}
                                </div>

                                <div className="flex flex-wrap items-center gap-3 border-t border-[rgba(255,110,64,0.12)] pt-6">
                                    <Button type="submit" variant="primary" size="lg" disabled={form.processing || saveLocked} className="rounded-2xl px-6">
                                        {form.processing ? 'Saving Branding...' : <><i className="bi bi-save mr-2"></i>Save Branding Settings</>}
                                    </Button>
                                    <Button type="button" variant="secondary" size="lg" disabled={form.processing} className="rounded-2xl px-6" onClick={resetToDefaults}>
                                        <i className="bi bi-arrow-counterclockwise mr-2"></i>Reset to Default
                                    </Button>
                                    <p className="text-sm text-[rgba(255,240,232,0.58)]">Reset restores form defaults locally. Click save to persist those defaults.</p>
                                </div>
                            </form>
                        </Card>

                        <Card className="border border-[rgba(255,110,64,0.18)] bg-[linear-gradient(180deg,rgba(22,18,18,0.94),rgba(10,10,10,0.98))]" variant="ghost">
                            <div className="mb-6">
                                <p className="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--admin-primary-light)]/80">Highlights</p>
                                <h3 className="mt-2 text-2xl font-semibold text-[#fff7f2]">What this section supports</h3>
                            </div>

                            <div className="grid gap-4">
                                {reportHighlights.map((item) => (
                                    <div key={item.title} className="rounded-2xl border border-[rgba(255,110,64,0.14)] bg-[rgba(255,247,242,0.03)] p-5">
                                        <div className="flex items-start gap-4">
                                            <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-[rgba(255,110,64,0.14)] text-[#ffcfb9]">
                                                <i className={`bi ${item.icon}`}></i>
                                            </div>
                                            <div>
                                                <div className="text-lg font-semibold text-[#fff7f2]">{item.title}</div>
                                                <p className="mt-2 text-sm leading-6 text-[rgba(255,240,232,0.64)]">{item.description}</p>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </Card>
                    </div>

                    <div className="space-y-6">
                        <Card className="border border-[rgba(255,110,64,0.18)] bg-[linear-gradient(180deg,rgba(22,18,18,0.94),rgba(10,10,10,0.98))]" variant="ghost">
                            <div id="white-label-preview-panel" className="mb-6 flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p className="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--admin-primary-light)]/80">Live Preview</p>
                                    <h3 className="mt-2 text-2xl font-semibold text-[#fff7f2]">Preview Report Theme</h3>
                                    <p className="mt-2 text-sm leading-6 text-[rgba(255,240,232,0.64)]">
                                        Reflects your current form state before branded report delivery is generated for users.
                                    </p>
                                </div>
                                <span className="inline-flex items-center rounded-full border border-[rgba(255,110,64,0.18)] bg-[rgba(255,110,64,0.12)] px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-[#ffcfb9]">
                                    {form.data.enabled ? 'Enabled' : 'Draft'}
                                </span>
                            </div>

                            <div className="overflow-hidden rounded-[28px] bg-[#131313]">
                                <div className="px-6 py-6 sm:px-8 lg:px-10">
                                    <div className="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-[24px] border border-[rgba(255,255,255,0.06)] bg-[rgba(255,255,255,0.02)] px-5 py-4">
                                        <div className="flex items-center gap-4">
                                            {renderLogoPreview()}
                                            <div>
                                                <div className="text-lg font-semibold tracking-[-0.02em] text-[#fff7f2]">{brandingSummary}</div>
                                                <div className="mt-1 font-mono text-[10px] uppercase tracking-[0.24em] text-[rgba(255,240,232,0.34)]">
                                                    {previewWebsite}
                                                </div>
                                            </div>
                                        </div>
                                        <div className="text-right">
                                            <div className="font-mono text-[10px] uppercase tracking-[0.28em] text-[#ff8d64]">Client SEO Report</div>
                                            <div className="mt-2 text-sm text-[rgba(255,240,232,0.58)]">{previewTitle}</div>
                                        </div>
                                    </div>

                                    <div className="mb-12 flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
                                        <div className="max-w-2xl">
                                            <span className="font-mono text-[11px] uppercase tracking-[0.35em] text-[#ff8d64]">Executive Summary</span>
                                            <h4 className="mt-4 text-5xl font-black leading-[0.92] tracking-[-0.04em] text-[#fff7f2] sm:text-6xl">
                                                Technical
                                                <br />
                                                <span className="text-[rgba(255,255,255,0.22)]">Audit</span> 2026
                                            </h4>
                                            <p className="mt-6 max-w-md text-sm leading-7 text-[rgba(255,240,232,0.50)]">
                                                Editorial demo report for {brandingSummary}. Your logo, title and company name appear live while the metrics below remain beautiful placeholders.
                                            </p>
                                        </div>
                                        <div className="flex flex-col gap-2 text-left font-mono text-[10px] uppercase tracking-[0.24em] text-[rgba(255,240,232,0.34)] lg:text-right">
                                            <span>{previewWebsite}</span>
                                            <span>{brandingSummary}</span>
                                            <span>{previewTitle}</span>
                                        </div>
                                    </div>

                                    <div className="grid gap-4 xl:grid-cols-[1.7fr,0.85fr]">
                                        <div className="relative overflow-hidden rounded-[26px] bg-[#1c1b1b] p-7">
                                            <div className="absolute inset-y-0 left-0 w-1 bg-[#ff5626]"></div>
                                            <div className="absolute -bottom-24 -right-16 h-56 w-56 rounded-full bg-[rgba(255,86,38,0.16)] blur-[100px]"></div>
                                            <div className="relative flex flex-col gap-8 md:flex-row md:items-center">
                                                <div className="relative h-52 w-52 flex-shrink-0">
                                                    <svg className="h-full w-full -rotate-90" viewBox="0 0 100 100">
                                                        <circle cx="50" cy="50" r="45" fill="transparent" stroke="#2a2a2a" strokeWidth="8"></circle>
                                                        <circle cx="50" cy="50" r="45" fill="transparent" stroke="#ff5626" strokeWidth="8" strokeDasharray={DEMO_SCORE_RING.circumference} strokeDashoffset={DEMO_SCORE_RING.offset} strokeLinecap="round"></circle>
                                                    </svg>
                                                    <div className="absolute inset-0 flex flex-col items-center justify-center">
                                                        <span className="text-5xl font-black tracking-[-0.04em] text-[#fff7f2]">93</span>
                                                        <span className="font-mono text-[10px] uppercase tracking-[0.28em] text-[#ff8d64]">Grade A</span>
                                                    </div>
                                                </div>
                                                <div className="relative z-10 flex-1">
                                                    <div className="flex flex-wrap items-center gap-4">
                                                        {renderLogoPreview()}
                                                        <div>
                                                            <div className="text-3xl font-bold tracking-[-0.03em] text-[#fff7f2]">Overall Health Score</div>
                                                            <div className="mt-2 text-sm text-[rgba(255,240,232,0.56)]">Live company identity preview in the report header.</div>
                                                        </div>
                                                    </div>
                                                    <p className="mt-6 max-w-lg text-sm leading-7 text-[rgba(255,240,232,0.58)]">
                                                        This premium demo block shows how the report opens with strong typography, a cinematic score ring and branded header treatment.
                                                    </p>
                                                    <div className="mt-6 grid grid-cols-2 gap-4">
                                                        <div className="rounded-2xl bg-[#201f1f] p-4"><span className="block font-mono text-[10px] uppercase tracking-[0.25em] text-[rgba(255,240,232,0.34)]">Crawl Capacity</span><span className="mt-2 block text-xl font-bold text-[#fff7f2]">98.2%</span></div>
                                                        <div className="rounded-2xl bg-[#201f1f] p-4"><span className="block font-mono text-[10px] uppercase tracking-[0.25em] text-[rgba(255,240,232,0.34)]">Indexability</span><span className="mt-2 block text-xl font-bold text-[#fff7f2]">100%</span></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div className="grid gap-4">
                                            <div className="rounded-[24px] bg-[#2a2a2a] p-6"><span className="block font-mono text-[10px] uppercase tracking-[0.24em] text-[rgba(255,240,232,0.34)]">Internal Links</span><div className="mt-3 flex items-center justify-between"><span className="text-4xl font-black tracking-[-0.04em] text-[#fff7f2]">12.4K</span><i className="bi bi-diagram-3-fill text-xl text-[#ff8d64]"></i></div></div>
                                            <div className="rounded-[24px] bg-[#2a2a2a] p-6"><span className="block font-mono text-[10px] uppercase tracking-[0.24em] text-[rgba(255,240,232,0.34)]">Domain Rating</span><div className="mt-3 flex items-center justify-between"><span className="text-4xl font-black tracking-[-0.04em] text-[#fff7f2]">74.1</span><i className="bi bi-star-fill text-xl text-[#ff8d64]"></i></div></div>
                                        </div>
                                    </div>

                                    <div className="mt-6 rounded-[26px] bg-[#1c1b1b] p-7">
                                        <div className="mb-8 flex flex-wrap items-start justify-between gap-4">
                                            <div>
                                                <h5 className="text-2xl font-bold tracking-[-0.03em] text-[#fff7f2]">Projected Visibility Growth</h5>
                                                <p className="mt-2 text-sm text-[rgba(255,240,232,0.48)]">Estimated traffic recovery bars styled like the revamp reference.</p>
                                            </div>
                                            <div className="flex gap-4 font-mono text-[10px] uppercase tracking-[0.24em] text-[rgba(255,240,232,0.4)]">
                                                <span className="flex items-center gap-2"><span className="h-2 w-2 rounded-full bg-[#ff5626]"></span>Current</span>
                                                <span className="flex items-center gap-2"><span className="h-2 w-2 rounded-full bg-[#6b625f]"></span>Projected</span>
                                            </div>
                                        </div>
                                        <div className="flex h-64 items-end gap-2">
                                            {[40, 44, 43, 55, 58, 61, 76, 84, 92, 100].map((value, index) => (
                                                <div
                                                    key={value}
                                                    className={`flex-1 rounded-t-[4px] ${index < 6 ? 'bg-[#201f1f]' : 'border-t-2 border-[#ff5626]'}`}
                                                    style={{ height: `${value}%`, backgroundColor: index < 6 ? '#201f1f' : `rgba(255,86,38,${0.18 + (index - 5) * 0.08})` }}
                                                ></div>
                                            ))}
                                        </div>
                                    </div>

                                    <div className="mt-6 grid gap-4 lg:grid-cols-[1.4fr,0.8fr]">
                                        <div className="rounded-[26px] bg-[#1c1b1b] p-7">
                                            <div className="mb-8 font-mono text-[10px] uppercase tracking-[0.32em] text-[rgba(255,240,232,0.34)]">Lighthouse Diagnostics</div>
                                            <div className="grid grid-cols-2 gap-6 xl:grid-cols-4">
                                                {DIAGNOSTIC_METRICS.map((metric) => (
                                                    <div key={metric.label} className="text-center">
                                                        <div className="relative mx-auto mb-4 h-24 w-24">
                                                            <svg className="h-full w-full -rotate-90" viewBox="0 0 36 36">
                                                                <circle cx="18" cy="18" r="16" fill="none" stroke="#2a2a2a" strokeWidth="3"></circle>
                                                                <circle cx="18" cy="18" r="16" fill="none" stroke={metric.tone === 'orange' ? '#ff5626' : '#22c55e'} strokeWidth="3" strokeDasharray="100" strokeDashoffset={100 - metric.value}></circle>
                                                            </svg>
                                                            <span className="absolute inset-0 flex items-center justify-center text-lg font-bold text-[#fff7f2]">{metric.value}</span>
                                                        </div>
                                                        <div className="text-[11px] font-bold uppercase tracking-[0.14em] text-[rgba(255,240,232,0.55)]">{metric.label}</div>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                        <div className="rounded-[26px] bg-[#2a2a2a] p-7">
                                            <div className="mb-6 font-mono text-[10px] uppercase tracking-[0.32em] text-[rgba(255,240,232,0.34)]">Status Ledger</div>
                                            <div className="space-y-5">
                                                {[
                                                    { label: 'HTTPS Protocol', state: 'Good', tone: '#22c55e' },
                                                    { label: 'XML Sitemap', state: 'Good', tone: '#22c55e' },
                                                    { label: 'Robots.txt', state: 'Stable', tone: '#22c55e' },
                                                    { label: 'Canonical Tags', state: 'Check', tone: '#f59e0b' },
                                                ].map((item) => (
                                                    <div key={item.label} className="flex items-center justify-between gap-3">
                                                        <span className="text-sm text-[rgba(255,240,232,0.72)]">{item.label}</span>
                                                        <span className="flex items-center gap-2 text-[11px] font-medium uppercase tracking-[0.16em]" style={{ color: item.tone }}>
                                                            <span className="h-2 w-2 rounded-full" style={{ backgroundColor: item.tone }}></span>
                                                            {item.state}
                                                        </span>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    </div>

                                    <div className="mt-6 rounded-[26px] bg-[#1c1b1b] p-7">
                                        <div className="mb-6 flex items-center justify-between gap-3">
                                            <div>
                                                <div className="font-mono text-[10px] uppercase tracking-[0.3em] text-[rgba(255,240,232,0.34)]">Top Issues By Severity</div>
                                                <div className="mt-2 text-2xl font-bold tracking-[-0.03em] text-[#fff7f2]">Audit issue summary</div>
                                            </div>
                                            <div className="rounded-full bg-[rgba(255,86,38,0.10)] px-3 py-1 font-mono text-[10px] uppercase tracking-[0.2em] text-[#ff8d64]">Demo findings</div>
                                        </div>
                                        <div className="overflow-hidden rounded-[20px] bg-[#151414]">
                                            <div className="grid grid-cols-[1.8fr,0.7fr,0.8fr,0.9fr] gap-3 px-5 py-3 font-mono text-[10px] uppercase tracking-[0.22em] text-[rgba(255,240,232,0.32)]">
                                                <span>Issue</span><span>Affected</span><span>Severity</span><span>Action</span>
                                            </div>
                                            {ISSUE_ROWS.map((row) => (
                                                <div key={row.title} className="grid grid-cols-[1.8fr,0.7fr,0.8fr,0.9fr] gap-3 border-t border-[rgba(255,255,255,0.04)] px-5 py-4">
                                                    <div><div className="text-sm font-semibold text-[#fff7f2]">{row.title}</div><div className="mt-1 text-xs text-[rgba(255,240,232,0.42)]">High-quality placeholder explanation for visual report structure.</div></div>
                                                    <div className="text-sm text-[rgba(255,240,232,0.72)]">{row.affected}</div>
                                                    <div><span className={`inline-flex rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.15em] ${row.severity === 'critical' ? 'bg-[rgba(239,68,68,0.16)] text-[#f87171]' : row.severity === 'warning' ? 'bg-[rgba(245,158,11,0.16)] text-[#fbbf24]' : 'bg-[rgba(96,165,250,0.16)] text-[#93c5fd]'}`}>{row.severity}</span></div>
                                                    <div className="text-sm text-[#ffb5a1]">{row.action}</div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>

                                    <div className="mt-6 grid gap-4 lg:grid-cols-2">
                                        {INSIGHT_CARDS.map((card) => (
                                            <div key={card.title} className={`relative overflow-hidden rounded-[24px] p-6 ${card.tone === 'primary' ? 'bg-[linear-gradient(135deg,#ff5626,#ff764d)] text-[#fff4ee]' : 'bg-[linear-gradient(135deg,#1c1b1b,#222222)] text-[#fff7f2]'}`}>
                                                <div className="relative">
                                                    <div className="text-xl font-bold tracking-[-0.03em]">{card.title}</div>
                                                    <p className={`mt-3 max-w-md text-sm leading-7 ${card.tone === 'primary' ? 'text-[#ffe0d4]' : 'text-[rgba(255,240,232,0.56)]'}`}>{card.body}</p>
                                                </div>
                                            </div>
                                        ))}
                                    </div>

                                    {previewFocusSections.length > 0 && (
                                        <div className="mt-6 grid gap-4">
                                            {previewFocusSections.map((group) => (
                                                <div key={group.key} className="rounded-[26px] bg-[#1c1b1b] p-7">
                                                    <div className="flex flex-wrap items-start justify-between gap-3">
                                                        <div>
                                                            <div className="font-mono text-[10px] uppercase tracking-[0.3em] text-[rgba(255,240,232,0.34)]">{group.title}</div>
                                                            <div className="mt-2 text-2xl font-bold tracking-[-0.03em] text-[#fff7f2]">Demo section with live-selected headings</div>
                                                        </div>
                                                        <div className="rounded-full bg-[rgba(255,86,38,0.10)] px-3 py-1 font-mono text-[10px] uppercase tracking-[0.2em] text-[#ff8d64]">{group.selectedItems.length} items</div>
                                                    </div>
                                                    <div className="mt-6 grid gap-4 lg:grid-cols-[1.2fr,0.8fr]">
                                                        <div className="rounded-[22px] bg-[#201f1f] p-5">
                                                            <div className="space-y-3">
                                                                {group.selectedItems.map((item) => (
                                                                    <div key={item.key} className="rounded-2xl bg-[#161515] px-4 py-3">
                                                                        <div className="flex items-center gap-3"><i className="bi bi-check-circle-fill text-[#ff8d64]"></i><span className="text-sm font-medium text-[#fff7f2]">{item.label}</span></div>
                                                                        <div className="mt-2 h-2.5 rounded-full bg-[rgba(255,255,255,0.06)]"><div className="h-2.5 rounded-full bg-[linear-gradient(90deg,#ff5626,#ff9c7c)]" style={{ width: `${62 + (item.label.length % 5) * 7}%` }}></div></div>
                                                                    </div>
                                                                ))}
                                                            </div>
                                                        </div>
                                                        <div className="rounded-[22px] bg-[#201f1f] p-5">
                                                            <div className="flex h-40 items-end gap-2">
                                                                {[34, 52, 48, 66, 74, 58].map((value) => (
                                                                    <div key={value} className="flex-1 rounded-t-[4px] bg-[linear-gradient(180deg,rgba(255,181,161,0.20),rgba(255,86,38,0.75))]" style={{ height: `${value}%` }}></div>
                                                                ))}
                                                            </div>
                                                            <div className="mt-4 space-y-2">
                                                                <div className="h-3 rounded-full bg-[rgba(255,255,255,0.07)]"></div>
                                                                <div className="h-3 w-4/5 rounded-full bg-[rgba(255,255,255,0.05)]"></div>
                                                                <div className="h-3 w-3/5 rounded-full bg-[rgba(255,255,255,0.04)]"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    )}

                                    <div className="mt-6 rounded-[26px] bg-[#1c1b1b] p-7">
                                        <div className="flex flex-wrap items-end justify-between gap-4">
                                            <div>
                                                <div className="font-mono text-[10px] uppercase tracking-[0.3em] text-[rgba(255,240,232,0.34)]">Report Closing Note</div>
                                                <div className="mt-2 text-2xl font-bold tracking-[-0.03em] text-[#fff7f2]">Branded ending block with your identity</div>
                                            </div>
                                            <div className="rounded-full bg-[rgba(255,86,38,0.10)] px-3 py-1 font-mono text-[10px] uppercase tracking-[0.2em] text-[#ff8d64]">
                                                Ready for export
                                            </div>
                                        </div>
                                        <div className="mt-6 grid gap-4 lg:grid-cols-[1.2fr,0.8fr]">
                                            <div className="rounded-[22px] bg-[#151414] p-5">
                                                <div className="text-sm leading-7 text-[rgba(255,240,232,0.60)]">
                                                    This footer-style report area stays inside the preview card, so the page ends neatly while still showing where your company name, support copy and branded sign-off would appear.
                                                </div>
                                                <div className="mt-5 h-px bg-[rgba(255,255,255,0.06)]"></div>
                                                <div className="mt-5 flex flex-wrap items-center justify-between gap-4">
                                                    <div>
                                                        <div className="text-base font-semibold text-[#fff7f2]">{brandingSummary}</div>
                                                        <div className="mt-1 text-sm text-[rgba(255,240,232,0.52)]">{previewFooter}</div>
                                                    </div>
                                                    {renderLogoPreview()}
                                                </div>
                                            </div>
                                            <div className="rounded-[22px] bg-[#201f1f] p-5">
                                                <div className="font-mono text-[10px] uppercase tracking-[0.24em] text-[rgba(255,240,232,0.34)]">Delivery Modules</div>
                                                <div className="mt-4 space-y-3">
                                                    {['Executive Summary', 'Priority Fixes', 'Section Snapshots', 'Performance Signals'].map((label) => (
                                                        <div key={label} className="flex items-center justify-between rounded-2xl bg-[#161515] px-4 py-3">
                                                            <span className="text-sm text-[#fff7f2]">{label}</span>
                                                            <i className="bi bi-check2-circle text-[#ff8d64]"></i>
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
