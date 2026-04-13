import { Link, useForm, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import AppLayout from '../../Components/Layout/AppLayout';
import Button from '../../Components/Shared/Button';
import Input from '../../Components/Shared/Input';

const DRAFT_STORAGE_KEY = 'bp-project-draft';

export default function ProjectsIndex({ projects = [], googleStatus, storageReady = true, planSummary }) {
    const { flash } = usePage().props;
    const [creatorOpen, setCreatorOpen] = useState(false);
    const [draftBooted, setDraftBooted] = useState(false);
    const form = useForm({
        name: '',
        project_url: '',
    });

    useEffect(() => {
        const savedDraft = window.localStorage.getItem(DRAFT_STORAGE_KEY);
        if (!savedDraft) {
            return;
        }

        try {
            const parsedDraft = JSON.parse(savedDraft);
            form.setData({
                name: parsedDraft.name || '',
                project_url: parsedDraft.project_url || '',
            });

            if (parsedDraft.name || parsedDraft.project_url) {
                setCreatorOpen(true);
            }
        } catch {
            window.localStorage.removeItem(DRAFT_STORAGE_KEY);
        }

        setDraftBooted(true);
    }, []);

    useEffect(() => {
        if (!draftBooted) {
            return;
        }

        window.localStorage.setItem(DRAFT_STORAGE_KEY, JSON.stringify(form.data));
    }, [draftBooted, form.data]);

    const openCreator = () => {
        setCreatorOpen(true);
    };

    const closeCreator = () => {
        setCreatorOpen(false);
    };

    const saveDraftAndGo = (href) => {
        window.localStorage.setItem(DRAFT_STORAGE_KEY, JSON.stringify(form.data));
        window.location.href = href;
    };

    const submit = (e) => {
        e.preventDefault();
        form.post('/projects', {
            onSuccess: () => {
                window.localStorage.removeItem(DRAFT_STORAGE_KEY);
                form.reset();
                setCreatorOpen(false);
            },
        });
    };

    const hasProjects = projects.length > 0;
    const ga4ConnectHref = '/auth/google-ga4/redirect?return_url=/projects';
    const gscConnectHref = '/google-seo/connect?return_url=/projects';

    return (
        <AppLayout
            header="Projects"
            subtitle="Create projects your way, connect Google tools, and keep the list tidy"
            actions={
                <button type="button" onClick={openCreator} className="bp-topbar-btn-primary">
                    <i className="bi bi-plus-lg"></i>
                    <span>Create Project</span>
                </button>
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

                <section className="overflow-hidden rounded-[32px] border border-[rgba(255,138,101,0.18)] bg-[linear-gradient(135deg,rgba(255,120,82,0.18),rgba(16,11,10,0.98)_34%,rgba(9,9,9,1)_100%)] shadow-[0_28px_90px_rgba(0,0,0,0.35)]">
                    <div className="grid gap-8 px-6 py-7 lg:grid-cols-[1.2fr,0.8fr] lg:px-10 lg:py-10">
                        <div className="max-w-2xl">
                            <p className="text-xs font-semibold uppercase tracking-[0.32em] text-[#ffb697]">Project Workspace</p>
                            <h2 className="mt-3 text-4xl font-semibold tracking-[-0.04em] text-[#fff7f2] sm:text-5xl">
                                Ek click se project banao, connect karo, aur list mein dekh lo.
                            </h2>
                            <p className="mt-4 max-w-xl text-base leading-7 text-[rgba(255,240,232,0.72)]">
                                Pehle sirf create button. Us ke baad clean create panel khulta hai jahan name, URL, Google Analytics aur Search Console sab ek hi flow mein milta hai.
                            </p>

                        </div>

                        <div className="grid gap-4 self-end">
                            <div className="rounded-[28px] border border-[rgba(255,255,255,0.08)] bg-[rgba(255,247,242,0.04)] p-5 backdrop-blur-sm">
                                <div className="text-xs uppercase tracking-[0.22em] text-[rgba(255,240,232,0.5)]">Connection Status</div>
                                <div className="mt-4 grid gap-3 sm:grid-cols-2">
                                    <div className="rounded-2xl bg-[rgba(255,255,255,0.04)] p-4">
                                        <div className="flex items-center gap-3">
                                            <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-500/12 text-emerald-300">
                                                <i className="bi bi-graph-up-arrow text-lg"></i>
                                            </div>
                                            <div>
                                                <div className="text-sm font-semibold text-[#fff7f2]">Google Analytics</div>
                                                <div className={`mt-1 text-xs ${googleStatus?.ga4_connected ? 'text-emerald-300' : 'text-amber-200'}`}>
                                                    {googleStatus?.ga4_connected ? 'Connected' : 'Pending'}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="rounded-2xl bg-[rgba(255,255,255,0.04)] p-4">
                                        <div className="flex items-center gap-3">
                                            <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-sky-500/12 text-sky-200">
                                                <i className="bi bi-search text-lg"></i>
                                            </div>
                                            <div>
                                                <div className="text-sm font-semibold text-[#fff7f2]">Search Console</div>
                                                <div className={`mt-1 text-xs ${googleStatus?.seo_connected ? 'text-sky-200' : 'text-amber-200'}`}>
                                                    {googleStatus?.seo_connected ? 'Connected' : 'Pending'}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                {googleStatus?.google_email && (
                                    <p className="mt-4 text-sm text-[rgba(255,240,232,0.66)]">{googleStatus.google_email}</p>
                                )}
                            </div>
                        </div>
                    </div>
                </section>

                {!storageReady && (
                    <div className="rounded-2xl border border-amber-400/20 bg-amber-500/10 px-5 py-4 text-sm text-amber-100 shadow-lg shadow-amber-950/20">
                        Projects ka design ready hai, lekin database migration abhi apply nahi hui. Create action tab enable hogi jab `projects` table ban jayegi.
                    </div>
                )}

                {creatorOpen && (
                    <section className="overflow-hidden rounded-[30px] border border-[rgba(255,138,101,0.2)] bg-[linear-gradient(180deg,rgba(24,16,14,0.98),rgba(10,10,10,1))] shadow-[0_24px_70px_rgba(0,0,0,0.28)]">
                        <div className="flex flex-wrap items-start justify-between gap-4 border-b border-[rgba(255,138,101,0.14)] px-6 py-6 lg:px-8">
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-[0.26em] text-[#ffb697]">Create Flow</p>
                                <h3 className="mt-2 text-3xl font-semibold tracking-[-0.04em] text-[#fff7f2]">New project</h3>
                                <p className="mt-2 text-sm text-[rgba(255,240,232,0.62)]">
                                    Pehli line mein details, neeche Google connect buttons, aur phir create.
                                </p>
                            </div>
                            <div className="flex flex-wrap items-center justify-end gap-3">
                                <div className="rounded-2xl border border-[rgba(255,138,101,0.18)] bg-[rgba(255,247,242,0.05)] px-4 py-3">
                                    <div className="text-[11px] uppercase tracking-[0.22em] text-[rgba(255,240,232,0.45)]">Current Plan</div>
                                    <div className="mt-1 text-sm font-semibold text-[#fff7f2]">{planSummary?.label || 'Starter'}</div>
                                </div>
                                <div className="rounded-2xl border border-[rgba(255,138,101,0.18)] bg-[rgba(255,247,242,0.05)] px-4 py-3">
                                    <div className="text-[11px] uppercase tracking-[0.22em] text-[rgba(255,240,232,0.45)]">Projects Remaining</div>
                                    <div className="mt-1 text-sm font-semibold text-[#fff7f2]">
                                        {planSummary?.is_unlimited ? 'Unlimited' : `${planSummary?.projects_remaining ?? 0} left`}
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    onClick={closeCreator}
                                    className="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-[rgba(255,255,255,0.08)] bg-[rgba(255,255,255,0.04)] text-[rgba(255,240,232,0.78)] transition hover:bg-[rgba(255,255,255,0.08)]"
                                >
                                    <i className="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>

                        <form onSubmit={submit} className="px-6 py-6 lg:px-8 lg:py-8">
                            <div className="grid gap-5 lg:grid-cols-2">
                                <Input
                                    label="Project Name"
                                    name="name"
                                    value={form.data.name}
                                    onChange={(e) => form.setData('name', e.target.value)}
                                    error={form.errors.name}
                                    required
                                    icon={<i className="bi bi-kanban text-[rgba(255,240,232,0.46)]"></i>}
                                    helpText="Example: XpertBid Growth"
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
                                    helpText="Example: https://www.xpertbid.com"
                                />
                            </div>

                            <div className="mt-4 rounded-[28px] border border-[rgba(255,138,101,0.14)] bg-[rgba(255,247,242,0.03)] p-4 lg:p-5">
                                <div className="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <div className="text-sm font-semibold text-[#fff7f2]">Google Connections</div>
                                        <div className="mt-1 text-sm text-[rgba(255,240,232,0.58)]">
                                            Chaho to create se pehle connect kar lo, phir project list mein ready status ke saath aayega.
                                            {!planSummary?.is_unlimited && (
                                                <span className="ml-1 text-[rgba(255,240,232,0.82)]">Aap ke paas {planSummary?.projects_remaining ?? 0} projects remaining hain.</span>
                                            )}
                                        </div>
                                    </div>
                                    <div className="text-xs uppercase tracking-[0.2em] text-[rgba(255,240,232,0.44)]">Optional</div>
                                </div>

                                <div className="mt-5 grid gap-3 md:grid-cols-2">
                                    <button
                                        type="button"
                                        onClick={() => saveDraftAndGo(ga4ConnectHref)}
                                        className="group rounded-[24px] border border-[rgba(16,185,129,0.22)] bg-[linear-gradient(135deg,rgba(16,185,129,0.12),rgba(255,255,255,0.02))] p-5 text-left transition hover:border-[rgba(16,185,129,0.36)] hover:bg-[linear-gradient(135deg,rgba(16,185,129,0.18),rgba(255,255,255,0.03))]"
                                    >
                                        <div className="flex items-center justify-between gap-3">
                                            <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-500/12 text-emerald-300">
                                                <i className="bi bi-graph-up-arrow text-xl"></i>
                                            </div>
                                            <span className={`rounded-full px-3 py-1 text-xs font-semibold ${googleStatus?.ga4_connected ? 'bg-emerald-500/12 text-emerald-300' : 'bg-amber-500/12 text-amber-200'}`}>
                                                {googleStatus?.ga4_connected ? 'Connected' : 'Connect'}
                                            </span>
                                        </div>
                                        <h4 className="mt-4 text-lg font-semibold text-[#fff7f2]">Google Analytics</h4>
                                        <p className="mt-1 text-sm text-[rgba(255,240,232,0.6)]">Traffic aur behavior data connect karein.</p>
                                    </button>

                                    <button
                                        type="button"
                                        onClick={() => saveDraftAndGo(gscConnectHref)}
                                        className="group rounded-[24px] border border-[rgba(96,165,250,0.22)] bg-[linear-gradient(135deg,rgba(96,165,250,0.12),rgba(255,255,255,0.02))] p-5 text-left transition hover:border-[rgba(96,165,250,0.36)] hover:bg-[linear-gradient(135deg,rgba(96,165,250,0.18),rgba(255,255,255,0.03))]"
                                    >
                                        <div className="flex items-center justify-between gap-3">
                                            <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-500/12 text-sky-200">
                                                <i className="bi bi-search text-xl"></i>
                                            </div>
                                            <span className={`rounded-full px-3 py-1 text-xs font-semibold ${googleStatus?.seo_connected ? 'bg-sky-500/12 text-sky-200' : 'bg-amber-500/12 text-amber-200'}`}>
                                                {googleStatus?.seo_connected ? 'Connected' : 'Connect'}
                                            </span>
                                        </div>
                                        <h4 className="mt-4 text-lg font-semibold text-[#fff7f2]">Google Search Console</h4>
                                        <p className="mt-1 text-sm text-[rgba(255,240,232,0.6)]">Search visibility aur keywords connect karein.</p>
                                    </button>
                                </div>
                            </div>

                            <div className="mt-6 flex flex-wrap items-center justify-between gap-4 border-t border-[rgba(255,138,101,0.14)] pt-6">
                                <p className="text-sm text-[rgba(255,240,232,0.56)]">
                                    Create karte hi project neeche list mein aa jayega.
                                </p>
                                <Button type="submit" variant="primary" size="lg" disabled={form.processing || !storageReady} className="rounded-2xl px-8">
                                    {form.processing ? 'Creating Project...' : <><i className="bi bi-stars mr-2"></i>Create Project</>}
                                </Button>
                            </div>
                        </form>
                    </section>
                )}

                <section className="overflow-hidden rounded-[30px] border border-[rgba(255,138,101,0.16)] bg-[linear-gradient(180deg,rgba(18,13,12,0.96),rgba(10,10,10,1))] shadow-[0_18px_60px_rgba(0,0,0,0.2)]">
                    <div className="flex flex-wrap items-center justify-between gap-4 border-b border-[rgba(255,138,101,0.12)] px-6 py-6 lg:px-8">
                        <div>
                            <p className="text-xs font-semibold uppercase tracking-[0.24em] text-[rgba(255,240,232,0.5)]">Projects List</p>
                            <h3 className="mt-2 text-3xl font-semibold tracking-[-0.04em] text-[#fff7f2]">Saved projects</h3>
                        </div>
                        <button
                            type="button"
                            onClick={openCreator}
                            className="inline-flex items-center rounded-2xl border border-[rgba(255,138,101,0.18)] bg-[rgba(255,247,242,0.04)] px-5 py-3 text-sm font-semibold text-[#fff7f2] transition hover:bg-[rgba(255,138,101,0.1)]"
                        >
                            <i className="bi bi-plus-lg mr-2"></i>New Project
                        </button>
                    </div>

                    <div className="px-6 py-6 lg:px-8 lg:py-8">
                        {hasProjects ? (
                            <div className="grid gap-4">
                                {projects.map((project) => (
                                    <Link
                                        key={project.id}
                                        href={`/projects/${project.id}`}
                                        className="block rounded-[26px] border border-[rgba(255,138,101,0.12)] bg-[linear-gradient(135deg,rgba(255,247,242,0.04),rgba(255,255,255,0.02))] p-5 transition hover:border-[rgba(255,138,101,0.26)] hover:translate-y-[-1px]"
                                    >
                                        <div className="flex flex-wrap items-start justify-between gap-4">
                                            <div className="min-w-0">
                                                <h4 className="truncate text-xl font-semibold text-[#fff7f2]">{project.name}</h4>
                                                <p className="mt-2 truncate text-sm text-[rgba(255,240,232,0.6)]">{project.project_url}</p>
                                                <p className="mt-3 text-xs uppercase tracking-[0.18em] text-[rgba(255,240,232,0.42)]">{project.host || 'No host detected'}</p>
                                            </div>

                                            <div className="flex flex-wrap gap-2">
                                                <span className={`rounded-full px-3 py-1 text-xs font-semibold ${project.ga4_connected_at ? 'bg-emerald-500/12 text-emerald-300' : 'bg-[rgba(255,255,255,0.06)] text-[rgba(255,240,232,0.68)]'}`}>
                                                    Analytics
                                                </span>
                                                <span className={`rounded-full px-3 py-1 text-xs font-semibold ${project.gsc_connected_at ? 'bg-sky-500/12 text-sky-200' : 'bg-[rgba(255,255,255,0.06)] text-[rgba(255,240,232,0.68)]'}`}>
                                                    Search Console
                                                </span>
                                            </div>
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        ) : (
                            <div className="flex min-h-[280px] flex-col items-center justify-center rounded-[28px] border border-dashed border-[rgba(255,138,101,0.16)] bg-[radial-gradient(circle_at_top,rgba(255,138,101,0.08),transparent_42%)] px-6 text-center">
                                <div className="flex h-20 w-20 items-center justify-center rounded-[28px] bg-[rgba(255,138,101,0.14)] text-[var(--admin-primary-light)]">
                                    <i className="bi bi-plus-square text-3xl"></i>
                                </div>
                                <h4 className="mt-6 text-2xl font-semibold text-[#fff7f2]">Abhi koi project list mein nahi hai</h4>
                                <p className="mt-3 max-w-md text-sm leading-6 text-[rgba(255,240,232,0.58)]">
                                    Upar `Create Project` par click karein, details fill karein, Google connect karein aur project ko yahin list mein add kar dein.
                                </p>
                                <button
                                    type="button"
                                    onClick={openCreator}
                                    className="mt-6 inline-flex items-center rounded-2xl bg-[#fff3ec] px-6 py-3 text-sm font-semibold text-[#1c130f] transition hover:bg-white"
                                >
                                    <i className="bi bi-plus-lg mr-2"></i>Create Your First Project
                                </button>
                            </div>
                        )}
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}
