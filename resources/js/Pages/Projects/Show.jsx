import { Link, useForm, usePage } from '@inertiajs/react';
import AppLayout from '../../Components/Layout/AppLayout';
import Card from '../../Components/Shared/Card';
import Button from '../../Components/Shared/Button';
import Input from '../../Components/Shared/Input';

export default function ProjectShow({ project, googleStatus }) {
    const { flash } = usePage().props;
    const form = useForm({
        name: project.name || '',
        project_url: project.project_url || '',
    });

    const submit = (e) => {
        e.preventDefault();
        form.put(`/projects/${project.id}`);
    };

    const ga4Href = `/auth/google-ga4/redirect?return_url=/projects/${project.id}&project_id=${project.id}`;
    const gscHref = `/google-seo/connect?return_url=/projects/${project.id}&project_id=${project.id}`;

    const integrationCards = [
        {
            key: 'ga4',
            title: 'Connect GA4',
            description: 'Link Google Analytics 4 for traffic and engagement reporting.',
            icon: 'bi-graph-up-arrow',
            accent: 'emerald',
            href: ga4Href,
            connected: Boolean(project.ga4_connected_at),
            connectedText: project.ga4_connected_at ? `Connected on ${new Date(project.ga4_connected_at).toLocaleDateString()}` : 'Not connected yet',
        },
        {
            key: 'gsc',
            title: 'Connect GSC',
            description: 'Attach Google Search Console to track search visibility and keywords.',
            icon: 'bi-search',
            accent: 'sky',
            href: gscHref,
            connected: Boolean(project.gsc_connected_at),
            connectedText: project.gsc_connected_at ? `Connected on ${new Date(project.gsc_connected_at).toLocaleDateString()}` : 'Not connected yet',
        },
    ];

    return (
        <AppLayout
            header={project.name}
            subtitle="Project details, website URL and Google integrations"
            actions={
                <Link href="/projects" className="bp-topbar-btn-secondary">
                    <i className="bi bi-arrow-left"></i>
                    <span>Back to Projects</span>
                </Link>
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

                <Card className="overflow-hidden border border-[rgba(255,110,64,0.18)] bg-[radial-gradient(circle_at_top_left,rgba(255,110,64,0.12),transparent_34%),linear-gradient(180deg,rgba(22,18,18,0.96),rgba(10,10,10,0.98))] shadow-[0_24px_60px_rgba(0,0,0,0.26)]">
                    <div className="grid gap-6 border-b border-[rgba(255,110,64,0.12)] px-6 py-6 lg:grid-cols-[1.25fr,0.75fr]">
                        <div>
                            <p className="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--admin-primary-light)]/80">Project Overview</p>
                            <h2 className="mt-2 text-3xl font-semibold text-[#fff7f2]">{project.name}</h2>
                            <p className="mt-3 text-sm leading-6 text-[rgba(255,240,232,0.64)]">
                                Manage the project name and URL here, then use the Google connection buttons below whenever you need them.
                            </p>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
                            <div className="rounded-3xl border border-[rgba(255,110,64,0.14)] bg-[rgba(255,247,242,0.03)] p-5">
                                <div className="text-xs uppercase tracking-[0.18em] text-[rgba(255,240,232,0.46)]">Project URL</div>
                                <div className="mt-3 break-all text-base font-semibold text-[#fff7f2]">{project.project_url}</div>
                            </div>
                            <div className="rounded-3xl border border-[rgba(255,110,64,0.14)] bg-[rgba(255,247,242,0.03)] p-5">
                                <div className="text-xs uppercase tracking-[0.18em] text-[rgba(255,240,232,0.46)]">Google Account</div>
                                <div className="mt-3 text-sm font-medium text-[rgba(255,240,232,0.72)]">{googleStatus?.google_email || 'Connect a Google account to continue'}</div>
                            </div>
                        </div>
                    </div>

                    <div className="grid gap-6 px-6 py-6 xl:grid-cols-[0.95fr,1.05fr]">
                        <Card className="border border-[rgba(255,110,64,0.18)] bg-[linear-gradient(180deg,rgba(22,18,18,0.94),rgba(10,10,10,0.98))]" variant="ghost">
                            <div className="mb-6">
                                <p className="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--admin-primary-light)]/80">Project Details</p>
                                <h3 className="mt-2 text-2xl font-semibold text-[#fff7f2]">Edit project information</h3>
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
                                />

                                <div className="flex flex-wrap gap-3 border-t border-[rgba(255,110,64,0.12)] pt-5">
                                    <Button type="submit" variant="primary" size="lg" disabled={form.processing} className="rounded-2xl px-6">
                                        {form.processing ? 'Saving...' : <><i className="bi bi-check2-circle mr-2"></i>Save Changes</>}
                                    </Button>
                                    <Button
                                        href={`/projects/${project.id}`}
                                        method="delete"
                                        variant="danger"
                                        size="lg"
                                        className="rounded-2xl px-6"
                                        onClick={(event) => {
                                            if (!window.confirm('Are you sure you want to delete this project?')) {
                                                event.preventDefault();
                                            }
                                        }}
                                    >
                                        <i className="bi bi-trash3 mr-2"></i>Delete Project
                                    </Button>
                                </div>
                            </form>
                        </Card>

                        <div className="space-y-6">
                            {integrationCards.map((integration) => (
                                <Card key={integration.key} className="border border-[rgba(255,110,64,0.18)] bg-[linear-gradient(180deg,rgba(22,18,18,0.94),rgba(10,10,10,0.98))]" variant="ghost">
                                    <div className="flex flex-wrap items-start justify-between gap-4">
                                        <div className="flex items-start gap-4">
                                            <div className={`flex h-14 w-14 items-center justify-center rounded-2xl ${integration.accent === 'emerald' ? 'bg-emerald-500/12 text-emerald-300' : 'bg-sky-500/12 text-sky-200'}`}>
                                                <i className={`bi ${integration.icon} text-2xl`}></i>
                                            </div>
                                            <div>
                                                <h3 className="text-xl font-semibold text-[#fff7f2]">{integration.title}</h3>
                                                <p className="mt-2 max-w-lg text-sm leading-6 text-[rgba(255,240,232,0.62)]">{integration.description}</p>
                                                <p className={`mt-3 text-sm font-medium ${integration.connected ? 'text-emerald-300' : 'text-[rgba(255,240,232,0.58)]'}`}>{integration.connectedText}</p>
                                            </div>
                                        </div>
                                        <span className={`inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold ${integration.connected ? 'border-emerald-400/20 bg-emerald-500/10 text-emerald-300' : 'border-amber-400/20 bg-amber-500/10 text-amber-200'}`}>
                                            {integration.connected ? 'Connected' : 'Pending'}
                                        </span>
                                    </div>

                                    <div className="mt-6 flex flex-wrap gap-3 border-t border-[rgba(255,110,64,0.12)] pt-5">
                                        <Button href={integration.href} variant={integration.connected ? 'secondary' : 'primary'} size="lg" className="rounded-2xl px-6">
                                            <i className={`bi ${integration.icon} mr-2`}></i>
                                            {integration.connected ? 'Reconnect' : integration.title}
                                        </Button>
                                    </div>
                                </Card>
                            ))}
                        </div>
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}
