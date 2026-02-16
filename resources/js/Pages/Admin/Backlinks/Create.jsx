import AdminLayout from '@/Components/Layout/AdminLayout';
import Card from '@/Components/Shared/Card';
import { Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function BacklinksCreate() {
    const { flash, errors } = usePage().props;
    const [activeTab, setActiveTab] = useState('single'); // 'single' or 'bulk'
    
    // Single add form
    const [singleForm, setSingleForm] = useState({
        url: '',
        type: 'comment',
        keyword: '',
        anchor_text: '',
        pa: '',
        da: '',
        status: 'pending',
    });
    
    // Bulk import form
    const [bulkForm, setBulkForm] = useState({
        csv_file: null,
    });
    const [uploading, setUploading] = useState(false);

    const handleSingleSubmit = (e) => {
        e.preventDefault();
        router.post('/admin/backlinks', singleForm, {
            preserveScroll: true,
            onSuccess: () => {
                router.visit('/admin/backlinks');
            },
        });
    };

    const handleBulkSubmit = (e) => {
        e.preventDefault();
        if (!bulkForm.csv_file) {
            alert('Please select a CSV file');
            return;
        }

        setUploading(true);
        const formData = new FormData();
        formData.append('csv_file', bulkForm.csv_file);

        router.post('/admin/backlinks/bulk-import', formData, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                router.visit('/admin/backlinks');
            },
            onError: () => {
                setUploading(false);
            },
        });
    };

    const handleFileChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setBulkForm({ ...bulkForm, csv_file: file });
        }
    };

    return (
        <AdminLayout header="Add Backlink">
            <div className="max-w-4xl mx-auto">
                {/* Page Header */}
                <div className="mb-6">
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold text-[var(--admin-text)] mb-1">Add New Backlink</h1>
                            <p className="text-sm text-[var(--admin-text-muted)]">Add backlinks individually or in bulk via CSV</p>
                        </div>
                        <div className="flex items-center gap-3">
                            <Link href="/admin/backlinks">
                                <button
                                    type="button"
                                    className="px-4 py-2.5 text-sm font-medium text-[var(--admin-text)] bg-[var(--admin-surface)] border border-[var(--admin-border)] rounded-lg hover:bg-[var(--admin-hover-bg)] transition-all"
                                >
                                    Cancel
                                </button>
                            </Link>
                            <button
                                type="submit"
                                form={activeTab === 'single' ? 'single-form' : 'bulk-form'}
                                disabled={activeTab === 'bulk' && uploading}
                                className="px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-[#2F6BFF] to-[#2457D6] hover:from-[#2457D6] hover:to-[#1E4BBD] rounded-lg shadow-lg shadow-[#2F6BFF]/20 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {activeTab === 'bulk' && uploading ? 'Uploading...' : activeTab === 'single' ? 'Add Backlink' : 'Import CSV'}
                            </button>
                        </div>
                    </div>
                </div>

                {/* Success/Error Messages */}
                {flash?.success && (
                    <div className="mb-6 p-4 rounded-lg bg-[#12B76A]/10 border border-[#12B76A]/30">
                        <p className="text-sm text-[#12B76A] font-medium">{flash.success}</p>
                    </div>
                )}
                {flash?.error && (
                    <div className="mb-6 p-4 rounded-lg bg-[#F04438]/10 border border-[#F04438]/30">
                        <p className="text-sm text-[#F04438] font-medium">{flash.error}</p>
                    </div>
                )}

                {/* Tabs */}
                <div className="mb-6">
                    <div className="flex border-b border-[var(--admin-border)]">
                        <button
                            onClick={() => setActiveTab('single')}
                            className={`px-6 py-3 font-semibold text-sm transition-colors relative ${
                                activeTab === 'single'
                                    ? 'text-[#2F6BFF]'
                                    : 'text-[var(--admin-text-muted)] hover:text-[var(--admin-text)]'
                            }`}
                        >
                            Single Add
                            {activeTab === 'single' && (
                                <div className="absolute bottom-0 left-0 right-0 h-0.5 bg-[#2F6BFF]"></div>
                            )}
                        </button>
                        <button
                            onClick={() => setActiveTab('bulk')}
                            className={`px-6 py-3 font-semibold text-sm transition-colors relative ${
                                activeTab === 'bulk'
                                    ? 'text-[#2F6BFF]'
                                    : 'text-[var(--admin-text-muted)] hover:text-[var(--admin-text)]'
                            }`}
                        >
                            Bulk Import (CSV)
                            {activeTab === 'bulk' && (
                                <div className="absolute bottom-0 left-0 right-0 h-0.5 bg-[#2F6BFF]"></div>
                            )}
                        </button>
                    </div>
                </div>

                {/* Single Add Form */}
                {activeTab === 'single' && (
                    <form id="single-form" onSubmit={handleSingleSubmit} className="space-y-6">
                        {/* Backlink Details */}
                        <Card variant="elevated">
                            <div className="p-6">
                                <div className="flex items-center gap-3 mb-6">
                                    <div className="flex items-center justify-center w-10 h-10 rounded-xl bg-[#5B8AFF]/15">
                                        <svg className="h-5 w-5 text-[#5B8AFF]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 className="text-lg font-semibold text-[var(--admin-text)]">Backlink Details</h3>
                                        <p className="text-sm text-[var(--admin-text-muted)]">URL and type information</p>
                                    </div>
                                </div>

                                <div className="space-y-6">
                                    <div>
                                        <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                            URL <span className="text-[#F04438]">*</span>
                                        </label>
                                        <input
                                            type="url"
                                            required
                                            value={singleForm.url}
                                            onChange={(e) => setSingleForm({ ...singleForm, url: e.target.value })}
                                            className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] placeholder-[var(--admin-text-muted)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                            placeholder="https://example.com/page"
                                        />
                                        {errors?.url && <p className="mt-1 text-sm text-[#F04438]">{errors.url}</p>}
                                        <p className="mt-1 text-xs text-[var(--admin-text-dim)]">Full URL where backlink exists</p>
                                    </div>

                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                                Type <span className="text-[#F04438]">*</span>
                                            </label>
                                            <select
                                                required
                                                value={singleForm.type}
                                                onChange={(e) => setSingleForm({ ...singleForm, type: e.target.value })}
                                                className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                            >
                                                <option value="comment">Comment</option>
                                                <option value="profile">Profile</option>
                                                <option value="forum">Forum</option>
                                                <option value="guestposting">Guest Post</option>
                                            </select>
                                            {errors?.type && <p className="mt-1 text-sm text-[#F04438]">{errors.type}</p>}
                                            <p className="mt-1 text-xs text-[var(--admin-text-dim)]">Backlink type</p>
                                        </div>

                                        <div>
                                            <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                                Status
                                            </label>
                                            <select
                                                value={singleForm.status}
                                                onChange={(e) => setSingleForm({ ...singleForm, status: e.target.value })}
                                                className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                            >
                                                <option value="pending">Pending</option>
                                                <option value="submitted">Submitted</option>
                                                <option value="verified">Verified</option>
                                                <option value="error">Error</option>
                                            </select>
                                            {errors?.status && <p className="mt-1 text-sm text-[#F04438]">{errors.status}</p>}
                                            <p className="mt-1 text-xs text-[var(--admin-text-dim)]">Current status</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </Card>

                        {/* SEO Information */}
                        <Card variant="elevated">
                            <div className="p-6">
                                <div className="flex items-center gap-3 mb-6">
                                    <div className="flex items-center justify-center w-10 h-10 rounded-xl bg-[#12B76A]/15">
                                        <svg className="h-5 w-5 text-[#12B76A]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 className="text-lg font-semibold text-[var(--admin-text)]">SEO Information</h3>
                                        <p className="text-sm text-[var(--admin-text-muted)]">Keywords and anchor text</p>
                                    </div>
                                </div>

                                <div className="space-y-6">
                                    <div>
                                        <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                            Keyword
                                        </label>
                                        <input
                                            type="text"
                                            value={singleForm.keyword}
                                            onChange={(e) => setSingleForm({ ...singleForm, keyword: e.target.value })}
                                            className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] placeholder-[var(--admin-text-muted)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                            placeholder="Target keyword"
                                        />
                                        {errors?.keyword && <p className="mt-1 text-sm text-[#F04438]">{errors.keyword}</p>}
                                        <p className="mt-1 text-xs text-[var(--admin-text-dim)]">Target SEO keyword</p>
                                    </div>

                                    <div>
                                        <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                            Anchor Text
                                        </label>
                                        <input
                                            type="text"
                                            value={singleForm.anchor_text}
                                            onChange={(e) => setSingleForm({ ...singleForm, anchor_text: e.target.value })}
                                            className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] placeholder-[var(--admin-text-muted)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                            placeholder="Anchor text used in backlink"
                                        />
                                        {errors?.anchor_text && <p className="mt-1 text-sm text-[#F04438]">{errors.anchor_text}</p>}
                                        <p className="mt-1 text-xs text-[var(--admin-text-dim)]">Clickable text of the backlink</p>
                                    </div>
                                </div>
                            </div>
                        </Card>

                        {/* Authority Metrics */}
                        <Card variant="elevated">
                            <div className="p-6">
                                <div className="flex items-center gap-3 mb-6">
                                    <div className="flex items-center justify-center w-10 h-10 rounded-xl bg-[#F79009]/15">
                                        <svg className="h-5 w-5 text-[#F79009]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 className="text-lg font-semibold text-[var(--admin-text)]">Authority Metrics</h3>
                                        <p className="text-sm text-[var(--admin-text-muted)]">Page and domain authority scores</p>
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                            PA (Page Authority)
                                        </label>
                                        <input
                                            type="number"
                                            min="0"
                                            max="100"
                                            value={singleForm.pa}
                                            onChange={(e) => setSingleForm({ ...singleForm, pa: e.target.value })}
                                            className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] placeholder-[var(--admin-text-muted)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                            placeholder="0-100"
                                        />
                                        {errors?.pa && <p className="mt-1 text-sm text-[#F04438]">{errors.pa}</p>}
                                        <p className="mt-1 text-xs text-[var(--admin-text-dim)]">Moz Page Authority score</p>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-semibold text-[var(--admin-text)] mb-2">
                                            DA (Domain Authority)
                                        </label>
                                        <input
                                            type="number"
                                            min="0"
                                            max="100"
                                            value={singleForm.da}
                                            onChange={(e) => setSingleForm({ ...singleForm, da: e.target.value })}
                                            className="w-full px-4 py-2.5 bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg text-[var(--admin-text)] placeholder-[var(--admin-text-muted)] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/50 focus:border-[#2F6BFF] transition-all"
                                            placeholder="0-100"
                                        />
                                        {errors?.da && <p className="mt-1 text-sm text-[#F04438]">{errors.da}</p>}
                                        <p className="mt-1 text-xs text-[var(--admin-text-dim)]">Moz Domain Authority score</p>
                                    </div>
                                </div>
                            </div>
                        </Card>

                        {/* Sticky Bottom Actions (mobile) */}
                        <div className="sticky bottom-0 left-0 right-0 p-4 bg-[var(--admin-surface)] border-t border-[var(--admin-border)] flex items-center justify-end gap-3 md:hidden">
                            <Link href="/admin/backlinks">
                                <button
                                    type="button"
                                    className="px-4 py-2.5 text-sm font-medium text-[var(--admin-text)] bg-[var(--admin-surface)] border border-[var(--admin-border)] rounded-lg hover:bg-[var(--admin-hover-bg)] transition-all"
                                >
                                    Cancel
                                </button>
                            </Link>
                            <button
                                type="submit"
                                className="px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-[#2F6BFF] to-[#2457D6] hover:from-[#2457D6] hover:to-[#1E4BBD] rounded-lg shadow-lg shadow-[#2F6BFF]/20 transition-all"
                            >
                                Add Backlink
                            </button>
                        </div>
                    </form>
                )}

                {/* Bulk Import Form */}
                {activeTab === 'bulk' && (
                    <form id="bulk-form" onSubmit={handleBulkSubmit} className="space-y-6">
                        {/* CSV Upload */}
                        <Card variant="elevated">
                            <div className="p-6">
                                <div className="flex items-center gap-3 mb-6">
                                    <div className="flex items-center justify-center w-10 h-10 rounded-xl bg-[#7F56D9]/15">
                                        <svg className="h-5 w-5 text-[#7F56D9]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 className="text-lg font-semibold text-[var(--admin-text)]">CSV Upload</h3>
                                        <p className="text-sm text-[var(--admin-text-muted)]">Upload bulk backlinks via CSV file</p>
                                    </div>
                                </div>

                                <div className="mt-1 flex justify-center px-6 pt-8 pb-8 border-2 border-dashed border-[var(--admin-border)] rounded-xl hover:border-[var(--admin-border-hover)] bg-[var(--admin-hover-bg)] transition-colors">
                                    <div className="space-y-3 text-center">
                                        {bulkForm.csv_file ? (
                                            <div>
                                                <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-[var(--admin-surface-2)] mb-3">
                                                    <svg className="h-8 w-8 text-[#10B981]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                </div>
                                                <p className="text-sm text-[var(--admin-text)] font-medium">{bulkForm.csv_file.name}</p>
                                                <p className="text-xs text-[var(--admin-text-dim)] mt-1">{(bulkForm.csv_file.size / 1024).toFixed(2)} KB</p>
                                                <button
                                                    type="button"
                                                    onClick={() => setBulkForm({ ...bulkForm, csv_file: null })}
                                                    className="text-sm text-[#F04438] hover:text-[#F04438]/80 mt-3 font-medium transition-colors"
                                                >
                                                    Remove file
                                                </button>
                                            </div>
                                        ) : (
                                            <>
                                                <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-[var(--admin-surface-2)] mb-3">
                                                    <svg className="h-8 w-8 text-[var(--admin-text-dim)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                                    </svg>
                                                </div>
                                                <div className="text-sm text-[var(--admin-text)]">
                                                    <label className="relative cursor-pointer font-semibold text-[#5B8AFF] hover:text-[#2F6BFF] transition-colors">
                                                        <span>Upload a file</span>
                                                        <input
                                                            type="file"
                                                            accept=".csv,.txt"
                                                            className="sr-only"
                                                            onChange={handleFileChange}
                                                            required
                                                        />
                                                    </label>
                                                    <span className="text-[var(--admin-text-muted)]"> or drag and drop</span>
                                                </div>
                                                <p className="text-xs text-[var(--admin-text-dim)]">CSV or TXT up to 10MB</p>
                                            </>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </Card>

                        {/* Info Note */}
                        <div className="p-4 rounded-lg bg-[#5B8AFF]/10 border border-[#5B8AFF]/30">
                            <div className="flex gap-3">
                                <svg className="h-5 w-5 text-[#5B8AFF] flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div>
                                    <p className="text-sm font-semibold text-[#5B8AFF]">Note</p>
                                    <p className="text-sm text-[var(--admin-text-muted)] mt-1">
                                        This imports actual backlinks. For bulk importing backlink opportunities (sites), use the <strong>Backlink Opportunities</strong> page.
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* CSV Format Guide */}
                        <Card variant="elevated">
                            <div className="p-6">
                                <h4 className="text-sm font-semibold text-[var(--admin-text)] mb-4">CSV Format Guide</h4>
                                <div className="space-y-3">
                                    <div>
                                        <p className="text-sm text-[var(--admin-text)] mb-1">Required columns:</p>
                                        <code className="text-sm text-[#5B8AFF] bg-[var(--admin-hover-bg)] px-2 py-1 rounded">url</code>
                                    </div>
                                    <div>
                                        <p className="text-sm text-[var(--admin-text)] mb-1">Optional columns:</p>
                                        <div className="flex flex-wrap gap-2">
                                            <code className="text-sm text-[var(--admin-text-muted)] bg-[var(--admin-hover-bg)] px-2 py-1 rounded">type</code>
                                            <code className="text-sm text-[var(--admin-text-muted)] bg-[var(--admin-hover-bg)] px-2 py-1 rounded">keyword</code>
                                            <code className="text-sm text-[var(--admin-text-muted)] bg-[var(--admin-hover-bg)] px-2 py-1 rounded">anchor_text</code>
                                            <code className="text-sm text-[var(--admin-text-muted)] bg-[var(--admin-hover-bg)] px-2 py-1 rounded">pa</code>
                                            <code className="text-sm text-[var(--admin-text-muted)] bg-[var(--admin-hover-bg)] px-2 py-1 rounded">da</code>
                                            <code className="text-sm text-[var(--admin-text-muted)] bg-[var(--admin-hover-bg)] px-2 py-1 rounded">status</code>
                                        </div>
                                    </div>
                                    <div>
                                        <p className="text-sm text-[var(--admin-text)] mb-2">Example:</p>
                                        <pre className="text-xs bg-[var(--admin-hover-bg)] p-4 rounded-lg border border-[var(--admin-border)] overflow-x-auto">
<code className="text-[var(--admin-text)]">{`url,type,keyword,anchor_text,pa,da,status
https://example.com/page,comment,seo keyword,click here,45,60,pending
https://example2.com/blog,forum,marketing,learn more,50,65,verified`}</code>
                                        </pre>
                                    </div>
                                    <p className="text-xs text-[var(--admin-text-dim)]">
                                        <strong>Column names:</strong> Use lowercase (url, pa, da) or mixed case (URL, PA, DA) - both work.
                                    </p>
                                </div>
                            </div>
                        </Card>

                        {/* Sticky Bottom Actions (mobile) */}
                        <div className="sticky bottom-0 left-0 right-0 p-4 bg-[var(--admin-surface)] border-t border-[var(--admin-border)] flex items-center justify-end gap-3 md:hidden">
                            <Link href="/admin/backlinks">
                                <button
                                    type="button"
                                    className="px-4 py-2.5 text-sm font-medium text-[var(--admin-text)] bg-[var(--admin-surface)] border border-[var(--admin-border)] rounded-lg hover:bg-[var(--admin-hover-bg)] transition-all"
                                >
                                    Cancel
                                </button>
                            </Link>
                            <button
                                type="submit"
                                disabled={uploading}
                                className="px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-[#2F6BFF] to-[#2457D6] hover:from-[#2457D6] hover:to-[#1E4BBD] rounded-lg shadow-lg shadow-[#2F6BFF]/20 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {uploading ? 'Uploading...' : 'Import CSV'}
                            </button>
                        </div>
                    </form>
                )}
            </div>
        </AdminLayout>
    );
}
