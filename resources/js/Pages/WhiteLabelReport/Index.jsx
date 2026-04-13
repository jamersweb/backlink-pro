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
        title: 'On Page SEO',
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

const blankReportProfile = {
    id: null,
    domain_id: '',
    client_name: '',
    client_website: '',
    report_title: '',
    reporting_period_start: '',
    reporting_period_end: '',
    target_keywords: '',
    notes: '',
    recommendations: '',
};

const cloneSections = (sections = {}) => JSON.parse(JSON.stringify(sections));

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

function SectionToggle({ title, description, checked, onChange, disabled }) {
    return (
        <label className="flex items-start justify-between gap-4 rounded-2xl border border-white/8 bg-[#111111] px-4 py-4">
            <div>
                <div className="text-sm font-medium text-[#fff7f2]">{title}</div>
                {description && <p className="mt-1 text-sm text-[rgba(255,240,232,0.48)]">{description}</p>}
            </div>
            <span className="relative mt-1 inline-flex h-7 w-12 flex-shrink-0">
                <input type="checkbox" checked={checked} onChange={onChange} className="peer sr-only" disabled={disabled} />
                <span className="absolute inset-0 rounded-full bg-white/10 transition peer-checked:bg-[var(--admin-primary)]"></span>
                <span className="absolute left-1 top-1 h-5 w-5 rounded-full bg-white transition peer-checked:translate-x-5"></span>
            </span>
        </label>
    );
}

function TextareaField({ label, value, onChange, error, disabled, rows = 5 }) {
    return (
        <div>
            <label className="mb-2 block text-sm font-medium text-[var(--admin-text)]">{label}</label>
            <textarea
                value={value}
                onChange={onChange}
                rows={rows}
                disabled={disabled}
                className="min-h-[140px] w-full rounded-2xl border border-white/8 bg-[#111111] px-4 py-3 text-sm text-[var(--admin-text)] outline-none focus:border-[var(--admin-primary)]"
            />
            {error && <p className="mt-2 text-sm text-[#F04438]">{error}</p>}
        </div>
    );
}

function DomainSelect({ domains, value, onChange, error, disabled }) {
    return (
        <div className="mb-5">
            <label className="mb-2 block text-sm font-medium text-[var(--admin-text)]">Linked Domain</label>
            <select
                value={value || ''}
                onChange={onChange}
                disabled={disabled}
                className="h-12 w-full rounded-2xl border border-white/8 bg-[#111111] px-4 text-sm text-[var(--admin-text)] outline-none focus:border-[var(--admin-primary)]"
            >
                <option value="">Match from website automatically</option>
                {domains.map((domain) => (
                    <option key={domain.id} value={domain.id}>
                        {domain.name} ({domain.host || domain.url})
                    </option>
                ))}
            </select>
            {error && <p className="mt-2 text-sm text-[#F04438]">{error}</p>}
        </div>
    );
}

export default function WhiteLabelReportIndex({
    organization = null,
    activeTab = 'branding',
    branding,
    defaultSettings,
    profiles = [],
    domains = [],
    selectedProfile = null,
    previewReport = null,
    tabLinks = {},
}) {
    const { flash, errors } = usePage().props;
    const fileInputRef = useRef(null);
    const [logoPreview, setLogoPreview] = useState(branding.logo_url);
    const [editingProfileId, setEditingProfileId] = useState(selectedProfile?.id ?? null);

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
        report_period_days: branding.report_period_days ?? defaultSettings.report_period_days,
        report_sections: cloneSections(branding.report_sections ?? defaultSettings.report_sections),
        use_custom_cover_title: branding.use_custom_cover_title ?? false,
        custom_cover_title: branding.custom_cover_title ?? '',
    });

    const profileForm = useForm(selectedProfile ?? blankReportProfile);

    useEffect(() => {
        if (selectedProfile) {
            profileForm.setData({
                id: selectedProfile.id,
                domain_id: selectedProfile.domain_id ?? '',
                client_name: selectedProfile.client_name ?? '',
                client_website: selectedProfile.client_website ?? '',
                report_title: selectedProfile.report_title ?? '',
                reporting_period_start: selectedProfile.reporting_period_start ?? '',
                reporting_period_end: selectedProfile.reporting_period_end ?? '',
                target_keywords: selectedProfile.target_keywords ?? '',
                notes: selectedProfile.notes ?? '',
                recommendations: selectedProfile.recommendations ?? '',
            });
            setEditingProfileId(selectedProfile.id);
        }
    }, [selectedProfile]);

    useEffect(() => () => {
        if (logoPreview && logoPreview.startsWith('blob:')) {
            URL.revokeObjectURL(logoPreview);
        }
    }, [logoPreview]);

    const hasWorkspace = Boolean(organization);
    const previewReady = Boolean(previewReport && selectedProfile);
    const previewBrandName = brandingForm.data.company_name || organization?.name || 'Your Brand';

    const profileSummary = useMemo(() => {
        if (!profileForm.data.client_name && !profileForm.data.report_title) {
            return 'Create a client report profile to generate a branded SEO report preview.';
        }

        return `${profileForm.data.client_name || 'Client'} | ${profileForm.data.report_title || 'Untitled report'}`;
    }, [profileForm.data.client_name, profileForm.data.report_title]);

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

    const startNewProfile = () => {
        setEditingProfileId(null);
        profileForm.setData(blankReportProfile);
        profileForm.clearErrors();
    };

    const editProfile = (profile) => {
        setEditingProfileId(profile.id);
        profileForm.setData({
            id: profile.id,
            domain_id: profile.domain_id ?? '',
            client_name: profile.client_name ?? '',
            client_website: profile.client_website ?? '',
            report_title: profile.report_title ?? '',
            reporting_period_start: profile.reporting_period_start ?? '',
            reporting_period_end: profile.reporting_period_end ?? '',
            target_keywords: profile.target_keywords ?? '',
            notes: profile.notes ?? '',
            recommendations: profile.recommendations ?? '',
        });
        profileForm.clearErrors();
    };

    const submitProfile = (event) => {
        event.preventDefault();

        const payload = {
            ...profileForm.data,
            domain_id: profileForm.data.domain_id || null,
        };

        if (editingProfileId) {
            profileForm.transform(() => payload);
            profileForm.put(`/label/reports/${editingProfileId}`, { preserveScroll: true });
            return;
        }

        profileForm.transform(() => payload);
        profileForm.post('/label/reports', { preserveScroll: true });
    };

    const deleteProfile = (profileId) => {
        if (!confirm('Delete this client report profile?')) return;
        router.delete(`/label/reports/${profileId}`, { preserveScroll: true });
    };

    const renderBrandingTab = () => (
        <div className="grid gap-6 xl:grid-cols-[1.15fr,0.85fr]">
            <Card className="border border-white/8 bg-[#141414]">
                <div className="mb-6">
                    <h3 className="text-2xl font-semibold tracking-[-0.03em] text-[#fff7f2]">Branding Settings</h3>
                    <p className="mt-2 text-sm text-[rgba(255,240,232,0.58)]">Save the brand identity used across white-label previews and PDF exports.</p>
                </div>

                <form onSubmit={submitBranding} className="space-y-6">
                    <SectionToggle
                        title="Enable white-label mode"
                        description="Turn on saved branding for client-facing report generation."
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
                        <TextareaField label="Custom Footer Text" value={brandingForm.data.footer_text} onChange={(e) => brandingForm.setData('footer_text', e.target.value)} error={brandingForm.errors.footer_text} disabled={!hasWorkspace || brandingForm.processing} rows={4} />
                        <TextareaField label="Address / Company Info" value={brandingForm.data.company_address} onChange={(e) => brandingForm.setData('company_address', e.target.value)} error={brandingForm.errors.company_address} disabled={!hasWorkspace || brandingForm.processing} rows={4} />
                    </div>

                    <div className="grid gap-5 md:grid-cols-2">
                        <Input label="Default Report Period (days)" name="report_period_days" type="number" min="7" max="30" value={brandingForm.data.report_period_days} onChange={(e) => brandingForm.setData('report_period_days', e.target.value)} error={brandingForm.errors.report_period_days} disabled={!hasWorkspace || brandingForm.processing} className="rounded-2xl border-white/8 bg-[#111111]" />
                        <Input label="Custom Cover Title" name="custom_cover_title" value={brandingForm.data.custom_cover_title} onChange={(e) => brandingForm.setData('custom_cover_title', e.target.value)} error={brandingForm.errors.custom_cover_title} disabled={!hasWorkspace || brandingForm.processing || !brandingForm.data.use_custom_cover_title} className="rounded-2xl border-white/8 bg-[#111111]" />
                    </div>

                    <SectionToggle
                        title="Use custom cover title"
                        checked={brandingForm.data.use_custom_cover_title}
                        onChange={(event) => brandingForm.setData('use_custom_cover_title', event.target.checked)}
                        disabled={!hasWorkspace || brandingForm.processing}
                    />

                    <div>
                        <div className="mb-4">
                            <h4 className="text-lg font-semibold text-[#fff7f2]">SEO Report Structure</h4>
                            <p className="mt-1 text-sm text-[rgba(255,240,232,0.48)]">Choose which saved sections should be emphasized in generated white-label reports.</p>
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

                    <div className="flex flex-wrap justify-end gap-3">
                        <Button type="submit" variant="primary" disabled={!hasWorkspace || brandingForm.processing}>
                            {brandingForm.processing ? 'Saving...' : 'Save Branding'}
                        </Button>
                    </div>
                </form>
            </Card>

            <div className="space-y-6">
                <Card className="border border-white/8 bg-[#141414]">
                    <h3 className="text-xl font-semibold text-[#fff7f2]">Current Branding Snapshot</h3>
                    <div className="mt-5 overflow-hidden rounded-[28px]" style={{ background: `linear-gradient(135deg, ${brandingForm.data.primary_color || '#FF5626'}, ${brandingForm.data.secondary_color || '#1C1B1B'})` }}>
                        <div className="space-y-6 px-6 py-7">
                            <div className="flex items-center justify-between gap-4">
                                <div>
                                    <div className="text-[11px] uppercase tracking-[0.24em] text-white/65">Brand Preview</div>
                                    <div className="mt-3 text-3xl font-semibold tracking-[-0.04em] text-white">{previewBrandName}</div>
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
                                {brandingForm.data.footer_text || 'Footer branding text will appear here once saved.'}
                            </div>
                        </div>
                    </div>
                </Card>

                <Card className="border border-white/8 bg-[#141414]">
                    <h3 className="text-xl font-semibold text-[#fff7f2]">Client Report Profiles</h3>
                    <p className="mt-2 text-sm text-[rgba(255,240,232,0.54)]">Recent profiles created in the Label section for this user.</p>
                    <div className="mt-4 space-y-3">
                        {profiles.length > 0 ? profiles.slice(0, 4).map((profile) => (
                            <div key={profile.id} className="rounded-2xl border border-white/8 bg-[#111111] px-4 py-4">
                                <div className="flex items-center justify-between gap-4">
                                    <div>
                                        <div className="text-sm font-medium text-[#fff7f2]">{profile.client_name}</div>
                                        <div className="mt-1 text-xs text-[rgba(255,240,232,0.44)]">{profile.report_title}</div>
                                    </div>
                                    <Button href={profile.preview_url} variant="ghost" size="sm">Preview</Button>
                                </div>
                            </div>
                        )) : (
                            <div className="rounded-2xl border border-dashed border-white/10 bg-[#111111] px-4 py-6 text-sm text-[rgba(255,240,232,0.50)]">
                                No client report profiles yet. Switch to Client Reports to create one.
                            </div>
                        )}
                    </div>
                </Card>
            </div>
        </div>
    );

    const renderReportsTab = () => (
        <div className="grid gap-6 xl:grid-cols-[0.9fr,1.1fr]">
            <Card className="border border-white/8 bg-[#141414]">
                <div className="flex items-center justify-between gap-4">
                    <div>
                        <h3 className="text-2xl font-semibold tracking-[-0.03em] text-[#fff7f2]">Client Reports</h3>
                        <p className="mt-2 text-sm text-[rgba(255,240,232,0.56)]">Create, edit, preview, and delete white-label report profiles.</p>
                    </div>
                    <Button type="button" variant="secondary" onClick={startNewProfile}>New Profile</Button>
                </div>

                <div className="mt-5 space-y-3">
                    {profiles.length > 0 ? profiles.map((profile) => (
                        <div key={profile.id} className={`rounded-2xl border px-4 py-4 ${editingProfileId === profile.id ? 'border-[var(--admin-primary)] bg-[rgba(47,107,255,0.08)]' : 'border-white/8 bg-[#111111]'}`}>
                            <div className="flex items-start justify-between gap-4">
                                <div>
                                    <div className="text-sm font-medium text-[#fff7f2]">{profile.client_name}</div>
                                    <div className="mt-1 text-xs text-[rgba(255,240,232,0.44)]">{profile.client_website}</div>
                                    <div className="mt-2 text-xs text-[rgba(255,240,232,0.44)]">{profile.report_title}</div>
                                </div>
                                <div className="flex gap-2">
                                    <button type="button" onClick={() => editProfile(profile)} className="rounded-full bg-white/6 px-3 py-2 text-xs text-[rgba(255,240,232,0.72)]">Edit</button>
                                    <Link href={profile.preview_url} className="rounded-full bg-white/6 px-3 py-2 text-xs text-[rgba(255,240,232,0.72)]">Preview</Link>
                                    <button type="button" onClick={() => deleteProfile(profile.id)} className="rounded-full bg-rose-500/10 px-3 py-2 text-xs text-rose-200">Delete</button>
                                </div>
                            </div>
                        </div>
                    )) : (
                        <div className="rounded-2xl border border-dashed border-white/10 bg-[#111111] px-4 py-8 text-sm text-[rgba(255,240,232,0.50)]">
                            No profiles created yet. Use the form to create your first client report profile.
                        </div>
                    )}
                </div>
            </Card>

            <Card className="border border-white/8 bg-[#141414]">
                <div className="mb-6">
                    <h3 className="text-2xl font-semibold tracking-[-0.03em] text-[#fff7f2]">
                        {editingProfileId ? 'Edit Client Report Profile' : 'Create Client Report Profile'}
                    </h3>
                    <p className="mt-2 text-sm text-[rgba(255,240,232,0.56)]">{profileSummary}</p>
                </div>

                <form onSubmit={submitProfile} className="space-y-5">
                    <div className="grid gap-5 md:grid-cols-2">
                        <Input label="Client Name" name="client_name" value={profileForm.data.client_name} onChange={(e) => profileForm.setData('client_name', e.target.value)} error={profileForm.errors.client_name || errors.client_name} className="rounded-2xl border-white/8 bg-[#111111]" disabled={!hasWorkspace || profileForm.processing} />
                        <Input label="Client Website / Domain" name="client_website" type="url" value={profileForm.data.client_website} onChange={(e) => profileForm.setData('client_website', e.target.value)} error={profileForm.errors.client_website || errors.client_website} className="rounded-2xl border-white/8 bg-[#111111]" disabled={!hasWorkspace || profileForm.processing} />
                        <Input label="Report Title" name="report_title" value={profileForm.data.report_title} onChange={(e) => profileForm.setData('report_title', e.target.value)} error={profileForm.errors.report_title || errors.report_title} className="rounded-2xl border-white/8 bg-[#111111]" disabled={!hasWorkspace || profileForm.processing} />
                        <DomainSelect domains={domains} value={profileForm.data.domain_id} onChange={(e) => profileForm.setData('domain_id', e.target.value)} error={profileForm.errors.domain_id || errors.domain_id} disabled={!hasWorkspace || profileForm.processing} />
                        <Input label="Reporting Period Start" name="reporting_period_start" type="date" value={profileForm.data.reporting_period_start} onChange={(e) => profileForm.setData('reporting_period_start', e.target.value)} error={profileForm.errors.reporting_period_start || errors.reporting_period_start} className="rounded-2xl border-white/8 bg-[#111111]" disabled={!hasWorkspace || profileForm.processing} />
                        <Input label="Reporting Period End" name="reporting_period_end" type="date" value={profileForm.data.reporting_period_end} onChange={(e) => profileForm.setData('reporting_period_end', e.target.value)} error={profileForm.errors.reporting_period_end || errors.reporting_period_end} className="rounded-2xl border-white/8 bg-[#111111]" disabled={!hasWorkspace || profileForm.processing} />
                    </div>

                    <div className="grid gap-5 md:grid-cols-2">
                        <TextareaField label="Target Keywords" value={profileForm.data.target_keywords} onChange={(e) => profileForm.setData('target_keywords', e.target.value)} error={profileForm.errors.target_keywords || errors.target_keywords} disabled={!hasWorkspace || profileForm.processing} />
                        <TextareaField label="Notes / Custom Summary" value={profileForm.data.notes} onChange={(e) => profileForm.setData('notes', e.target.value)} error={profileForm.errors.notes || errors.notes} disabled={!hasWorkspace || profileForm.processing} />
                    </div>

                    <TextareaField label="Recommendations / Next Steps" value={profileForm.data.recommendations} onChange={(e) => profileForm.setData('recommendations', e.target.value)} error={profileForm.errors.recommendations || errors.recommendations} disabled={!hasWorkspace || profileForm.processing} />

                    <div className="flex flex-wrap justify-end gap-3">
                        {editingProfileId && (
                            <Button type="button" variant="ghost" onClick={startNewProfile}>
                                Cancel Edit
                            </Button>
                        )}
                        <Button type="submit" variant="primary" disabled={!hasWorkspace || profileForm.processing}>
                            {profileForm.processing ? 'Saving...' : editingProfileId ? 'Update Profile' : 'Create Profile'}
                        </Button>
                    </div>
                </form>
            </Card>
        </div>
    );

    const renderPreviewTab = () => (
        <div className="space-y-6">
            <div className="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h3 className="text-2xl font-semibold tracking-[-0.03em] text-[#fff7f2]">Report Preview / Generate Report</h3>
                    <p className="mt-2 text-sm text-[rgba(255,240,232,0.56)]">Preview the white-label report on-screen and export the current version as PDF.</p>
                </div>
                <div className="flex flex-wrap gap-3">
                    {selectedProfile && (
                        <>
                            <Button href="/label/reports" variant="secondary">Manage Profiles</Button>
                            <Button href={selectedProfile.pdf_url} variant="primary">
                                <i className="bi bi-download mr-2"></i>Download PDF
                            </Button>
                        </>
                    )}
                </div>
            </div>

            {profiles.length > 0 && (
                <Card className="border border-white/8 bg-[#141414]">
                    <div className="flex flex-wrap gap-3">
                        {profiles.map((profile) => (
                            <Link
                                key={profile.id}
                                href={profile.preview_url}
                                className={`rounded-full px-4 py-2 text-sm transition ${selectedProfile?.id === profile.id ? 'bg-[var(--admin-primary)] text-white' : 'bg-[#111111] text-[rgba(255,240,232,0.72)]'}`}
                            >
                                {profile.client_name}
                            </Link>
                        ))}
                    </div>
                </Card>
            )}

            {previewReady ? (
                <BrandedAuditReportView report={previewReport} />
            ) : (
                <Card className="border border-dashed border-white/10 bg-[#141414]">
                    <div className="py-12 text-center">
                        <div className="text-xl font-semibold text-[#fff7f2]">No preview selected yet</div>
                        <p className="mt-2 text-sm text-[rgba(255,240,232,0.54)]">Create or choose a client report profile to generate the on-screen report preview.</p>
                        <div className="mt-5">
                            <Button href="/label/reports" variant="primary">Go To Client Reports</Button>
                        </div>
                    </div>
                </Card>
            )}
        </div>
    );

    return (
        <AppLayout
            header="Label"
            subtitle="Brand your client-facing SEO reports, manage client report profiles, and export polished previews."
        >
            <div className="space-y-6">
                {(flash?.success || flash?.error) && (
                    <div className={`rounded-2xl px-5 py-4 text-sm ${flash?.success ? 'border border-emerald-400/20 bg-emerald-500/10 text-emerald-200' : 'border border-rose-400/20 bg-rose-500/10 text-rose-200'}`}>
                        {flash?.success || flash?.error}
                    </div>
                )}

                <Card className="border border-[rgba(255,110,64,0.18)] bg-[linear-gradient(135deg,rgba(255,86,38,0.10),rgba(15,15,15,0.92))]">
                    <div className="flex flex-wrap items-start justify-between gap-6">
                        <div className="max-w-3xl">
                            <div className="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--admin-primary-light)]/80">White Label SEO Reports</div>
                            <h2 className="mt-3 text-3xl font-semibold tracking-[-0.04em] text-[#fff7f2]">Everything white-label now lives under Label</h2>
                            <p className="mt-3 text-sm leading-7 text-[rgba(255,240,232,0.66)]">
                                Save your brand identity, create client-specific report profiles, preview the report on-screen, and export a PDF using the real SEO data currently available in your account.
                            </p>
                        </div>
                        <div className="rounded-[24px] border border-white/10 bg-[rgba(0,0,0,0.18)] px-5 py-4 text-sm text-[rgba(255,240,232,0.72)]">
                            <div className="text-[11px] uppercase tracking-[0.2em] text-[rgba(255,240,232,0.42)]">Current Workspace</div>
                            <div className="mt-2 text-lg font-semibold text-[#fff7f2]">{organization?.name || 'Workspace required'}</div>
                        </div>
                    </div>
                </Card>

                {!hasWorkspace && (
                    <Card className="border border-[rgba(255,110,64,0.18)] bg-[#141414]">
                        <div className="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <h3 className="text-xl font-semibold text-[#fff7f2]">A workspace is required before using Label</h3>
                                <p className="mt-2 text-sm text-[rgba(255,240,232,0.58)]">
                                    Branding and client report profiles are stored against your current workspace so the system can keep them isolated from other users.
                                </p>
                            </div>
                            <Button href="/orgs/create" variant="primary">Create Workspace</Button>
                        </div>
                    </Card>
                )}

                <div className="flex flex-wrap gap-3">
                    <TabLink href={tabLinks.branding || '/label'} active={activeTab === 'branding'} icon="bi-palette" label="Branding Settings" />
                    <TabLink href={tabLinks.reports || '/label/reports'} active={activeTab === 'reports'} icon="bi-people" label="Client Reports" />
                    {tabLinks.preview && (
                        <TabLink href={tabLinks.preview} active={activeTab === 'preview'} icon="bi-file-earmark-text" label="Report Preview" />
                    )}
                </div>

                {activeTab === 'branding' && renderBrandingTab()}
                {activeTab === 'reports' && renderReportsTab()}
                {activeTab === 'preview' && renderPreviewTab()}
            </div>
        </AppLayout>
    );
}
