import AppLayout from '../../Components/Layout/AppLayout';
import Card from '../../Components/Shared/Card';
import Button from '../../Components/Shared/Button';

export default function WhiteLabelReportIndex({ reportHighlights = [], setupSteps = [] }) {
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
                                <Button variant="primary" size="lg" className="rounded-2xl px-6">
                                    <i className="bi bi-magic mr-2"></i>Start Branding Setup
                                </Button>
                                <Button variant="secondary" size="lg" className="rounded-2xl px-6">
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
            </div>
        </AppLayout>
    );
}
