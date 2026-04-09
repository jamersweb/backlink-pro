import { useState } from 'react';
import AppLayout from '../../Components/Layout/AppLayout';
import Card from '../../Components/Shared/Card';
import Button from '../../Components/Shared/Button';
import Input from '../../Components/Shared/Input';

export default function WhiteLabelReportIndex({ reportHighlights = [], setupSteps = [] }) {
    const [branding, setBranding] = useState({
        companyName: 'Backlink Pro Studio',
        reportTitle: 'Monthly SEO Growth Report',
        primaryColor: '#ff8a65',
        accentColor: '#ffcfb9',
        logoText: 'BP',
        footerNote: 'Prepared for client delivery with your white label branding.',
        sections: {
            overview: true,
            backlinks: true,
            rankings: true,
            recommendations: true,
        },
    });

    const updateField = (field, value) => {
        setBranding((current) => ({
            ...current,
            [field]: value,
        }));
    };

    const updateSection = (section) => {
        setBranding((current) => ({
            ...current,
            sections: {
                ...current.sections,
                [section]: !current.sections[section],
            },
        }));
    };

    const scrollToSection = (sectionId, focusId) => {
        document.getElementById(sectionId)?.scrollIntoView({ behavior: 'smooth', block: 'start' });

        if (focusId) {
            window.setTimeout(() => {
                document.getElementById(focusId)?.focus();
            }, 350);
        }
    };

    const selectedSections = Object.entries(branding.sections)
        .filter(([, enabled]) => enabled)
        .map(([key]) => key);

    const formatSectionLabel = (value) => value.charAt(0).toUpperCase() + value.slice(1);

    return (
        <AppLayout
            header="White Label Report"
            subtitle="Create client-ready reports that match your brand and the rest of your dashboard experience."
        >
            <div className="space-y-6">
                <Card className="overflow-hidden border border-[rgba(255,110,64,0.18)] bg-[radial-gradient(circle_at_top_left,rgba(255,110,64,0.12),transparent_30%),linear-gradient(180deg,rgba(22,18,18,0.94),rgba(10,10,10,0.98))] shadow-[0_24px_60px_rgba(0,0,0,0.24)]">
                    <div className="grid gap-6 lg:grid-cols-[1.2fr,0.8fr] lg:items-center">
                        <div>
                            <p className="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--admin-primary-light)]/80">Client Reporting</p>
                            <h2 className="mt-2 text-3xl font-semibold text-[#fff7f2]">Launch branded reports without breaking your dashboard flow</h2>
                            <p className="mt-3 max-w-2xl text-sm leading-6 text-[rgba(255,240,232,0.68)]">
                                This page follows the same premium dashboard theme and gives you a dedicated space for white label reporting controls, previews and future client delivery settings.
                            </p>
                            <div className="mt-6 flex flex-wrap gap-3">
                                <Button
                                    variant="primary"
                                    size="lg"
                                    className="rounded-2xl px-6"
                                    onClick={() => scrollToSection('branding-setup-section', 'company_name')}
                                >
                                    <i className="bi bi-magic mr-2"></i>Start Branding Setup
                                </Button>
                                <Button
                                    variant="secondary"
                                    size="lg"
                                    className="rounded-2xl px-6"
                                    onClick={() => scrollToSection('preview-report-section')}
                                >
                                    <i className="bi bi-eye mr-2"></i>Preview Report Theme
                                </Button>
                            </div>
                        </div>

                        <div className="grid gap-4">
                            <div className="rounded-3xl border border-[rgba(255,110,64,0.18)] bg-[rgba(255,247,242,0.04)] p-5">
                                <div className="text-xs uppercase tracking-[0.18em] text-[rgba(255,240,232,0.46)]">Brand Status</div>
                                <div className="mt-3 text-2xl font-semibold text-[#fff7f2]">Ready for setup</div>
                                <p className="mt-2 text-sm text-[rgba(255,240,232,0.62)]">Connect logo, colors and visible sections for a cleaner client experience.</p>
                            </div>
                            <div className="rounded-3xl border border-[rgba(255,110,64,0.14)] bg-[linear-gradient(180deg,rgba(255,110,64,0.09),rgba(255,110,64,0.03))] p-5">
                                <div className="text-xs uppercase tracking-[0.18em] text-[rgba(255,240,232,0.46)]">Use Case</div>
                                <div className="mt-3 text-lg font-semibold text-[#fff7f2]">Agency-friendly reporting</div>
                                <p className="mt-2 text-sm text-[rgba(255,240,232,0.62)]">Perfect for sending polished backlinks and SEO updates under your own brand name.</p>
                            </div>
                        </div>
                    </div>
                </Card>

                <div className="grid gap-6 xl:grid-cols-[1.15fr,0.85fr]">
                    <Card className="border border-[rgba(255,110,64,0.18)] bg-[linear-gradient(180deg,rgba(22,18,18,0.94),rgba(10,10,10,0.98))]" variant="ghost">
                        <div className="mb-6">
                            <p className="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--admin-primary-light)]/80">Highlights</p>
                            <h3 className="mt-2 text-2xl font-semibold text-[#fff7f2]">What this section will support</h3>
                        </div>

                        <div className="grid gap-4">
                            {reportHighlights.map((item) => (
                                <div
                                    key={item.title}
                                    className="rounded-2xl border border-[rgba(255,110,64,0.14)] bg-[rgba(255,247,242,0.03)] p-5"
                                >
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

                    <Card
                        className="border border-[rgba(255,110,64,0.18)] bg-[linear-gradient(180deg,rgba(22,18,18,0.94),rgba(10,10,10,0.98))]"
                        variant="ghost"
                    >
                        <div id="branding-setup-section" className="mb-6">
                            <p className="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--admin-primary-light)]/80">Branding Setup</p>
                            <h3 className="mt-2 text-2xl font-semibold text-[#fff7f2]">Configure your white label identity</h3>
                            <p className="mt-2 text-sm leading-6 text-[rgba(255,240,232,0.64)]">
                                These fields now control the live preview below, so both top buttons perform the exact action written on them.
                            </p>
                        </div>

                        <div className="grid gap-5 md:grid-cols-2">
                            <Input
                                label="Company Name"
                                name="company_name"
                                value={branding.companyName}
                                onChange={(e) => updateField('companyName', e.target.value)}
                                className="rounded-2xl border-[rgba(255,110,64,0.16)] bg-[rgba(255,247,242,0.03)]"
                                helpText="Shown as the main white label brand on the report."
                            />
                            <Input
                                label="Report Title"
                                name="report_title"
                                value={branding.reportTitle}
                                onChange={(e) => updateField('reportTitle', e.target.value)}
                                className="rounded-2xl border-[rgba(255,110,64,0.16)] bg-[rgba(255,247,242,0.03)]"
                                helpText="This becomes the client-facing report heading."
                            />
                            <Input
                                label="Logo Text"
                                name="logo_text"
                                value={branding.logoText}
                                onChange={(e) => updateField('logoText', e.target.value.slice(0, 3).toUpperCase())}
                                className="rounded-2xl border-[rgba(255,110,64,0.16)] bg-[rgba(255,247,242,0.03)]"
                                helpText="Short initials for the preview badge."
                            />
                            <Input
                                label="Footer Note"
                                name="footer_note"
                                value={branding.footerNote}
                                onChange={(e) => updateField('footerNote', e.target.value)}
                                className="rounded-2xl border-[rgba(255,110,64,0.16)] bg-[rgba(255,247,242,0.03)]"
                                helpText="Displayed at the bottom of the report preview."
                            />
                        </div>

                        <div className="mt-2 grid gap-5 md:grid-cols-2">
                            <div>
                                <label className="mb-2 block text-sm font-medium text-[var(--admin-text)]">Primary Color</label>
                                <div className="flex items-center gap-3 rounded-2xl border border-[rgba(255,110,64,0.16)] bg-[rgba(255,247,242,0.03)] px-4 py-3">
                                    <input
                                        type="color"
                                        value={branding.primaryColor}
                                        onChange={(e) => updateField('primaryColor', e.target.value)}
                                        className="h-11 w-14 rounded-lg border border-[rgba(255,110,64,0.18)] bg-transparent"
                                    />
                                    <div>
                                        <div className="text-sm font-semibold text-[#fff7f2]">{branding.primaryColor}</div>
                                        <div className="text-xs text-[rgba(255,240,232,0.58)]">Main button and highlight color</div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label className="mb-2 block text-sm font-medium text-[var(--admin-text)]">Accent Color</label>
                                <div className="flex items-center gap-3 rounded-2xl border border-[rgba(255,110,64,0.16)] bg-[rgba(255,247,242,0.03)] px-4 py-3">
                                    <input
                                        type="color"
                                        value={branding.accentColor}
                                        onChange={(e) => updateField('accentColor', e.target.value)}
                                        className="h-11 w-14 rounded-lg border border-[rgba(255,110,64,0.18)] bg-transparent"
                                    />
                                    <div>
                                        <div className="text-sm font-semibold text-[#fff7f2]">{branding.accentColor}</div>
                                        <div className="text-xs text-[rgba(255,240,232,0.58)]">Used for soft cards and support accents</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="mt-6">
                            <p className="text-sm font-medium text-[var(--admin-text)]">Visible Report Sections</p>
                            <div className="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                {Object.entries(branding.sections).map(([section, enabled]) => (
                                    <button
                                        key={section}
                                        type="button"
                                        onClick={() => updateSection(section)}
                                        className={`rounded-2xl border px-4 py-4 text-left transition-all ${
                                            enabled
                                                ? 'border-[rgba(255,110,64,0.28)] bg-[rgba(255,110,64,0.1)]'
                                                : 'border-[rgba(255,110,64,0.14)] bg-[rgba(255,247,242,0.03)]'
                                        }`}
                                    >
                                        <div className="flex items-center justify-between gap-3">
                                            <span className="text-sm font-semibold text-[#fff7f2]">{formatSectionLabel(section)}</span>
                                            <i className={`bi ${enabled ? 'bi-check-circle-fill text-[#ffcfb9]' : 'bi-circle text-[rgba(255,240,232,0.42)]'}`}></i>
                                        </div>
                                        <p className="mt-2 text-xs leading-5 text-[rgba(255,240,232,0.58)]">
                                            {enabled ? 'Included in the preview report.' : 'Hidden from the preview report.'}
                                        </p>
                                    </button>
                                ))}
                            </div>
                        </div>
                    </Card>

                    <div className="space-y-6">
                        <Card className="border border-[rgba(255,110,64,0.18)] bg-[linear-gradient(180deg,rgba(22,18,18,0.94),rgba(10,10,10,0.98))]" variant="ghost">
                            <p className="text-xs font-semibold uppercase tracking-[0.24em] text-[rgba(255,240,232,0.58)]">Quick Setup</p>
                            <div className="mt-5 space-y-3">
                                {setupSteps.map((step, index) => (
                                    <div
                                        key={step}
                                        className="flex items-start gap-3 rounded-2xl border border-[rgba(255,110,64,0.14)] bg-[rgba(255,247,242,0.03)] p-4"
                                    >
                                        <div className="flex h-8 w-8 items-center justify-center rounded-full bg-[rgba(255,110,64,0.14)] text-sm font-semibold text-[#ffcfb9]">
                                            {index + 1}
                                        </div>
                                        <p className="pt-1 text-sm leading-6 text-[rgba(255,240,232,0.72)]">{step}</p>
                                    </div>
                                ))}
                            </div>
                        </Card>

                        <Card className="border border-[rgba(255,110,64,0.18)] bg-[rgba(255,110,64,0.06)]" variant="ghost">
                            <p className="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--admin-primary-light)]/80">Next Step</p>
                            <p className="mt-3 text-sm leading-6 text-[rgba(255,240,232,0.72)]">
                                The navigation tab is now in place and opens a page that matches the rest of the dashboard theme. From here we can wire real branding fields, uploads and export options.
                            </p>
                        </Card>
                    </div>
                </div>

                <Card
                    className="border border-[rgba(255,110,64,0.18)] bg-[linear-gradient(180deg,rgba(22,18,18,0.94),rgba(10,10,10,0.98))]"
                    variant="ghost"
                >
                    <div id="preview-report-section" className="mb-6 flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p className="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--admin-primary-light)]/80">Live Preview</p>
                            <h3 className="mt-2 text-2xl font-semibold text-[#fff7f2]">Preview Report Theme</h3>
                            <p className="mt-2 text-sm leading-6 text-[rgba(255,240,232,0.64)]">
                                This section updates instantly based on your branding setup choices.
                            </p>
                        </div>
                        <div
                            className="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em]"
                            style={{
                                borderColor: `${branding.primaryColor}55`,
                                backgroundColor: `${branding.primaryColor}18`,
                                color: branding.accentColor,
                            }}
                        >
                            White Label Preview
                        </div>
                    </div>

                    <div className="overflow-hidden rounded-[28px] border border-[rgba(255,110,64,0.16)] bg-[#120f0f]">
                        <div
                            className="flex flex-wrap items-center justify-between gap-4 px-6 py-5"
                            style={{
                                background: `linear-gradient(135deg, ${branding.primaryColor}26, transparent 58%), linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0))`,
                            }}
                        >
                            <div className="flex items-center gap-4">
                                <div
                                    className="flex h-14 w-14 items-center justify-center rounded-2xl text-lg font-bold"
                                    style={{
                                        backgroundColor: branding.primaryColor,
                                        color: '#140f0f',
                                    }}
                                >
                                    {branding.logoText || 'BP'}
                                </div>
                                <div>
                                    <div className="text-xl font-semibold text-[#fff7f2]">{branding.companyName || 'Your Company'}</div>
                                    <div className="mt-1 text-sm text-[rgba(255,240,232,0.6)]">{branding.reportTitle || 'White Label Report'}</div>
                                </div>
                            </div>
                            <div className="text-sm text-[rgba(255,240,232,0.6)]">Prepared for client presentation</div>
                        </div>

                        <div className="grid gap-5 px-6 py-6 lg:grid-cols-[0.9fr,1.1fr]">
                            <div className="space-y-4">
                                <div className="rounded-3xl border border-[rgba(255,255,255,0.08)] bg-[rgba(255,255,255,0.03)] p-5">
                                    <div className="text-xs uppercase tracking-[0.18em] text-[rgba(255,240,232,0.42)]">Included Modules</div>
                                    <div className="mt-4 flex flex-wrap gap-2">
                                        {selectedSections.length > 0 ? (
                                            selectedSections.map((section) => (
                                                <span
                                                    key={section}
                                                    className="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                                                    style={{
                                                        backgroundColor: `${branding.primaryColor}20`,
                                                        color: branding.accentColor,
                                                    }}
                                                >
                                                    {formatSectionLabel(section)}
                                                </span>
                                            ))
                                        ) : (
                                            <span className="text-sm text-[rgba(255,240,232,0.58)]">Select at least one section above.</span>
                                        )}
                                    </div>
                                </div>

                                <div className="rounded-3xl border border-[rgba(255,255,255,0.08)] bg-[rgba(255,255,255,0.03)] p-5">
                                    <div className="text-xs uppercase tracking-[0.18em] text-[rgba(255,240,232,0.42)]">Client Footer</div>
                                    <p className="mt-3 text-sm leading-6 text-[rgba(255,240,232,0.72)]">{branding.footerNote || 'Add a footer note for client delivery.'}</p>
                                </div>
                            </div>

                            <div className="space-y-4">
                                <div className="grid gap-4 sm:grid-cols-2">
                                    {[
                                        { label: 'Authority Growth', value: '+18%', tone: branding.primaryColor },
                                        { label: 'Verified Backlinks', value: '46', tone: branding.accentColor },
                                        { label: 'SEO Tasks Closed', value: '12', tone: branding.primaryColor },
                                        { label: 'Client Visibility', value: 'High', tone: branding.accentColor },
                                    ].map((metric) => (
                                        <div
                                            key={metric.label}
                                            className="rounded-3xl border border-[rgba(255,255,255,0.08)] bg-[rgba(255,255,255,0.03)] p-5"
                                        >
                                            <div className="text-xs uppercase tracking-[0.18em] text-[rgba(255,240,232,0.42)]">{metric.label}</div>
                                            <div className="mt-3 text-2xl font-semibold" style={{ color: metric.tone }}>
                                                {metric.value}
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                <div className="rounded-3xl border border-[rgba(255,255,255,0.08)] bg-[rgba(255,255,255,0.03)] p-5">
                                    <div className="flex items-center justify-between gap-3">
                                        <div className="text-sm font-semibold text-[#fff7f2]">Preview Branding Summary</div>
                                        <div
                                            className="h-2 w-28 rounded-full"
                                            style={{
                                                background: `linear-gradient(90deg, ${branding.primaryColor}, ${branding.accentColor})`,
                                            }}
                                        ></div>
                                    </div>
                                    <p className="mt-3 text-sm leading-6 text-[rgba(255,240,232,0.68)]">
                                        {branding.companyName || 'Your company'} will appear as the visible brand, while the selected colors shape headings, metric accents and client-facing badges.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}
