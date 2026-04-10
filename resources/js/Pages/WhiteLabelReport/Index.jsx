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

export default function WhiteLabelReportIndex({
    organization = null,
    canUseWhiteLabel = false,
    upgradeUrl = '/plans',
    settings,
    defaultSettings,
    reportHighlights = [],
    setupSteps = [],
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

    const formLocked = !organization || !canUseWhiteLabel;
    const previewTitle = form.data.use_custom_cover_title
        ? form.data.custom_cover_title.trim()
        : `${form.data.company_name || organization?.name || 'Your Company'} SEO Report`;
    const previewFooter = form.data.footer_text.trim()
        || `${form.data.company_name || organization?.name || 'Your company'} client reporting`;
    const previewWebsite = form.data.website.trim() || 'Website not set yet';
    const previewModeLabel = form.data.enabled ? 'Client-ready white label mode' : 'White label disabled';
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
                                                disabled={formLocked}
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
                                        disabled={formLocked}
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
                                        disabled={formLocked}
                                    />
                                    <div className="md:col-span-2">
                                        <label className="mb-2 block text-sm font-medium text-[var(--admin-text)]">Logo Upload</label>
                                        <div className="rounded-2xl border border-dashed border-[rgba(255,110,64,0.18)] bg-[rgba(255,247,242,0.03)] p-4">
                                            <input
                                                ref={fileInputRef}
                                                type="file"
                                                accept="image/*"
                                                onChange={onLogoChange}
                                                disabled={formLocked}
                                                className="block w-full text-sm text-[rgba(255,240,232,0.7)] file:mr-4 file:rounded-xl file:border-0 file:bg-[rgba(255,110,64,0.14)] file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-[#fff7f2] hover:file:bg-[rgba(255,110,64,0.2)]"
                                            />
                                            <p className="mt-3 text-xs text-[rgba(255,240,232,0.56)]">Accepted: JPG, PNG, WEBP, SVG up to 2MB.</p>
                                            {form.errors.logo && <p className="mt-2 text-sm text-[#F04438]">{form.errors.logo}</p>}
                                            {(logoPreviewUrl || settings.logo_url) && (
                                                <button
                                                    type="button"
                                                    onClick={removeCurrentLogo}
                                                    disabled={formLocked}
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
                                                                disabled={formLocked}
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
                                                    disabled={formLocked}
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
                                        disabled={formLocked || !form.data.use_custom_cover_title}
                                    />
                                </div>

                                <div>
                                    <label className="mb-2 block text-sm font-medium text-[var(--admin-text)]">Footer Text</label>
                                    <textarea
                                        name="footer_text"
                                        value={form.data.footer_text}
                                        onChange={(event) => form.setData('footer_text', event.target.value)}
                                        rows={4}
                                        disabled={formLocked}
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
                                    <Button type="submit" variant="primary" size="lg" disabled={form.processing || formLocked} className="rounded-2xl px-6">
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

                            <div className="overflow-hidden rounded-[28px] border border-[rgba(255,110,64,0.16)] bg-[#120f0f]">
                                <div className="border-b border-[rgba(255,255,255,0.06)] px-6 py-5" style={{ background: 'linear-gradient(135deg, rgba(255,110,64,0.18), transparent 60%)' }}>
                                    <div className="flex flex-wrap items-center justify-between gap-4">
                                        <div className="flex items-center gap-4">
                                            {renderLogoPreview()}
                                            <div>
                                                <div className="text-sm uppercase tracking-[0.18em] text-[rgba(255,240,232,0.46)]">Report Header</div>
                                                <div className="mt-2 text-2xl font-semibold text-[#fff7f2]">{previewTitle}</div>
                                            </div>
                                        </div>
                                        <div className="rounded-2xl border border-[rgba(255,255,255,0.08)] bg-[rgba(255,255,255,0.03)] px-4 py-3 text-sm text-[rgba(255,240,232,0.7)]">
                                            {previewModeLabel}
                                        </div>
                                    </div>
                                </div>

                                <div className="space-y-4 px-6 py-6">
                                    <div
                                        className="rounded-3xl border p-5"
                                        style={{
                                            borderColor: 'rgba(255,110,64,0.18)',
                                            background: 'linear-gradient(180deg, rgba(255,110,64,0.10), rgba(255,255,255,0.02))',
                                        }}
                                    >
                                        <div className="text-xs uppercase tracking-[0.18em] text-[rgba(255,240,232,0.46)]">Sample Report Header Card</div>
                                        <div className="mt-3 text-lg font-semibold text-[#fff7f2]">{brandingSummary}</div>
                                        <p className="mt-2 text-sm leading-6 text-[rgba(255,240,232,0.68)]">
                                            Company identity, cover title and selected SEO focus areas update from the white label form in real time.
                                        </p>
                                    </div>

                                    <div className="rounded-3xl border border-[rgba(255,255,255,0.08)] bg-[rgba(255,255,255,0.03)] p-5">
                                        <div className="text-xs uppercase tracking-[0.18em] text-[rgba(255,240,232,0.46)]">Client Report Scope</div>
                                        <div className="mt-5 rounded-2xl border border-[rgba(255,255,255,0.06)] bg-[#0f0c0c] p-4">
                                            <div className="flex flex-wrap items-start justify-between gap-3">
                                                <div>
                                                    <div className="text-sm font-semibold text-[#fff7f2]">{brandingSummary}</div>
                                                    <div className="mt-1 text-sm text-[rgba(255,240,232,0.58)]">{previewWebsite}</div>
                                                </div>
                                                <div className="rounded-full border border-[rgba(255,110,64,0.18)] bg-[rgba(255,110,64,0.08)] px-3 py-1 text-xs font-semibold text-[#ffcfb9]">
                                                    {previewFocusSections.reduce((total, group) => total + group.selectedItems.length, 0)} focus points
                                                </div>
                                            </div>
                                            <div className="mt-4 grid gap-4 md:grid-cols-3">
                                                {previewFocusSections.length > 0 ? previewFocusSections.map((group) => (
                                                    <div key={group.key} className="rounded-2xl border border-[rgba(255,110,64,0.12)] bg-[rgba(255,247,242,0.03)] p-4">
                                                        <div className="text-sm font-semibold text-[#fff7f2]">{group.title}</div>
                                                        <div className="mt-3 space-y-2">
                                                            {group.selectedItems.map((item) => (
                                                                <div key={item.key} className="flex items-center gap-2 text-sm text-[rgba(255,240,232,0.68)]">
                                                                    <i className="bi bi-check-circle-fill text-[#ff9f7f]"></i>
                                                                    <span>{item.label}</span>
                                                                </div>
                                                            ))}
                                                        </div>
                                                    </div>
                                                )) : (
                                                    <div className="rounded-2xl border border-dashed border-[rgba(255,110,64,0.16)] bg-[rgba(255,247,242,0.02)] p-4 text-sm text-[rgba(255,240,232,0.58)] md:col-span-3">
                                                        No SEO focus sections selected yet. Choose On Page, Off Page, or Technical SEO items from the form.
                                                    </div>
                                                )}
                                            </div>
                                            <p className="mt-4 text-sm leading-6 text-[rgba(255,240,232,0.68)]">{previewFooter}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </Card>

                        <Card className="border border-[rgba(255,110,64,0.18)] bg-[linear-gradient(180deg,rgba(22,18,18,0.94),rgba(10,10,10,0.98))]" variant="ghost">
                            <p className="text-xs font-semibold uppercase tracking-[0.24em] text-[rgba(255,240,232,0.58)]">Quick Setup</p>
                            <div className="mt-5 space-y-3">
                                {setupSteps.map((step, index) => (
                                    <div key={step} className="flex items-start gap-3 rounded-2xl border border-[rgba(255,110,64,0.14)] bg-[rgba(255,247,242,0.03)] p-4">
                                        <div className="flex h-8 w-8 items-center justify-center rounded-full bg-[rgba(255,110,64,0.14)] text-sm font-semibold text-[#ffcfb9]">
                                            {index + 1}
                                        </div>
                                        <p className="pt-1 text-sm leading-6 text-[rgba(255,240,232,0.72)]">{step}</p>
                                    </div>
                                ))}
                            </div>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
