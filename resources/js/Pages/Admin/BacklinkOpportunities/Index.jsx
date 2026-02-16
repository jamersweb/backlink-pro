import { useState } from 'react';
import AdminLayout from '@/Components/Layout/AdminLayout';
import Card from '@/Components/Shared/Card';
import { Link, router, usePage } from '@inertiajs/react';

export default function BacklinkOpportunitiesIndex({ opportunities, stats, categories, filters = {} }) {
    const { flash } = usePage().props;
    const [search, setSearch] = useState(filters.search || '');
    const [categoryFilter, setCategoryFilter] = useState(filters.category_id || '');
    const [paMin, setPaMin] = useState(filters.pa_min || '');
    const [paMax, setPaMax] = useState(filters.pa_max || '');
    const [daMin, setDaMin] = useState(filters.da_min || '');
    const [daMax, setDaMax] = useState(filters.da_max || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || 'all');
    const [siteTypeFilter, setSiteTypeFilter] = useState(filters.site_type || '');
    const [showModal, setShowModal] = useState(false);
    const [csvFile, setCsvFile] = useState(null);
    const [uploading, setUploading] = useState(false);

    const handleFilter = () => {
        router.get('/admin/backlink-opportunities', {
            search: search || undefined,
            category_id: categoryFilter || undefined,
            pa_min: paMin || undefined,
            pa_max: paMax || undefined,
            da_min: daMin || undefined,
            da_max: daMax || undefined,
            status: statusFilter !== 'all' ? statusFilter : undefined,
            site_type: siteTypeFilter || undefined,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleExport = () => {
        const params = new URLSearchParams({
            ...(categoryFilter && { category_id: categoryFilter }),
            ...(statusFilter !== 'all' && { status: statusFilter }),
            ...(siteTypeFilter && { site_type: siteTypeFilter }),
        });
        window.open(`/admin/backlink-opportunities/export?${params.toString()}`, '_blank');
    };

    const handleCsvImport = (e) => {
        e.preventDefault();
        if (!csvFile) {
            alert('Please select a CSV file');
            return;
        }

        setUploading(true);
        const formData = new FormData();
        formData.append('csv_file', csvFile);

        router.post('/admin/backlink-opportunities/bulk-import', formData, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                setShowModal(false);
                setCsvFile(null);
                setUploading(false);
            },
            onError: () => {
                setUploading(false);
            },
        });
    };

    return (
        <AdminLayout header="Backlink Opportunities">
            <div className="space-y-6">
                {/* Success/Error Messages */}
                {flash?.success && (
                    <div className="p-4 rounded-lg bg-[#12B76A]/10 border border-[#12B76A]/30">
                        <p className="text-sm text-[#12B76A] font-medium">{flash.success}</p>
                    </div>
                )}
                {flash?.error && (
                    <div className="p-4 rounded-lg bg-[#F04438]/10 border border-[#F04438]/30">
                        <p className="text-sm text-[#F04438] font-medium">{flash.error}</p>
                    </div>
                )}
                {flash?.import_errors && flash.import_errors.length > 0 && (
                    <div className="p-4 rounded-lg bg-[#F79009]/10 border border-[#F79009]/30">
                        <p className="text-sm font-semibold text-[#F79009] mb-2">Import Warnings:</p>
                        <ul className="list-disc list-inside text-sm text-[#F79009] space-y-1">
                            {flash.import_errors.slice(0, 10).map((error, index) => (
                                <li key={index}>{error}</li>
                            ))}
                            {flash.import_errors.length > 10 && (
                                <li>... and {flash.import_errors.length - 10} more</li>
                            )}
                        </ul>
                    </div>
                )}

                {/* Page Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-2xl font-bold text-[var(--admin-text)]">Backlink Opportunities</h2>
                        <p className="text-[var(--admin-text-muted)] mt-1">Manage backlink opportunities database</p>
                    </div>
                    <div className="flex gap-2">
                        <button
                            onClick={() => setShowModal(true)}
                            className="px-4 py-2.5 bg-[var(--admin-surface)] hover:bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] text-[var(--admin-text)] rounded-lg font-medium transition-all duration-200 flex items-center gap-2"
                        >
                            <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            Import CSV
                        </button>
                        <Link
                            href="/admin/backlink-opportunities/create"
                            className="px-4 py-2.5 bg-gradient-to-r from-[#2F6BFF] to-[#2457D6] hover:from-[#2457D6] hover:to-[#1E4BBD] text-white rounded-lg font-medium transition-all duration-200 flex items-center gap-2 shadow-lg shadow-[#2F6BFF]/20"
                        >
                            <i className="bi bi-plus-lg"></i>
                            Add Opportunity
                        </Link>
                    </div>
                </div>

                {/* KPI Stats Cards - Dashboard Style */}
                <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    {/* Total */}
                    <div className="group relative overflow-hidden rounded-xl bg-[var(--admin-surface)] border border-[var(--admin-border)] p-6 hover:border-[var(--admin-border-hover)] transition-all duration-300 shadow-[var(--admin-shadow-sm)]">
                        <div className="absolute top-0 right-0 w-24 h-24 bg-slate-500/10 rounded-full blur-2xl -mr-8 -mt-8 group-hover:bg-slate-500/20 transition-colors dark:opacity-100 opacity-0"></div>
                        <div className="relative flex items-center justify-between">
                            <div>
                                <p className="text-[var(--admin-text-muted)] text-sm font-medium mb-1">Total</p>
                                <p className="text-3xl font-bold text-[var(--admin-text)]">{stats?.total || 0}</p>
                                <p className="text-[var(--admin-text-dim)] text-xs mt-2">All opportunities</p>
                            </div>
                            <div className="flex items-center justify-center h-14 w-14 rounded-xl bg-slate-500/10">
                                <svg className="h-7 w-7 text-slate-600 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    {/* Active */}
                    <div className="group relative overflow-hidden rounded-xl bg-[var(--admin-surface)] border border-[var(--admin-border)] p-6 hover:border-[#12B76A]/50 transition-all duration-300 shadow-[var(--admin-shadow-sm)]">
                        <div className="absolute top-0 right-0 w-24 h-24 bg-[#12B76A]/10 rounded-full blur-2xl -mr-8 -mt-8 group-hover:bg-[#12B76A]/20 transition-colors dark:opacity-100 opacity-0"></div>
                        <div className="relative flex items-center justify-between">
                            <div>
                                <p className="text-[var(--admin-text-muted)] text-sm font-medium mb-1">Active</p>
                                <p className="text-3xl font-bold text-[var(--admin-text)]">{stats?.active || 0}</p>
                                <p className="text-[var(--admin-text-dim)] text-xs mt-2">Available</p>
                            </div>
                            <div className="flex items-center justify-center h-14 w-14 rounded-xl bg-[#12B76A]/15">
                                <svg className="h-7 w-7 text-[#12B76A]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    {/* Inactive */}
                    <div className="group relative overflow-hidden rounded-xl bg-[var(--admin-surface)] border border-[var(--admin-border)] p-6 hover:border-[#F79009]/50 transition-all duration-300 shadow-[var(--admin-shadow-sm)]">
                        <div className="absolute top-0 right-0 w-24 h-24 bg-[#F79009]/10 rounded-full blur-2xl -mr-8 -mt-8 group-hover:bg-[#F79009]/20 transition-colors dark:opacity-100 opacity-0"></div>
                        <div className="relative flex items-center justify-between">
                            <div>
                                <p className="text-[var(--admin-text-muted)] text-sm font-medium mb-1">Inactive</p>
                                <p className="text-3xl font-bold text-[var(--admin-text)]">{stats?.inactive || 0}</p>
                                <p className="text-[var(--admin-text-dim)] text-xs mt-2">Not in use</p>
                            </div>
                            <div className="flex items-center justify-center h-14 w-14 rounded-xl bg-[#F79009]/15">
                                <svg className="h-7 w-7 text-[#F79009]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    {/* Banned */}
                    <div className="group relative overflow-hidden rounded-xl bg-[var(--admin-surface)] border border-[var(--admin-border)] p-6 hover:border-[#F04438]/50 transition-all duration-300 shadow-[var(--admin-shadow-sm)]">
                        <div className="absolute top-0 right-0 w-24 h-24 bg-[#F04438]/10 rounded-full blur-2xl -mr-8 -mt-8 group-hover:bg-[#F04438]/20 transition-colors dark:opacity-100 opacity-0"></div>
                        <div className="relative flex items-center justify-between">
                            <div>
                                <p className="text-[var(--admin-text-muted)] text-sm font-medium mb-1">Banned</p>
                                <p className="text-3xl font-bold text-[var(--admin-text)]">{stats?.banned || 0}</p>
                                <p className="text-[var(--admin-text-dim)] text-xs mt-2">Blocked sites</p>
                            </div>
                            <div className="flex items-center justify-center h-14 w-14 rounded-xl bg-[#F04438]/15">
                                <svg className="h-7 w-7 text-[#F04438]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Filters Toolbar - Dashboard Style */}
                <Card variant="elevated">
                    <div className="p-5">
                        <div className="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-8 gap-4">
                            <div className="md:col-span-2 relative">
                                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg className="h-5 w-5 text-[var(--admin-text-muted)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input
                                    type="text"
                                    placeholder="Search URLs..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyPress={(e) => e.key === 'Enter' && handleFilter()}
                                    className="w-full pl-10 pr-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] placeholder-[var(--admin-text-muted)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                />
                            </div>
                            <div>
                                <select
                                    value={categoryFilter}
                                    onChange={(e) => setCategoryFilter(e.target.value)}
                                    className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                >
                                    <option value="">All Categories</option>
                                    {categories?.map((cat) => (
                                        <option key={cat.id} value={cat.id}>
                                            {cat.parent ? `└─ ${cat.name}` : cat.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <input
                                    type="number"
                                    placeholder="PA Min"
                                    value={paMin}
                                    onChange={(e) => setPaMin(e.target.value)}
                                    min="0"
                                    max="100"
                                    className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] placeholder-[var(--admin-text-muted)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                />
                            </div>
                            <div>
                                <input
                                    type="number"
                                    placeholder="PA Max"
                                    value={paMax}
                                    onChange={(e) => setPaMax(e.target.value)}
                                    min="0"
                                    max="100"
                                    className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] placeholder-[var(--admin-text-muted)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                />
                            </div>
                            <div>
                                <input
                                    type="number"
                                    placeholder="DA Min"
                                    value={daMin}
                                    onChange={(e) => setDaMin(e.target.value)}
                                    min="0"
                                    max="100"
                                    className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] placeholder-[var(--admin-text-muted)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                />
                            </div>
                            <div>
                                <input
                                    type="number"
                                    placeholder="DA Max"
                                    value={daMax}
                                    onChange={(e) => setDaMax(e.target.value)}
                                    min="0"
                                    max="100"
                                    className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] placeholder-[var(--admin-text-muted)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                />
                            </div>
                            <div>
                                <select
                                    value={statusFilter}
                                    onChange={(e) => setStatusFilter(e.target.value)}
                                    className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                >
                                    <option value="all">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="banned">Banned</option>
                                </select>
                            </div>
                            <div>
                                <select
                                    value={siteTypeFilter}
                                    onChange={(e) => setSiteTypeFilter(e.target.value)}
                                    className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                >
                                    <option value="">All Types</option>
                                    <option value="comment">Comment</option>
                                    <option value="profile">Profile</option>
                                    <option value="forum">Forum</option>
                                    <option value="guestposting">Guest Post</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div className="flex gap-2 mt-4">
                            <button
                                onClick={handleFilter}
                                className="flex-1 px-4 py-2.5 bg-gradient-to-r from-[#2F6BFF] to-[#2457D6] hover:from-[#2457D6] hover:to-[#1E4BBD] text-white rounded-lg font-medium transition-all duration-200 flex items-center justify-center gap-2"
                            >
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                Search
                            </button>
                            <button
                                onClick={handleExport}
                                className="px-4 py-2.5 bg-[var(--admin-surface)] hover:bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] text-[var(--admin-text)] rounded-lg font-medium transition-all duration-200 flex items-center gap-2"
                            >
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                                </svg>
                                Export CSV
                            </button>
                        </div>
                    </div>
                </Card>

                {/* Opportunities Table - Dashboard Style */}
                <Card variant="elevated">
                    {opportunities?.data && opportunities.data.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-[var(--admin-border)]">
                                <thead className="bg-[var(--admin-surface-2)]">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-[var(--admin-text-muted)] uppercase tracking-wider">URL</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-[var(--admin-text-muted)] uppercase tracking-wider">PA</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-[var(--admin-text-muted)] uppercase tracking-wider">DA</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-[var(--admin-text-muted)] uppercase tracking-wider">Categories</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-[var(--admin-text-muted)] uppercase tracking-wider">Type</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-[var(--admin-text-muted)] uppercase tracking-wider">Status</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-[var(--admin-text-muted)] uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-[var(--admin-border)]">
                                    {opportunities.data.map((opp) => (
                                        <tr key={opp.id} className="hover:bg-[var(--admin-hover-bg)] transition-colors">
                                            <td className="px-6 py-4">
                                                <a 
                                                    href={opp.url} 
                                                    target="_blank" 
                                                    rel="noopener noreferrer" 
                                                    className="text-sm text-[#2F6BFF] hover:text-[#5B8AFF] hover:underline break-all max-w-xs truncate block transition-colors"
                                                    title={opp.url}
                                                >
                                                    {opp.url}
                                                </a>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className="text-sm font-medium text-[var(--admin-text)]">
                                                    {opp.pa !== null ? opp.pa : 'N/A'}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className="text-sm font-medium text-[var(--admin-text)]">
                                                    {opp.da !== null ? opp.da : 'N/A'}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="flex flex-wrap gap-1">
                                                    {opp.categories?.slice(0, 2).map((cat) => (
                                                        <span key={cat.id} className="px-2 py-1 text-xs font-medium rounded-full bg-[var(--admin-surface-2)] text-[var(--admin-text)] border border-[var(--admin-border)]">
                                                            {cat.name}
                                                        </span>
                                                    ))}
                                                    {opp.categories?.length > 2 && (
                                                        <span className="px-2 py-1 text-xs font-medium rounded-full bg-[var(--admin-surface-2)] text-[var(--admin-text-muted)] border border-[var(--admin-border)]">
                                                            +{opp.categories.length - 2}
                                                        </span>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className="text-sm capitalize text-[var(--admin-text)]">{opp.site_type}</span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className={`px-3 py-1 text-xs font-semibold rounded-full ${
                                                    opp.status === 'active' 
                                                        ? 'bg-[#12B76A]/15 text-[#12B76A] border border-[#12B76A]/30' 
                                                        : opp.status === 'inactive' 
                                                            ? 'bg-[var(--admin-surface-2)] text-[var(--admin-text-muted)] border border-[var(--admin-border)]' 
                                                            : 'bg-[#F04438]/15 text-[#F04438] border border-[#F04438]/30'
                                                }`}>
                                                    {opp.status}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm">
                                                <Link 
                                                    href={`/admin/backlink-opportunities/${opp.id}/edit`} 
                                                    className="p-1.5 rounded-lg hover:bg-[var(--admin-surface-2)] text-[var(--admin-text-muted)] hover:text-[#2F6BFF] transition-colors inline-flex items-center"
                                                    title="Edit"
                                                >
                                                    <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <div className="text-center py-16">
                            <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-[var(--admin-surface-2)] mb-4">
                                <svg className="h-8 w-8 text-[var(--admin-text-dim)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                            </div>
                            <p className="text-[var(--admin-text-muted)] font-medium">No opportunities found</p>
                            <p className="text-[var(--admin-text-dim)] text-sm mt-1">Import CSV or add manually to get started</p>
                            <div className="flex items-center justify-center gap-2 mt-4">
                                <button
                                    onClick={() => setShowModal(true)}
                                    className="inline-flex items-center gap-2 px-4 py-2 bg-[var(--admin-surface)] hover:bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] text-[var(--admin-text)] rounded-lg text-sm font-medium transition-colors"
                                >
                                    <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                    Import CSV
                                </button>
                                <Link
                                    href="/admin/backlink-opportunities/create"
                                    className="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-[#2F6BFF] to-[#2457D6] hover:from-[#2457D6] hover:to-[#1E4BBD] text-white rounded-lg text-sm font-medium transition-colors shadow-lg shadow-[#2F6BFF]/20"
                                >
                                    <i className="bi bi-plus-lg"></i>
                                    Add Opportunity
                                </Link>
                            </div>
                        </div>
                    )}

                    {/* Pagination */}
                    {opportunities?.links && opportunities.links.length > 3 && (
                        <div className="px-6 py-4 border-t border-[var(--admin-border)] bg-[var(--admin-surface-2)]">
                            <div className="flex flex-col sm:flex-row items-center justify-between gap-4">
                                <div className="text-sm text-[var(--admin-text-muted)]">
                                    Showing <span className="font-medium text-[var(--admin-text)]">{opportunities.from || 0}</span> to <span className="font-medium text-[var(--admin-text)]">{opportunities.to || 0}</span> of <span className="font-medium text-[var(--admin-text)]">{opportunities.total || 0}</span> results
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    {opportunities.links.map((link, index) => (
                                        <Link
                                            key={index}
                                            href={link.url || '#'}
                                            className={`px-3 py-2 text-sm font-medium rounded-lg transition-all ${
                                                link.active
                                                    ? 'bg-gradient-to-r from-[#2F6BFF] to-[#2457D6] text-white shadow-lg shadow-[#2F6BFF]/20'
                                                    : 'bg-[var(--admin-surface)] text-[var(--admin-text)] hover:bg-[var(--admin-hover-bg)] border border-[var(--admin-border)]'
                                            }`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </div>
                            </div>
                        </div>
                    )}
                </Card>
            </div>

            {/* CSV Import Modal - Dashboard Style */}
            {showModal && (
                <div className="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                    <div className="bg-[var(--admin-surface)] rounded-2xl shadow-2xl max-w-2xl w-full border border-[var(--admin-border)]">
                        <div className="p-6">
                            <div className="flex justify-between items-center mb-6">
                                <div className="flex items-center gap-3">
                                    <div className="flex items-center justify-center w-10 h-10 rounded-xl bg-[#2F6BFF]/15">
                                        <svg className="h-5 w-5 text-[#5B8AFF]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 className="text-xl font-bold text-[var(--admin-text)]">Import CSV</h3>
                                        <p className="text-sm text-[var(--admin-text-muted)]">Upload opportunities data</p>
                                    </div>
                                </div>
                                <button
                                    onClick={() => {
                                        setShowModal(false);
                                        setCsvFile(null);
                                    }}
                                    className="p-2 rounded-lg hover:bg-[var(--admin-hover-bg)] text-[var(--admin-text-muted)] hover:text-[var(--admin-text)] transition-colors"
                                >
                                    <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <form onSubmit={handleCsvImport} className="space-y-4">
                                <div>
                                    <label className="block text-sm font-medium text-[var(--admin-text)] mb-2">
                                        CSV File <span className="text-[#F04438]">*</span>
                                    </label>
                                    <div className="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-dashed border-[var(--admin-border)] rounded-xl hover:border-[#2F6BFF]/50 transition-colors bg-[var(--admin-hover-bg)]">
                                        <div className="space-y-1 text-center">
                                            {csvFile ? (
                                                <div>
                                                    <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-[#12B76A]/15 mb-3">
                                                        <svg className="h-8 w-8 text-[#12B76A]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                        </svg>
                                                    </div>
                                                    <p className="text-sm text-[var(--admin-text)] font-medium">{csvFile.name}</p>
                                                    <button
                                                        type="button"
                                                        onClick={() => setCsvFile(null)}
                                                        className="text-sm text-[#F04438] hover:text-[#F04438]/80 mt-2 font-medium"
                                                    >
                                                        Remove file
                                                    </button>
                                                </div>
                                            ) : (
                                                <>
                                                    <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-[var(--admin-surface-2)] mb-3">
                                                        <svg className="h-8 w-8 text-[var(--admin-text-dim)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                                        </svg>
                                                    </div>
                                                    <div className="flex text-sm text-[var(--admin-text-muted)]">
                                                        <label className="relative cursor-pointer rounded-md font-medium text-[#2F6BFF] hover:text-[#5B8AFF] transition-colors">
                                                            <span>Upload a file</span>
                                                            <input
                                                                type="file"
                                                                accept=".csv,.txt"
                                                                className="sr-only"
                                                                onChange={(e) => setCsvFile(e.target.files[0])}
                                                                required
                                                            />
                                                        </label>
                                                        <p className="pl-1">or drag and drop</p>
                                                    </div>
                                                    <p className="text-xs text-[var(--admin-text-dim)]">CSV, TXT up to 10MB</p>
                                                </>
                                            )}
                                        </div>
                                    </div>
                                </div>

                                <div className="bg-[var(--admin-hover-bg)] p-4 rounded-xl border border-[var(--admin-border)]">
                                    <p className="text-sm font-semibold text-[var(--admin-text)] mb-2">CSV Format:</p>
                                    <p className="text-xs text-[var(--admin-text-muted)] mb-2">
                                        Required columns: <span className="font-semibold text-[var(--admin-text)]">url</span>, <span className="font-semibold text-[var(--admin-text)]">categories</span>
                                    </p>
                                    <p className="text-xs text-[var(--admin-text-muted)] mb-2">
                                        Optional columns: <span className="font-semibold text-[var(--admin-text)]">pa</span>, <span className="font-semibold text-[var(--admin-text)]">da</span>, <span className="font-semibold text-[var(--admin-text)]">site_type</span>, <span className="font-semibold text-[var(--admin-text)]">status</span>
                                    </p>
                                    <p className="text-xs text-[var(--admin-text-dim)] mt-3 mb-1">Example:</p>
                                    <pre className="text-xs bg-[var(--admin-surface)] p-3 rounded-lg border border-[var(--admin-border)] text-[var(--admin-text)] font-mono">
{`url,pa,da,categories,site_type,status
https://example.com/page,45,60,Business|SEO,comment,active`}
                                    </pre>
                                </div>

                                <div className="flex gap-3 pt-4">
                                    <button
                                        type="submit"
                                        disabled={uploading}
                                        className="flex-1 px-4 py-2.5 bg-gradient-to-r from-[#2F6BFF] to-[#2457D6] hover:from-[#2457D6] hover:to-[#1E4BBD] text-white rounded-lg font-medium transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-[#2F6BFF]/20"
                                    >
                                        {uploading ? (
                                            <span className="flex items-center justify-center gap-2">
                                                <svg className="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Uploading...
                                            </span>
                                        ) : 'Import CSV'}
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setShowModal(false);
                                            setCsvFile(null);
                                        }}
                                        className="px-4 py-2.5 bg-[var(--admin-surface)] hover:bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] text-[var(--admin-text)] rounded-lg font-medium transition-all duration-200"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}

