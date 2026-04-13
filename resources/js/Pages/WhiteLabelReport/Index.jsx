import { Link, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';
import AppLayout from '../../Components/Layout/AppLayout';
import Button from '../../Components/Shared/Button';
import Card from '../../Components/Shared/Card';
import Input from '../../Components/Shared/Input';
import BrandedAuditReportView from './BrandedAuditReportView';

const SECTION_GROUPS = [
    {
        key: 'on_page',
        title: 'On-Page SEO',
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
        title: 'Off-Page SEO',
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
        items: [
            { key: 'crawlability', label: 'Crawlability' },
            { key: 'indexability', label: 'Indexability' },
            { key: 'pagespeed', label: 'PageSpeed' },
            { key: 'structured_data', label: 'Structured Data' },
            { key: 'mobile_usability', label: 'Mobile Usability' },
        ],
    },
];

const emptyClient = {
    id: null,
    domain_id: '',
    client_name: '',
    client_website: '',
    client_company_info: '',
    report_title: '',
    reporting_period_start: '',
    reporting_period_end: '',
    target_keywords: '',
    notes: '',
    recommendations: '',
};

const cloneSections = (sections = {}) => JSON.parse(JSON.stringify(sections));

function formatDate(value) {
    if (!value) return 'Not set';

    try {
        return new Intl.DateTimeFormat('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
        }).format(new Date(value));
    } catch {
        return value;
    }
}

function TabLink({ href, active, icon, label }) {
    return (
        <Link
            href={href}
            className={`inline-flex items-center gap-2 rounded-full px-4 py-2.5 text-sm font-medium transition ${
                active
                    ? 'bg-[var(--admin-primary)] text-white shadow-lg shadow-[var(--admin-primary)]/20'
                    : 'bg-[rgba(255,255,255,0.04)] text-[rgba(255,240,232,0.72)] hover:bg-[rgba(255,255,255,0.08)]'
            }`}
        >
            <i className={`bi ${icon}`}></i>
            {label}
        </Link>
    );
}

function TextareaField({ label, value, onChange, error, disabled, rows = 5, placeholder = '' }) {
    return (
        <div>
            <label className="mb-2 block text-sm font-medium text-[var(--admin-text)]">{label}</label>
            <textarea
                value={value}
                onChange={onChange}
                rows={rows}
                disabled={disabled}
                placeholder={placeholder}
                className="min-h-[120px] w-full rounded-2xl border border-white/8 bg-[#111111] px-4 py-3 text-sm text-[var(--admin-text)] outline-none focus:border-[var(--admin-primary)]"
            />
            {error && <p className="mt-2 text-sm text-[#F04438]">{error}</p>}
        </div>
    );
}

function FieldHint({ children }) {
    return <p className="mt-2 text-xs text-[rgba(255,240,232,0.48)]">{children}</p>;
}

function SectionToggle({ title, description, checked, onChange, disabled }) {
    return (
        <label className="flex items-start justify-between gap-4 rounded-2xl border border-white/8 bg-[#111111] px-4 py-4">
            <div>
                <div className="text-sm font-medium text-[#fff7f2]">{title}</div>
                {description ? <p className="mt-1 text-sm text-[rgba(255,240,232,0.48)]">{description}</p> : null}
            </div>
            <span className="relative mt-1 inline-flex h-7 w-12 flex-shrink-0">
                <input type="checkbox" checked={checked} onChange={onChange} className="peer sr-only" disabled={disabled} />
                <span className="absolute inset-0 rounded-full bg-white/10 transition peer-checked:bg-[var(--admin-primary)]"></span>
                <span className="absolute left-1 top-1 h-5 w-5 rounded-full bg-white transition peer-checked:translate-x-5"></span>
            </span>
        </label>
    );
}

function EmptyState({ title, body, action }) {
    return (
        <Card className="border border-dashed border-white/10 bg-[#141414]">
            <div className="py-10 text-center">
                <div className="text-xl font-semibold text-[#fff7f2]">{title}</div>
                <p className="mx-auto mt-2 max-w-2xl text-sm leading-7 text-[rgba(255,240,232,0.54)]">{body}</p>
                {action ? <div className="mt-5">{action}</div> : null}
            </div>
        </Card>
    );
}

export default function WhiteLabelReportIndex(props) {
    const {
        organization = null,
        activeTab = 'branding',
        branding,
        defaultSettings,
        profiles = [],
        domains = [],
        reports = [],
        selectedReport = null,
        previewReport = null,
        profilesTableExists = true,
        reportsTableExists = true,
        tabLinks = {},
    } = props;

    const { flash, errors } = usePage().props;
    const fileInputRef = useRef(null);
    const [logoPreview, setLogoPreview] = useState(branding.logo_url);
    const [editingClientId, setEditingClientId] = useState(null);

    const hasWorkspace = Boolean(organization);
    const hasClients = profiles.length > 0;
    const hasReports = reports.length > 0;

    const brandingForm = useForm({
        enabled: branding.enabled ?? false,
        company_name: branding.company_name ?? '',
        logo: null,
        remove_logo: false,
        primary_color: branding.primary_color ?? defaultSettings.primary_color,
        secondary_color: branding.secondary_color ?? defaultSettings.secondary_color,
        website: branding.website ?? '',
        support_email: branding.support_email ?? '',
        support_phone: branding.support_phone ?? '',
        company_address: branding.company_address ?? '',
        footer_text: branding.footer_text ?? '',
        intro_text: branding.intro_text ?? '',
        outro_text: branding.outro_text ?? '',
        report_period_days: branding.report_period_days ?? defaultSettings.report_period_days,
        report_sections: cloneSections(branding.report_sections ?? defaultSettings.report_sections),
        use_custom_cover_title: branding.use_custom_cover_title ?? false,
        custom_cover_title: branding.custom_cover_title ?? '',
    });

    const clientForm = useForm(emptyClient);
    const generateForm = useForm({
        profile_id: selectedReport?.profile_id ?? profiles[0]?.id ?? '',
        report_title: selectedReport?.report_title ?? '',
        reporting_period_start: selectedReport?.reporting_period_start ?? profiles[0]?.reporting_period_start ?? '',
        reporting_period_end: selectedReport?.reporting_period_end ?? profiles[0]?.reporting_period_end ?? '',
    });

    useEffect(() => () => {
        if (logoPreview && logoPreview.startsWith('blob:')) {
            URL.revokeObjectURL(logoPreview);
        }
    }, [logoPreview]);

    const selectedProfileForGeneration = useMemo(
        () => profiles.find((profile) => String(profile.id) === String(generateForm.data.profile_id)) ?? null,
        [profiles, generateForm.data.profile_id],
    );

    useEffect(() => {
        if (selectedProfileForGeneration) {
            generateForm.setData((current) => ({
                ...current,
                report_title: current.report_title || selectedProfileForGeneration.report_title || '',
                reporting_period_start: current.reporting_period_start || selectedProfileForGeneration.reporting_period_start || '',
                reporting_period_end: current.reporting_period_end || selectedProfileForGeneration.reporting_period_end || '',
            }));
        }
    }, [selectedProfileForGeneration]);

    useEffect(() => {
        if (!flash?.success) return;

        if (activeTab === 'clients') {
            resetClientForm();
        }

        if (activeTab === 'branding') {
            brandingForm.setData('logo', null);
            brandingForm.setData('remove_logo', false);
        }
    }, [flash?.success, activeTab]);

    const previewBrandName = brandingForm.data.company_name || organization?.name || 'Your Brand';
    const canManageClients = hasWorkspace && profilesTableExists;
    const canGenerateReports = hasWorkspace && profilesTableExists && reportsTableExists && hasClients;

    const updateSectionValue = (groupKey, itemKey, checked) => {
        brandingForm.setData('report_sections', {
            ...brandingForm.data.report_sections,
            [groupKey]: {
                ...brandingForm.data.report_sections[groupKey],
                [itemKey]: checked,
            },
        });
    };

    const handleLogoChange = (event) => {
        const file = event.target.files?.[0] ?? null;
        brandingForm.setData('logo', file);
        brandingForm.setData('remove_logo', false);

        if (logoPreview && logoPreview.startsWith('blob:')) {
            URL.revokeObjectURL(logoPreview);
        }

        setLogoPreview(file ? URL.createObjectURL(file) : branding.logo_url);
    };

    const submitBranding = (event) => {
        event.preventDefault();

        brandingForm.transform((data) => ({
            ...data,
            enabled: data.enabled ? 1 : 0,
            remove_logo: data.remove_logo ? 1 : 0,
            use_custom_cover_title: data.use_custom_cover_title ? 1 : 0,
        }));

        brandingForm.put('/label/branding', {
            forceFormData: brandingForm.data.logo instanceof File,
            preserveScroll: true,
            onSuccess: () => {
                brandingForm.setData('logo', null);
                brandingForm.setData('remove_logo', false);
            },
        });
    };

    const resetClientForm = () => {
        setEditingClientId(null);
        clientForm.setData(emptyClient);
        clientForm.clearErrors();
    };

    const editClient = (profile) => {
        setEditingClientId(profile.id);
        clientForm.setData({
            id: profile.id,
            domain_id: profile.domain_id ?? '',
            client_name: profile.client_name ?? '',
            client_website: profile.client_website ?? '',
            client_company_info: profile.client_company_info ?? '',
            report_title: profile.report_title ?? '',
            reporting_period_start: profile.reporting_period_start ?? '',
            reporting_period_end: profile.reporting_period_end ?? '',
            target_keywords: profile.target_keywords ?? '',
            notes: profile.notes ?? '',
            recommendations: profile.recommendations ?? '',
        });
        clientForm.clearErrors();
    };

    const submitClient = (event) => {
        event.preventDefault();

        if (editingClientId) {
            clientForm.put(`/label/clients/${editingClientId}`, { preserveScroll: true });
            return;
        }

        clientForm.post('/label/clients', { preserveScroll: true });
    };

    const deleteClient = (profileId) => {
        if (!confirm('Delete this client profile?')) return;
        router.delete(`/label/clients/${profileId}`, { preserveScroll: true });
    };

    const generateReport = (event) => {
        event.preventDefault();
        generateForm.post('/label/reports/generate', { preserveScroll: true });
    };

    const deleteReport = (reportId) => {
        if (!confirm('Delete this generated report?')) return;
        router.delete(`/label/reports/${reportId}`, { preserveScroll: true });
    };

    const regenerateReport = (reportId) => {
        router.post(`/label/reports/${reportId}/regenerate`, {}, { preserveScroll: true });
    };

    const renderBrandingTab = () => (
        <div className="grid gap-6 xl:grid-cols-[1.1fr,0.9fr]">
            <Card className="border border-white/8 bg-[#141414]">
                <div className="mb-6">
                    <h3 className="text-2xl font-semibold tracking-[-0.03em] text-[#fff7f2]">Branding Settings</h3>
                    <p className="mt-2 text-sm text-[rgba(255,240,232,0.56)]">Configure the branding that appears across white-label reports, previews, and PDF exports.</p>
                </div>

                <form onSubmit={submitBranding} className="space-y-6">
                    <SectionToggle
                        title="Enable white-label mode"
                        description="Use this saved branding across generated reports and client-facing exports."
                        checked={brandingForm.data.enabled}
                        onChange={(event) => brandingForm.setData('enabled', event.target.checked)}
                        disabled={!hasWorkspace || brandingForm.processing}
                    />

                    <div className="grid gap-5 md:grid-cols-2">
                        <Input label="Brand / Company Name" name="company_name" value={brandingForm.data.company_name} onChange={(e) => brandingForm.setData('company_name', e.target.value)} error={brandingForm.errors.company_name} disabled={!hasWorkspace || brandingForm.processing} className="rounded-2xl border-white/8 bg-[#111111]" />
                        <Input label="Website URL" name="website" type="url" value={brandingForm.data.website} onChange={(e) => brandingForm.setData('website', e.target.value)} error={brandingForm.errors.website} disabled={!hasWorkspace || brandingForm.processing} className="rounded-2xl border-white/8 bg-[#111111]" />
                        <Input label="Primary Color" name="primary_color" value={brandingForm.data.primary_color} onChange={(e) => brandingForm.setData('primary_color', e.target.value)} error={brandingForm.errors.primary_color} disabled={!hasWorkspace || brandingForm.processing} className="rounded-2xl border-white/8 bg-[#111111]" />
                        <Input label="Secondary Color" name="secondary_color" value={brandingForm.data.secondary_color} onChange={(e) => brandingForm.setData('secondary_color', e.target.value)} error={brandingForm.errors.secondary_color} disabled={!hasWorkspace || brandingForm.processing} className="rounded-2xl border-white/8 bg-[#111111]" />
                        <Input label="Support Email" name="support_email" type="email" value={brandingForm.data.support_email} onChange={(e) => brandingForm.setData('support_email', e.target.value)} error={brandingForm.errors.support_email} disabled={!hasWorkspace || brandingForm.processing} className="rounded-2xl border-white/8 bg-[#111111]" />
                        <Input label="Support Phone" name="support_phone" value={brandingForm.data.support_phone} onChange={(e) => brandingForm.setData('support_phone', e.target.value)} error={brandingForm.errors.support_phone} disabled={!hasWorkspace || brandingForm.processing} className="rounded-2xl border-white/8 bg-[#111111]" />
                    </div>

                    <div>
                        <label className="mb-2 block text-sm font-medium text-[var(--admin-text)]">Logo Upload</label>
                        <div className="rounded-2xl border border-dashed border-white/10 bg-[#111111] p-4">
                            <input ref={fileInputRef} type="file" accept="image/png,image/jpeg,image/webp" className="hidden" onChange={handleLogoChange} />
                            <div className="flex flex-wrap items-center justify-between gap-4">
                                <div className="flex items-center gap-4">
                                    {logoPreview && !brandingForm.data.remove_logo ? (
                                        <img src={logoPreview} alt="Brand logo preview" className="h-16 max-w-[180px] rounded-2xl object-contain" />
                                    ) : (
                                        <div className="rounded-2xl bg-[#1c1c1c] px-4 py-4 text-sm font-semibold text-[#fff7f2]">{previewBrandName}</div>
                                    )}
                                    <div className="text-sm text-[rgba(255,240,232,0.52)]">PNG, JPG, or WEBP up to 2MB.</div>
                                </div>
                                <div className="flex flex-wrap gap-3">
                                    <Button type="button" variant="secondary" onClick={() => fileInputRef.current?.click()} disabled={!hasWorkspace || brandingForm.processing}>
                                        Upload Logo
                                    </Button>
                                    {logoPreview && (
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            onClick={() => {
                                                brandingForm.setData('logo', null);
                                                brandingForm.setData('remove_logo', true);
                                                setLogoPreview(null);
                                            }}
                                            disabled={!hasWorkspace || brandingForm.processing}
                                        >
                                            Remove Logo
                                        </Button>
                                    )}
                                </div>
                            </div>
                            {brandingForm.errors.logo && <p className="mt-3 text-sm text-[#F04438]">{brandingForm.errors.logo}</p>}
                        </div>
                    </div>

                    <div className="grid gap-5 md:grid-cols-2">
                        <TextareaField label="Company Address" value={brandingForm.data.company_address} onChange={(e) => brandingForm.setData('company_address', e.target.value)} error={brandingForm.errors.company_address} disabled={!hasWorkspace || brandingForm.processing} rows={4} />
                        <TextareaField label="Footer Text" value={brandingForm.data.footer_text} onChange={(e) => brandingForm.setData('footer_text', e.target.value)} error={brandingForm.errors.footer_text} disabled={!hasWorkspace || brandingForm.processing} rows={4} />
                    </div>

                    <div className="grid gap-5 md:grid-cols-2">
                        <TextareaField label="Report Intro Text" value={brandingForm.data.intro_text} onChange={(e) => brandingForm.setData('intro_text', e.target.value)} error={brandingForm.errors.intro_text} disabled={!hasWorkspace || brandingForm.processing} rows={5} placeholder="Optional opening copy for the executive summary section." />
                        <TextareaField label="Report Outro Text" value={brandingForm.data.outro_text} onChange={(e) => brandingForm.setData('outro_text', e.target.value)} error={brandingForm.errors.outro_text} disabled={!hasWorkspace || brandingForm.processing} rows={5} placeholder="Optional closing message before the footer branding block." />
                    </div>

                    <div className="grid gap-5 md:grid-cols-2">
                        <Input label="Default Report Period (days)" name="report_period_days" type="number" min="7" max="30" value={brandingForm.data.report_period_days} onChange={(e) => brandingForm.setData('report_period_days', e.target.value)} error={brandingForm.errors.report_period_days} disabled={!hasWorkspace || brandingForm.processing} className="rounded-2xl border-white/8 bg-[#111111]" />
                        <Input label="Custom Cover Title" name="custom_cover_title" value={brandingForm.data.custom_cover_title} onChange={(e) => brandingForm.setData('custom_cover_title', e.target.value)} error={brandingForm.errors.custom_cover_title} disabled={!hasWorkspace || brandingForm.processing || !brandingForm.data.use_custom_cover_title} className="rounded-2xl border-white/8 bg-[#111111]" />
                    </div>

                    <SectionToggle
                        title="Use custom cover title"
                        description="Override the default report title shown on generated reports and PDFs."
                        checked={brandingForm.data.use_custom_cover_title}
                        onChange={(event) => brandingForm.setData('use_custom_cover_title', event.target.checked)}
                        disabled={!hasWorkspace || brandingForm.processing}
                    />

                    <div>
                        <div className="mb-4">
                            <h4 className="text-lg font-semibold text-[#fff7f2]">Report Section Preferences</h4>
                            <p className="mt-1 text-sm text-[rgba(255,240,232,0.48)]">Control which SEO themes should be emphasized when reports are generated from live account data.</p>
                        </div>
                        <div className="grid gap-4 lg:grid-cols-3">
                            {SECTION_GROUPS.map((group) => (
                                <div key={group.key} className="rounded-2xl border border-white/8 bg-[#111111] p-4">
                                    <div className="mb-4 text-sm font-semibold text-[#fff7f2]">{group.title}</div>
                                    <div className="space-y-3">
                                        {group.items.map((item) => (
                                            <label key={item.key} className="flex items-center justify-between gap-3 text-sm text-[rgba(255,240,232,0.72)]">
                                                <span>{item.label}</span>
                                                <input
                                                    type="checkbox"
                                                    checked={Boolean(brandingForm.data.report_sections?.[group.key]?.[item.key])}
                                                    onChange={(event) => updateSectionValue(group.key, item.key, event.target.checked)}
                                                    disabled={!hasWorkspace || brandingForm.processing}
                                                />
                                            </label>
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="flex justify-end">
                        <Button type="submit" variant="primary" disabled={!hasWorkspace || brandingForm.processing}>
                            {brandingForm.processing ? 'Saving...' : 'Save Branding'}
                        </Button>
                    </div>
                </form>
            </Card>

            <div className="space-y-6">
                <Card className="overflow-hidden border border-white/8 bg-[#141414]">
                    <div className="px-6 py-5">
                        <div className="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--admin-primary-light)]/70">Brand Snapshot</div>
                        <h3 className="mt-3 text-2xl font-semibold tracking-[-0.03em] text-[#fff7f2]">{previewBrandName}</h3>
                    </div>
                    <div className="mx-6 mb-6 overflow-hidden rounded-[28px]" style={{ background: `linear-gradient(135deg, ${brandingForm.data.primary_color || '#FF5626'}, ${brandingForm.data.secondary_color || '#1C1B1B'})` }}>
                        <div className="space-y-6 px-6 py-7">
                            <div className="flex items-center justify-between gap-4">
                                <div>
                                    <div className="text-[11px] uppercase tracking-[0.24em] text-white/65">Preview Cover</div>
                                    <div className="mt-3 text-3xl font-semibold tracking-[-0.04em] text-white">{brandingForm.data.custom_cover_title || 'White-Label SEO Report'}</div>
                                </div>
                                {logoPreview && !brandingForm.data.remove_logo ? (
                                    <img src={logoPreview} alt="Brand preview logo" className="max-h-16 max-w-[160px] object-contain" />
                                ) : null}
                            </div>

                            <div className="grid gap-3 sm:grid-cols-2">
                                <div className="rounded-2xl bg-white/10 px-4 py-4 text-sm text-white/80">{brandingForm.data.website || 'Website URL will appear here.'}</div>
                                <div className="rounded-2xl bg-white/10 px-4 py-4 text-sm text-white/80">{brandingForm.data.support_email || 'Support email will appear here.'}</div>
                            </div>

                            <div className="rounded-2xl bg-white/10 px-4 py-4 text-sm leading-7 text-white/80">
                                {brandingForm.data.intro_text || 'Add a polished intro message for the opening summary of your reports.'}
                            </div>

                            <div className="rounded-2xl bg-white/10 px-4 py-4 text-sm leading-7 text-white/80">
                                {brandingForm.data.footer_text || 'Footer branding text will appear here once saved.'}
                            </div>
                        </div>
                    </div>
                </Card>

                <Card className="border border-white/8 bg-[#141414]">
                    <h3 className="text-xl font-semibold text-[#fff7f2]">Saved Clients</h3>
                    <p className="mt-2 text-sm text-[rgba(255,240,232,0.54)]">Quick glance at the client records available for report generation.</p>
                    <div className="mt-4 space-y-3">
                        {hasClients ? profiles.slice(0, 4).map((profile) => (
                            <div key={profile.id} className="rounded-2xl border border-white/8 bg-[#111111] px-4 py-4">
                                <div className="flex items-start justify-between gap-4">
                                    <div>
                                        <div className="text-sm font-medium text-[#fff7f2]">{profile.client_name}</div>
                                        <div className="mt-1 text-xs text-[rgba(255,240,232,0.44)]">{profile.client_website}</div>
                                    </div>
                                    <Button href={tabLinks.clients} variant="ghost" size="sm">Manage</Button>
                                </div>
                            </div>
                        )) : (
                            <div className="rounded-2xl border border-dashed border-white/10 bg-[#111111] px-4 py-6 text-sm text-[rgba(255,240,232,0.50)]">
                                No clients yet. Create your first client profile to start generating Label reports.
                            </div>
                        )}
                    </div>
                </Card>
            </div>
        </div>
    );
    const renderClientsTab = () => (
        <div className="grid gap-6 xl:grid-cols-[0.95fr,1.05fr]">
            <Card className="border border-white/8 bg-[#141414]">
                <div className="mb-6 flex items-start justify-between gap-4">
                    <div>
                        <h3 className="text-2xl font-semibold tracking-[-0.03em] text-[#fff7f2]">
                            {editingClientId ? 'Edit Client' : 'Create Client'}
                        </h3>
                        <p className="mt-2 text-sm text-[rgba(255,240,232,0.56)]">
                            Save client profiles once, then reuse them whenever you generate a branded SEO report.
                        </p>
                    </div>
                    {editingClientId ? (
                        <Button type="button" variant="ghost" onClick={resetClientForm}>
                            Cancel Edit
                        </Button>
                    ) : null}
                </div>

                {!hasWorkspace ? (
                    <EmptyState
                        title="Workspace required"
                        body="Create or join a workspace before managing Label clients."
                    />
                ) : !profilesTableExists ? (
                    <EmptyState
                        title="Clients setup required"
                        body="The client profiles table is not available yet. Run the latest migrations to enable client management."
                    />
                ) : (
                    <form onSubmit={submitClient} className="space-y-6">
                        <div className="grid gap-5 md:grid-cols-2">
                            <Input
                                label="Client Name"
                                name="client_name"
                                value={clientForm.data.client_name}
                                onChange={(e) => clientForm.setData('client_name', e.target.value)}
                                error={clientForm.errors.client_name}
                                disabled={clientForm.processing}
                                className="rounded-2xl border-white/8 bg-[#111111]"
                            />
                            <Input
                                label="Client Website / Domain"
                                name="client_website"
                                type="url"
                                value={clientForm.data.client_website}
                                onChange={(e) => clientForm.setData('client_website', e.target.value)}
                                error={clientForm.errors.client_website}
                                disabled={clientForm.processing}
                                className="rounded-2xl border-white/8 bg-[#111111]"
                            />
                        </div>

                        <div className="grid gap-5 md:grid-cols-2">
                            <div>
                                <label className="mb-2 block text-sm font-medium text-[var(--admin-text)]">Connected Domain</label>
                                <select
                                    value={clientForm.data.domain_id}
                                    onChange={(e) => clientForm.setData('domain_id', e.target.value)}
                                    disabled={clientForm.processing}
                                    className="w-full rounded-2xl border border-white/8 bg-[#111111] px-4 py-3 text-sm text-[var(--admin-text)] outline-none focus:border-[var(--admin-primary)]"
                                >
                                    <option value="">Use website only</option>
                                    {domains.map((domain) => (
                                        <option key={domain.id} value={domain.id}>
                                            {domain.name || domain.host || domain.url}
                                        </option>
                                    ))}
                                </select>
                                {clientForm.errors.domain_id && <p className="mt-2 text-sm text-[#F04438]">{clientForm.errors.domain_id}</p>}
                                <FieldHint>Select a connected domain if you want the report to pull matching SEO data automatically.</FieldHint>
                            </div>

                            <Input
                                label="Report Title"
                                name="report_title"
                                value={clientForm.data.report_title}
                                onChange={(e) => clientForm.setData('report_title', e.target.value)}
                                error={clientForm.errors.report_title}
                                disabled={clientForm.processing}
                                className="rounded-2xl border-white/8 bg-[#111111]"
                            />
                        </div>

                        <div className="grid gap-5 md:grid-cols-2">
                            <Input
                                label="Reporting Period Start"
                                name="reporting_period_start"
                                type="date"
                                value={clientForm.data.reporting_period_start}
                                onChange={(e) => clientForm.setData('reporting_period_start', e.target.value)}
                                error={clientForm.errors.reporting_period_start}
                                disabled={clientForm.processing}
                                className="rounded-2xl border-white/8 bg-[#111111]"
                            />
                            <Input
                                label="Reporting Period End"
                                name="reporting_period_end"
                                type="date"
                                value={clientForm.data.reporting_period_end}
                                onChange={(e) => clientForm.setData('reporting_period_end', e.target.value)}
                                error={clientForm.errors.reporting_period_end}
                                disabled={clientForm.processing}
                                className="rounded-2xl border-white/8 bg-[#111111]"
                            />
                        </div>

                        <div className="grid gap-5 md:grid-cols-2">
                            <TextareaField
                                label="Client Company Info"
                                value={clientForm.data.client_company_info}
                                onChange={(e) => clientForm.setData('client_company_info', e.target.value)}
                                error={clientForm.errors.client_company_info}
                                disabled={clientForm.processing}
                                rows={4}
                                placeholder="Optional address, contact details, or company context."
                            />
                            <TextareaField
                                label="Target Keywords"
                                value={clientForm.data.target_keywords}
                                onChange={(e) => clientForm.setData('target_keywords', e.target.value)}
                                error={clientForm.errors.target_keywords}
                                disabled={clientForm.processing}
                                rows={4}
                                placeholder="Example: seo audit, white label seo, backlink analysis"
                            />
                        </div>

                        <div className="grid gap-5 md:grid-cols-2">
                            <TextareaField
                                label="Notes / Custom Summary"
                                value={clientForm.data.notes}
                                onChange={(e) => clientForm.setData('notes', e.target.value)}
                                error={clientForm.errors.notes}
                                disabled={clientForm.processing}
                                rows={5}
                                placeholder="Opening summary, achievements, or client context."
                            />
                            <TextareaField
                                label="Recommendations / Next Steps"
                                value={clientForm.data.recommendations}
                                onChange={(e) => clientForm.setData('recommendations', e.target.value)}
                                error={clientForm.errors.recommendations}
                                disabled={clientForm.processing}
                                rows={5}
                                placeholder="Recommended actions and next milestones."
                            />
                        </div>

                        <div className="flex justify-end gap-3">
                            {editingClientId ? (
                                <Button type="button" variant="secondary" onClick={resetClientForm} disabled={clientForm.processing}>
                                    Cancel
                                </Button>
                            ) : null}
                            <Button type="submit" variant="primary" disabled={!canManageClients || clientForm.processing}>
                                {clientForm.processing ? 'Saving...' : editingClientId ? 'Update Client' : 'Save Client'}
                            </Button>
                        </div>
                    </form>
                )}
            </Card>

            <Card className="border border-white/8 bg-[#141414]">
                <div className="mb-6 flex items-start justify-between gap-4">
                    <div>
                        <h3 className="text-2xl font-semibold tracking-[-0.03em] text-[#fff7f2]">Clients</h3>
                        <p className="mt-2 text-sm text-[rgba(255,240,232,0.56)]">
                            Manage the client records that power your white-label reporting workflow.
                        </p>
                    </div>
                    <div className="rounded-full border border-white/8 bg-[#111111] px-3 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-[rgba(255,240,232,0.58)]">
                        {profiles.length} saved
                    </div>
                </div>

                {hasClients ? (
                    <div className="space-y-4">
                        {profiles.map((profile) => (
                            <div key={profile.id} className="rounded-[26px] border border-white/8 bg-[#111111] p-5">
                                <div className="flex flex-wrap items-start justify-between gap-4">
                                    <div>
                                        <div className="text-lg font-semibold text-[#fff7f2]">{profile.client_name}</div>
                                        <div className="mt-1 text-sm text-[rgba(255,240,232,0.48)]">{profile.client_website}</div>
                                        <div className="mt-3 flex flex-wrap gap-2">
                                            <span className="rounded-full bg-white/5 px-3 py-1 text-xs text-[rgba(255,240,232,0.70)]">
                                                {profile.report_title}
                                            </span>
                                            <span className="rounded-full bg-white/5 px-3 py-1 text-xs text-[rgba(255,240,232,0.70)]">
                                                {formatDate(profile.reporting_period_start)} - {formatDate(profile.reporting_period_end)}
                                            </span>
                                        </div>
                                    </div>

                                    <div className="flex flex-wrap gap-2">
                                        <Button href={profile.preview_url} variant="secondary" size="sm">
                                            Preview
                                        </Button>
                                        <Button type="button" variant="ghost" size="sm" onClick={() => editClient(profile)}>
                                            Edit
                                        </Button>
                                        <Button type="button" variant="ghost" size="sm" onClick={() => deleteClient(profile.id)}>
                                            Delete
                                        </Button>
                                    </div>
                                </div>

                                {profile.client_company_info ? (
                                    <p className="mt-4 rounded-2xl bg-[#171717] px-4 py-4 text-sm leading-7 text-[rgba(255,240,232,0.66)]">
                                        {profile.client_company_info}
                                    </p>
                                ) : null}
                            </div>
                        ))}
                    </div>
                ) : (
                    <EmptyState
                        title="No clients yet"
                        body="Create your first client profile here, then move to Reports to generate a polished white-label SEO report."
                    />
                )}
            </Card>
        </div>
    );

    const renderReportsTab = () => (
        <div className="space-y-6">
            <div className="grid gap-6 xl:grid-cols-[0.92fr,1.08fr]">
                <Card className="border border-white/8 bg-[#141414]">
                    <div className="mb-6 flex items-start justify-between gap-4">
                        <div>
                            <h3 className="text-2xl font-semibold tracking-[-0.03em] text-[#fff7f2]">Generate Report</h3>
                            <p className="mt-2 text-sm text-[rgba(255,240,232,0.56)]">
                                Pick a saved client and reporting period, then create a reusable white-label report snapshot.
                            </p>
                        </div>
                        <div className="rounded-full border border-white/8 bg-[#111111] px-3 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-[rgba(255,240,232,0.58)]">
                            Reports
                        </div>
                    </div>

                    {!hasWorkspace ? (
                        <EmptyState
                            title="Workspace required"
                            body="Create or join a workspace before generating Label reports."
                        />
                    ) : !profilesTableExists || !reportsTableExists ? (
                        <EmptyState
                            title="Reports setup required"
                            body="The Label report tables are not fully available yet. Run the latest migrations to enable report generation and storage."
                        />
                    ) : !hasClients ? (
                        <EmptyState
                            title="Create a client first"
                            body="Save at least one client in Label > Clients before generating a report."
                            action={<Button href={tabLinks.clients} variant="primary">Go to Clients</Button>}
                        />
                    ) : (
                        <form onSubmit={generateReport} className="space-y-5">
                            <div>
                                <label className="mb-2 block text-sm font-medium text-[var(--admin-text)]">Client</label>
                                <select
                                    value={generateForm.data.profile_id}
                                    onChange={(e) => generateForm.setData('profile_id', e.target.value)}
                                    disabled={generateForm.processing}
                                    className="w-full rounded-2xl border border-white/8 bg-[#111111] px-4 py-3 text-sm text-[var(--admin-text)] outline-none focus:border-[var(--admin-primary)]"
                                >
                                    <option value="">Select client</option>
                                    {profiles.map((profile) => (
                                        <option key={profile.id} value={profile.id}>
                                            {profile.client_name} - {profile.client_website}
                                        </option>
                                    ))}
                                </select>
                                {generateForm.errors.profile_id && <p className="mt-2 text-sm text-[#F04438]">{generateForm.errors.profile_id}</p>}
                            </div>

                            <Input
                                label="Report Title"
                                name="report_title"
                                value={generateForm.data.report_title}
                                onChange={(e) => generateForm.setData('report_title', e.target.value)}
                                error={generateForm.errors.report_title}
                                disabled={generateForm.processing}
                                className="rounded-2xl border-white/8 bg-[#111111]"
                            />

                            <div className="grid gap-5 md:grid-cols-2">
                                <Input
                                    label="Reporting Period Start"
                                    name="reporting_period_start"
                                    type="date"
                                    value={generateForm.data.reporting_period_start}
                                    onChange={(e) => generateForm.setData('reporting_period_start', e.target.value)}
                                    error={generateForm.errors.reporting_period_start}
                                    disabled={generateForm.processing}
                                    className="rounded-2xl border-white/8 bg-[#111111]"
                                />
                                <Input
                                    label="Reporting Period End"
                                    name="reporting_period_end"
                                    type="date"
                                    value={generateForm.data.reporting_period_end}
                                    onChange={(e) => generateForm.setData('reporting_period_end', e.target.value)}
                                    error={generateForm.errors.reporting_period_end}
                                    disabled={generateForm.processing}
                                    className="rounded-2xl border-white/8 bg-[#111111]"
                                />
                            </div>

                            {selectedProfileForGeneration ? (
                                <div className="rounded-[24px] border border-white/8 bg-[#111111] px-4 py-4">
                                    <div className="text-sm font-medium text-[#fff7f2]">{selectedProfileForGeneration.client_name}</div>
                                    <div className="mt-1 text-xs text-[rgba(255,240,232,0.48)]">{selectedProfileForGeneration.client_website}</div>
                                    {selectedProfileForGeneration.notes ? (
                                        <p className="mt-3 text-sm leading-7 text-[rgba(255,240,232,0.66)]">{selectedProfileForGeneration.notes}</p>
                                    ) : null}
                                </div>
                            ) : null}

                            <div className="flex justify-end">
                                <Button type="submit" variant="primary" disabled={!canGenerateReports || generateForm.processing}>
                                    {generateForm.processing ? 'Generating...' : 'Generate Report'}
                                </Button>
                            </div>
                        </form>
                    )}
                </Card>

                <Card className="border border-white/8 bg-[#141414]">
                    <div className="mb-6 flex items-start justify-between gap-4">
                        <div>
                            <h3 className="text-2xl font-semibold tracking-[-0.03em] text-[#fff7f2]">Saved Reports</h3>
                            <p className="mt-2 text-sm text-[rgba(255,240,232,0.56)]">
                                Open previous white-label reports, regenerate them with fresh data, or export the latest PDF.
                            </p>
                        </div>
                        <div className="rounded-full border border-white/8 bg-[#111111] px-3 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-[rgba(255,240,232,0.58)]">
                            {reports.length} stored
                        </div>
                    </div>

                    {hasReports ? (
                        <div className="space-y-4">
                            {reports.map((report) => {
                                const active = selectedReport?.id === report.id;

                                return (
                                    <div
                                        key={report.id}
                                        className={`rounded-[26px] border p-5 transition ${
                                            active
                                                ? 'border-[var(--admin-primary)] bg-[rgba(255,86,38,0.08)]'
                                                : 'border-white/8 bg-[#111111]'
                                        }`}
                                    >
                                        <div className="flex flex-wrap items-start justify-between gap-4">
                                            <div>
                                                <div className="text-lg font-semibold text-[#fff7f2]">{report.client_name}</div>
                                                <div className="mt-1 text-sm text-[rgba(255,240,232,0.48)]">{report.client_website}</div>
                                                <div className="mt-3 flex flex-wrap gap-2">
                                                    <span className="rounded-full bg-white/5 px-3 py-1 text-xs text-[rgba(255,240,232,0.70)]">
                                                        {report.report_title}
                                                    </span>
                                                    <span className="rounded-full bg-white/5 px-3 py-1 text-xs text-[rgba(255,240,232,0.70)]">
                                                        {formatDate(report.generated_at)}
                                                    </span>
                                                    <span className="rounded-full bg-white/5 px-3 py-1 text-xs uppercase text-[rgba(255,240,232,0.70)]">
                                                        {report.status}
                                                    </span>
                                                </div>
                                            </div>

                                            <div className="flex flex-wrap gap-2">
                                                <Button href={report.preview_url} variant="secondary" size="sm">
                                                    Preview
                                                </Button>
                                                <a
                                                    href={report.pdf_url}
                                                    className="inline-flex items-center justify-center rounded-lg px-3 py-1.5 text-sm font-semibold text-[var(--admin-text-muted)] transition hover:bg-[var(--admin-hover-bg)] hover:text-[var(--admin-text)]"
                                                >
                                                    Download PDF
                                                </a>
                                                <Button type="button" variant="ghost" size="sm" onClick={() => regenerateReport(report.id)}>
                                                    Regenerate
                                                </Button>
                                                <Button type="button" variant="ghost" size="sm" onClick={() => deleteReport(report.id)}>
                                                    Delete
                                                </Button>
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    ) : (
                        <EmptyState
                            title="No reports generated yet"
                            body="Use the form on the left to create your first saved Label report. It will appear here with preview and PDF actions."
                        />
                    )}
                </Card>
            </div>

            <Card className="border border-white/8 bg-[#141414]">
                <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h3 className="text-2xl font-semibold tracking-[-0.03em] text-[#fff7f2]">Report Preview</h3>
                        <p className="mt-2 text-sm text-[rgba(255,240,232,0.56)]">
                            Preview the selected report exactly as it will appear for clients and PDF export.
                        </p>
                    </div>
                    {selectedReport ? (
                        <div className="flex flex-wrap gap-3">
                            <a
                                href={selectedReport.pdf_url}
                                className="inline-flex items-center justify-center rounded-lg border border-[var(--admin-border)] bg-[var(--admin-surface-2)] px-5 py-2.5 text-sm font-semibold text-[var(--admin-text)] transition hover:bg-[var(--admin-surface-3)]"
                            >
                                Download PDF
                            </a>
                            <Button type="button" variant="ghost" onClick={() => regenerateReport(selectedReport.id)}>
                                Regenerate
                            </Button>
                        </div>
                    ) : null}
                </div>

                {previewReport ? (
                    <BrandedAuditReportView report={previewReport} />
                ) : (
                    <EmptyState
                        title="Choose a report to preview"
                        body="Open a saved report from the list above, or generate a new one to see the branded client-facing preview here."
                    />
                )}
            </Card>
        </div>
    );

    const tabContent = {
        branding: renderBrandingTab(),
        clients: renderClientsTab(),
        reports: renderReportsTab(),
    };

    return (
        <AppLayout
            header="Label"
            subtitle="Build your brand, manage clients, and deliver polished white-label SEO reports."
        >
            <div className="space-y-6">
                {flash?.success ? (
                    <div className="rounded-2xl border border-[#12B76A]/20 bg-[#12B76A]/10 px-4 py-4 text-sm text-[#D1FADF]">
                        {flash.success}
                    </div>
                ) : null}

                {flash?.error ? (
                    <div className="rounded-2xl border border-[#F04438]/20 bg-[#F04438]/10 px-4 py-4 text-sm text-[#FEE4E2]">
                        {flash.error}
                    </div>
                ) : null}

                {!hasWorkspace ? (
                    <div className="rounded-[32px] border border-[#F79009]/20 bg-[rgba(247,144,9,0.08)] px-6 py-5 text-sm text-[#FDE68A]">
                        Create or join a workspace to use the complete Label system.
                    </div>
                ) : null}

                {(errors?.message || errors?.error) ? (
                    <div className="rounded-2xl border border-[#F04438]/20 bg-[#F04438]/10 px-4 py-4 text-sm text-[#FEE4E2]">
                        {errors.message || errors.error}
                    </div>
                ) : null}

                <section className="overflow-hidden rounded-[34px] border border-white/8 bg-[radial-gradient(circle_at_top_left,rgba(255,86,38,0.22),transparent_38%),linear-gradient(180deg,#1B1412_0%,#141414_100%)] p-7 shadow-[0_24px_80px_rgba(0,0,0,0.28)]">
                    <div className="grid gap-6 xl:grid-cols-[1.15fr,0.85fr] xl:items-end">
                        <div>
                            <div className="text-xs font-semibold uppercase tracking-[0.28em] text-[var(--admin-primary-light)]/70">
                                White-Label SEO Reports
                            </div>
                            <h2 className="mt-4 max-w-3xl text-4xl font-semibold tracking-[-0.05em] text-[#fff7f2]">
                                Everything white-label now lives under Label
                            </h2>
                            <p className="mt-4 max-w-3xl text-base leading-8 text-[rgba(255,240,232,0.66)]">
                                Save your brand identity once, manage client-specific report profiles, generate polished reports,
                                preview them on-screen, and export client-ready PDFs from one clean workflow.
                            </p>
                        </div>

                        <div className="grid gap-3 sm:grid-cols-2">
                            <div className="rounded-[28px] border border-white/8 bg-white/5 px-5 py-5">
                                <div className="text-[11px] uppercase tracking-[0.22em] text-[rgba(255,240,232,0.45)]">Current Workspace</div>
                                <div className="mt-3 text-2xl font-semibold tracking-[-0.04em] text-[#fff7f2]">
                                    {organization?.name || 'Not connected'}
                                </div>
                            </div>
                            <div className="rounded-[28px] border border-white/8 bg-white/5 px-5 py-5">
                                <div className="text-[11px] uppercase tracking-[0.22em] text-[rgba(255,240,232,0.45)]">Saved Assets</div>
                                <div className="mt-3 text-2xl font-semibold tracking-[-0.04em] text-[#fff7f2]">
                                    {profiles.length} clients / {reports.length} reports
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <div className="flex flex-wrap gap-3">
                    <TabLink href={tabLinks.branding} active={activeTab === 'branding'} icon="bi-palette2" label="Branding Settings" />
                    <TabLink href={tabLinks.clients} active={activeTab === 'clients'} icon="bi-people" label="Clients" />
                    <TabLink href={tabLinks.reports} active={activeTab === 'reports'} icon="bi-file-earmark-bar-graph" label="Reports" />
                </div>

                {tabContent[activeTab] || renderBrandingTab()}
            </div>
        </AppLayout>
    );
}
