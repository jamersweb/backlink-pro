import { Link, useForm, usePage } from '@inertiajs/react';
import AppLayout from '../../Components/Layout/AppLayout';
import Card from '../../Components/Shared/Card';
import Button from '../../Components/Shared/Button';
import Input from '../../Components/Shared/Input';

export default function ProjectsIndex({ projects = [], googleStatus, storageReady = true }) {
    const { flash } = usePage().props;
    const form = useForm({
        name: '',
        project_url: '',
    });

    const submit = (e) => {
        e.preventDefault();
        form.post('/projects');
    };

    const summaryCards = [
        {
            label: 'Total Projects',
            value: projects.length,
            icon: 'bi-kanban',
            tint: 'from-[#ff8a65]/20 to-transparent',
        },
        {
            label: 'GA4 Ready',
            value: projects.filter((project) => project.ga4_connected_at).length,
            icon: 'bi-graph-up-arrow',
            tint: 'from-[#2dd4bf]/20 to-transparent',
        },
        {
            label: 'GSC Ready',
            value: projects.filter((project) => project.gsc_connected_at).length,
            icon: 'bi-search',
            tint: 'from-[#60a5fa]/20 to-transparent',
        },
    ];

    return (
        <AppLayout
            header="Projects"
            subtitle="Create client-ready projects and connect analytics in one clean workspace"
            actions={
                <a href="#project-create" className="bp-topbar-btn-primary">
                    <i className="bi bi-plus-lg"></i>
                    <span>New Project</span>
                </a>
            }
        >
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

                {!storageReady && (
                    <div className="rounded-2xl border border-amber-400/20 bg-amber-500/10 px-5 py-4 text-sm text-amber-100 shadow-lg shadow-amber-950/20">
                        Projects page ab open ho rahi hai, lekin database migration abhi apply nahi hui. `Create Project` tab kaam karega jab `projects` table create ho jayegi.
                    </div>
                )}

                <Card className="overflow-hidden border border-[rgba(255,110,64,0.18)] bg-[radial-gradient(circle_at_top_left,rgba(255,110,64,0.12),transparent_35%),linear-gradient(180deg,rgba(22,18,18,0.96),rgba(10,10,10,0.98))] shadow-[0_24px_60px_rgba(0,0,0,0.26)]">
                    <div className="grid gap-6 border-b border-[rgba(255,110,64,0.12)] px-6 py-6 lg:grid-cols-[1.3fr,0.7fr] lg:items-end">
                        <div>
                            <p className="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--admin-primary-light)]/80">Project Hub</p>
                            <h2 className="mt-2 text-3xl font-semibold text-[#fff7f2]">Keep every website neatly organized</h2>
                            <p className="mt-3 max-w-2xl text-sm leading-6 text-[rgba(255,240,232,0.64)]">
                                Add a project once, open its detail screen, and connect GA4 plus Search Console without jumping around the dashboard.
                            </p>
                        </div>

                        <div className="rounded-3xl border border-[rgba(255,110,64,0.14)] bg-[rgba(255,247,242,0.04)] p-5">
                            <div className="text-xs uppercase tracking-[0.18em] text-[rgba(255,240,232,0.46)]">Google Workspace</div>
                            <div className="mt-3 flex flex-wrap gap-2">
                                <span className={`inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold ${googleStatus?.ga4_connected ? 'border-emerald-400/20 bg-emerald-500/10 text-emerald-300' : 'border-amber-400/20 bg-amber-500/10 text-amber-200'}`}>
                                    <i className="bi bi-graph-up-arrow mr-2"></i>{googleStatus?.ga4_connected ? 'GA4 Connected' : 'GA4 Pending'}
                                </span>
                                <span className={`inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold ${googleStatus?.seo_connected ? 'border-sky-400/20 bg-sky-500/10 text-sky-200' : 'border-amber-400/20 bg-amber-500/10 text-amber-200'}`}>
                                    <i className="bi bi-search mr-2"></i>{googleStatus?.seo_connected ? 'GSC Connected' : 'GSC Pending'}
                                </span>
                            </div>
                            {googleStatus?.google_email && (
                                <p className="mt-3 text-sm text-[rgba(255,240,232,0.62)]">{googleStatus.google_email}</p>
                            )}
                        </div>
                    </div>

                    <div className="grid gap-5 px-6 py-6 md:grid-cols-3">
                        {summaryCards.map((card) => (
                            <div key={card.label} className={`rounded-3xl border border-[rgba(255,110,64,0.12)] bg-[linear-gradient(135deg,rgba(255,247,242,0.05),rgba(255,255,255,0.02)),radial-gradient(circle_at_top_left,var(--tw-gradient-stops))] ${card.tint} p-5`}>
                                <div className="flex items-center justify-between">
                                    <div>
                                        <div className="text-xs uppercase tracking-[0.18em] text-[rgba(255,240,232,0.46)]">{card.label}</div>
                                        <div className="mt-3 text-3xl font-semibold text-[#fff7f2]">{card.value}</div>
                                    </div>
                                    <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-[rgba(255,255,255,0.06)] text-[var(--admin-primary-light)]">
                                        <i className={`bi ${card.icon} text-xl`}></i>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </Card>

                <div className="grid gap-6 xl:grid-cols-[0.95fr,1.05fr]">
                    <Card id="project-create" className="border border-[rgba(255,110,64,0.18)] bg-[linear-gradient(180deg,rgba(22,18,18,0.94),rgba(10,10,10,0.98))]" variant="ghost">
                        <div className="mb-6">
                            <p className="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--admin-primary-light)]/80">New Project</p>
                            <h3 className="mt-2 text-2xl font-semibold text-[#fff7f2]">Add project details</h3>
                            <p className="mt-2 text-sm text-[rgba(255,240,232,0.62)]">Project page par name, URL, GA4 aur GSC actions ready milenge.</p>
                        </div>

                        <form onSubmit={submit} className="space-y-2">
                            <Input
                                label="Project Name"
                                name="name"
                                value={form.data.name}
                                onChange={(e) => form.setData('name', e.target.value)}
                                error={form.errors.name}
                                required
                                icon={<i className="bi bi-kanban text-[rgba(255,240,232,0.46)]"></i>}
                                helpText="Example: Alpha SEO Growth"
                            />

                            <Input
                                label="Project URL"
                                name="project_url"
                                type="url"
                                value={form.data.project_url}
                                onChange={(e) => form.setData('project_url', e.target.value)}
                                error={form.errors.project_url}
                                required
                                icon={<i className="bi bi-globe2 text-[rgba(255,240,232,0.46)]"></i>}
                                helpText="Full website URL enter karein, jaise https://example.com"
                            />

                            <div className="border-t border-[rgba(255,110,64,0.12)] pt-5">
                                <Button type="submit" variant="primary" size="lg" disabled={form.processing || !storageReady} className="w-full rounded-2xl">
                                    {form.processing ? 'Creating Project...' : <><i className="bi bi-stars mr-2"></i>Create Project</>}
                                </Button>
                            </div>
                        </form>
                    </Card>

                    <Card className="border border-[rgba(255,110,64,0.18)] bg-[linear-gradient(180deg,rgba(22,18,18,0.94),rgba(10,10,10,0.98))]" variant="ghost">
                        <div className="mb-6 flex items-center justify-between gap-3">
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-[0.24em] text-[rgba(255,240,232,0.58)]">Saved Projects</p>
                                <h3 className="mt-2 text-2xl font-semibold text-[#fff7f2]">Your project list</h3>
                            </div>
                            <div className="rounded-full border border-[rgba(255,110,64,0.14)] bg-[rgba(255,247,242,0.03)] px-3 py-1 text-xs font-semibold text-[rgba(255,240,232,0.72)]">
                                {projects.length} Items
                            </div>
                        </div>

                        <div className="space-y-4">
                            {projects.length > 0 ? projects.map((project) => (
                                <Link
                                    key={project.id}
                                    href={`/projects/${project.id}`}
                                    className="block rounded-3xl border border-[rgba(255,110,64,0.14)] bg-[rgba(255,247,242,0.03)] p-5 transition-all hover:border-[rgba(255,110,64,0.26)] hover:bg-[rgba(255,110,64,0.06)]"
                                >
                                    <div className="flex flex-wrap items-start justify-between gap-4">
                                        <div>
                                            <h4 className="text-lg font-semibold text-[#fff7f2]">{project.name}</h4>
                                            <p className="mt-1 text-sm text-[rgba(255,240,232,0.58)]">{project.project_url}</p>
                                        </div>
                                        <div className="flex flex-wrap gap-2">
                                            <span className={`inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ${project.ga4_connected_at ? 'bg-emerald-500/10 text-emerald-300' : 'bg-[rgba(255,255,255,0.06)] text-[rgba(255,240,232,0.66)]'}`}>GA4</span>
                                            <span className={`inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ${project.gsc_connected_at ? 'bg-sky-500/10 text-sky-200' : 'bg-[rgba(255,255,255,0.06)] text-[rgba(255,240,232,0.66)]'}`}>GSC</span>
                                        </div>
                                    </div>
                                    <div className="mt-5 flex items-center justify-between text-sm">
                                        <span className="text-[rgba(255,240,232,0.48)]">{project.host || 'No host detected'}</span>
                                        <span className="font-semibold text-[var(--admin-primary-light)]">Open Project <i className="bi bi-arrow-right-short text-base align-middle"></i></span>
                                    </div>
                                </Link>
                            )) : (
                                <div className="rounded-3xl border border-dashed border-[rgba(255,110,64,0.16)] bg-[rgba(255,247,242,0.03)] px-6 py-10 text-center">
                                    <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-[rgba(255,110,64,0.12)] text-[var(--admin-primary-light)]">
                                        <i className="bi bi-kanban text-2xl"></i>
                                    </div>
                                    <h4 className="mt-5 text-xl font-semibold text-[#fff7f2]">No project added yet</h4>
                                    <p className="mt-2 text-sm text-[rgba(255,240,232,0.58)]">Pehla project create karein aur phir us ke andar GA4 aur GSC connect karein.</p>
                                </div>
                            )}
                        </div>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
