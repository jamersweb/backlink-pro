import { useState } from 'react';
import AdminLayout from '../../../Components/Layout/AdminLayout';
import Card from '../../../Components/Shared/Card';
import Button from '../../../Components/Shared/Button';
import Input from '../../../Components/Shared/Input';
import { Link, router, usePage } from '@inertiajs/react';

export default function AdminBacklinksIndex({ backlinks, stats, campaigns, users, filters = {} }) {
    const { flash } = usePage().props;
    const [search, setSearch] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || '');
    const [typeFilter, setTypeFilter] = useState(filters.type || '');
    const [campaignFilter, setCampaignFilter] = useState(filters.campaign_id || '');
    const [userFilter, setUserFilter] = useState(filters.user_id || '');
    const [dateFrom, setDateFrom] = useState(filters.date_from || '');
    const [dateTo, setDateTo] = useState(filters.date_to || '');
    
    // Modal states
    const [showModal, setShowModal] = useState(false);
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

    const handleFilter = () => {
        router.get('/admin/backlinks', {
            search: search || undefined,
            status: statusFilter || undefined,
            type: typeFilter || undefined,
            campaign_id: campaignFilter || undefined,
            user_id: userFilter || undefined,
            date_from: dateFrom || undefined,
            date_to: dateTo || undefined,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleExport = () => {
        const params = new URLSearchParams({
            ...(statusFilter && { status: statusFilter }),
            ...(typeFilter && { type: typeFilter }),
            ...(campaignFilter && { campaign_id: campaignFilter }),
            ...(userFilter && { user_id: userFilter }),
        });
        window.open(`/admin/backlinks/export?${params.toString()}`, '_blank');
    };

    const handleSingleSubmit = (e) => {
        e.preventDefault();
        router.post('/admin/backlinks', singleForm, {
            preserveScroll: true,
            onSuccess: () => {
                setShowModal(false);
                setSingleForm({
                    url: '',
                    type: 'comment',
                    keyword: '',
                    anchor_text: '',
                    pa: '',
                    da: '',
                    status: 'pending',
                });
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
                setShowModal(false);
                setBulkForm({ csv_file: null });
                setUploading(false);
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
        <AdminLayout header="Backlinks Management">
            <div className="space-y-6">
                {/* Success/Error Messages */}
                {flash?.success && (
                    <div className="p-4 bg-green-50 border border-green-200 rounded-md">
                        <p className="text-sm text-green-800">{flash.success}</p>
                    </div>
                )}
                {flash?.error && (
                    <div className="p-4 bg-red-50 border border-red-200 rounded-md">
                        <p className="text-sm text-red-800">{flash.error}</p>
                    </div>
                )}
                {flash?.import_errors && flash.import_errors.length > 0 && (
                    <div className="p-4 bg-yellow-50 border border-yellow-200 rounded-md">
                        <p className="text-sm font-semibold text-yellow-800 mb-2">Import Warnings:</p>
                        <ul className="list-disc list-inside text-sm text-yellow-700 space-y-1">
                            {flash.import_errors.slice(0, 10).map((error, index) => (
                                <li key={index}>{error}</li>
                            ))}
                            {flash.import_errors.length > 10 && (
                                <li>... and {flash.import_errors.length - 10} more</li>
                            )}
                        </ul>
                    </div>
                )}

                {/* Action Bar */}
                <div className="flex justify-between items-center">
                    <h2 className="text-xl font-semibold text-gray-900">Backlinks</h2>
                    <Button variant="primary" onClick={() => setShowModal(true)}>
                        ➕ Add Link
                    </Button>
                </div>

                {/* Stats Cards */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8">
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <div className="p-4">
                            <p className="text-gray-600 text-xs font-medium mb-1">Total</p>
                            <p className="text-2xl font-bold text-gray-900">{stats?.total || 0}</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-green-200 shadow-md">
                        <div className="p-4">
                            <p className="text-green-600 text-xs font-medium mb-1">Verified</p>
                            <p className="text-2xl font-bold text-green-900">{stats?.verified || 0}</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-yellow-200 shadow-md">
                        <div className="p-4">
                            <p className="text-yellow-600 text-xs font-medium mb-1">Pending</p>
                            <p className="text-2xl font-bold text-yellow-900">{stats?.pending || 0}</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-blue-200 shadow-md">
                        <div className="p-4">
                            <p className="text-blue-600 text-xs font-medium mb-1">Submitted</p>
                            <p className="text-2xl font-bold text-blue-900">{stats?.submitted || 0}</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-red-200 shadow-md">
                        <div className="p-4">
                            <p className="text-red-600 text-xs font-medium mb-1">Error</p>
                            <p className="text-2xl font-bold text-red-900">{stats?.error || 0}</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <div className="p-4">
                            <p className="text-gray-600 text-xs font-medium mb-1">Today</p>
                            <p className="text-2xl font-bold text-gray-900">{stats?.today || 0}</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <div className="p-4">
                            <p className="text-gray-600 text-xs font-medium mb-1">This Week</p>
                            <p className="text-2xl font-bold text-gray-900">{stats?.this_week || 0}</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <div className="p-4">
                            <p className="text-gray-600 text-xs font-medium mb-1">This Month</p>
                            <p className="text-2xl font-bold text-gray-900">{stats?.this_month || 0}</p>
                        </div>
                    </Card>
                </div>

                {/* Filters */}
                <Card className="bg-white border border-gray-200 shadow-md">
                    <div className="p-4">
                        <div className="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-8 gap-4">
                            <div className="md:col-span-2">
                                <Input
                                    type="text"
                                    placeholder="Search URLs, keywords..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyPress={(e) => e.key === 'Enter' && handleFilter()}
                                />
                            </div>
                            <div>
                                <select
                                    value={statusFilter}
                                    onChange={(e) => setStatusFilter(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500"
                                >
                                    <option value="">All Statuses</option>
                                    <option value="pending">Pending</option>
                                    <option value="submitted">Submitted</option>
                                    <option value="verified">Verified</option>
                                    <option value="error">Error</option>
                                </select>
                            </div>
                            <div>
                                <select
                                    value={typeFilter}
                                    onChange={(e) => setTypeFilter(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500"
                                >
                                    <option value="">All Types</option>
                                    <option value="comment">Comment</option>
                                    <option value="profile">Profile</option>
                                    <option value="forum">Forum</option>
                                    <option value="guestposting">Guest Post</option>
                                </select>
                            </div>
                            <div>
                                <select
                                    value={campaignFilter}
                                    onChange={(e) => setCampaignFilter(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500"
                                >
                                    <option value="">All Campaigns</option>
                                    {campaigns?.map((campaign) => (
                                        <option key={campaign.id} value={campaign.id}>
                                            {campaign.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <select
                                    value={userFilter}
                                    onChange={(e) => setUserFilter(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500"
                                >
                                    <option value="">All Users</option>
                                    {users?.map((user) => (
                                        <option key={user.id} value={user.id}>
                                            {user.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Input
                                    type="date"
                                    placeholder="From Date"
                                    value={dateFrom}
                                    onChange={(e) => setDateFrom(e.target.value)}
                                />
                            </div>
                            <div>
                                <Input
                                    type="date"
                                    placeholder="To Date"
                                    value={dateTo}
                                    onChange={(e) => setDateTo(e.target.value)}
                                />
                            </div>
                        </div>
                        <div className="flex gap-2 mt-4">
                            <Button variant="primary" onClick={handleFilter} className="flex-1">
                                🔍 Filter
                            </Button>
                            <Button variant="secondary" onClick={handleExport}>
                                📥 Export CSV
                            </Button>
                        </div>
                    </div>
                </Card>

                {/* Backlinks Table */}
                <Card className="bg-white border border-gray-200 shadow-md">
                    {backlinks?.data && backlinks.data.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">ID</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Campaign</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">User</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">URL</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Type</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Keyword</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">PA</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">DA</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Status</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Created</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {backlinks.data.map((backlink) => (
                                        <tr key={backlink.id} className="hover:bg-gray-50 transition-colors">
                                            <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-600">#{backlink.id}</td>
                                            <td className="px-4 py-3 whitespace-nowrap">
                                                <Link href={`/admin/campaigns/${backlink.campaign_id}`} className="text-sm text-gray-900 hover:text-gray-700">
                                                    {backlink.campaign?.name || 'N/A'}
                                                </Link>
                                            </td>
                                            <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                                {backlink.campaign?.user?.name || 'N/A'}
                                            </td>
                                            <td className="px-4 py-3 text-sm">
                                                <a href={backlink.url} target="_blank" rel="noopener noreferrer" className="text-gray-900 hover:text-gray-700 break-all max-w-xs truncate block">
                                                    {backlink.url}
                                                </a>
                                            </td>
                                            <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-600 capitalize">{backlink.type}</td>
                                            <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{backlink.keyword || 'N/A'}</td>
                                            <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{backlink.pa !== null ? backlink.pa : 'N/A'}</td>
                                            <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{backlink.da !== null ? backlink.da : 'N/A'}</td>
                                            <td className="px-4 py-3 whitespace-nowrap">
                                                <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                                                    backlink.status === 'verified' ? 'bg-green-100 text-green-800' :
                                                    backlink.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                                    backlink.status === 'submitted' ? 'bg-blue-100 text-blue-800' :
                                                    'bg-red-100 text-red-800'
                                                }`}>
                                                    {backlink.status}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                                {new Date(backlink.created_at).toLocaleDateString()}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <div className="text-center py-16">
                            <div className="inline-block p-6 bg-gray-100 rounded-full mb-4">
                                <span className="text-5xl">🔗</span>
                            </div>
                            <p className="text-gray-500 font-medium">No backlinks found</p>
                            <p className="text-gray-400 text-sm mt-2">Backlinks will appear here once created</p>
                        </div>
                    )}

                    {/* Pagination */}
                    {backlinks?.links && backlinks.links.length > 3 && (
                        <div className="px-6 py-4 border-t border-gray-200 bg-gray-50">
                            <div className="flex flex-col sm:flex-row items-center justify-between gap-4">
                                <div className="text-sm text-gray-700">
                                    Showing <span className="font-medium">{backlinks.from || 0}</span> to <span className="font-medium">{backlinks.to || 0}</span> of <span className="font-medium">{backlinks.total || 0}</span> results
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    {backlinks.links.map((link, index) => (
                                        <Link
                                            key={index}
                                            href={link.url || '#'}
                                            className={`px-3 py-2 text-sm font-medium rounded-md transition-colors ${
                                                link.active
                                                    ? 'bg-gray-900 text-white'
                                                    : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300'
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

            {/* Add Link Modal */}
            {showModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
                    <div className="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                        <div className="p-6">
                            {/* Modal Header */}
                            <div className="flex justify-between items-center mb-6">
                                <h3 className="text-xl font-bold text-gray-900">Add Backlink</h3>
                                <button
                                    onClick={() => setShowModal(false)}
                                    className="text-gray-400 hover:text-gray-600"
                                >
                                    <i className="bi bi-x-lg text-xl"></i>
                                </button>
                            </div>

                            {/* Tabs */}
                            <div className="flex border-b border-gray-200 mb-6">
                                <button
                                    onClick={() => setActiveTab('single')}
                                    className={`px-4 py-2 font-medium text-sm ${
                                        activeTab === 'single'
                                            ? 'border-b-2 border-gray-900 text-gray-900'
                                            : 'text-gray-500 hover:text-gray-700'
                                    }`}
                                >
                                    Single Add
                                </button>
                                <button
                                    onClick={() => setActiveTab('bulk')}
                                    className={`px-4 py-2 font-medium text-sm ${
                                        activeTab === 'bulk'
                                            ? 'border-b-2 border-gray-900 text-gray-900'
                                            : 'text-gray-500 hover:text-gray-700'
                                    }`}
                                >
                                    Bulk Import (CSV)
                                </button>
                            </div>

                            {/* Single Add Form */}
                            {activeTab === 'single' && (
                                <form onSubmit={handleSingleSubmit} className="space-y-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            URL <span className="text-red-500">*</span>
                                        </label>
                                        <Input
                                            type="url"
                                            required
                                            value={singleForm.url}
                                            onChange={(e) => setSingleForm({ ...singleForm, url: e.target.value })}
                                            placeholder="https://example.com/page"
                                        />
                                    </div>

                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Type <span className="text-red-500">*</span>
                                            </label>
                                            <select
                                                required
                                                value={singleForm.type}
                                                onChange={(e) => setSingleForm({ ...singleForm, type: e.target.value })}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-900"
                                            >
                                                <option value="comment">Comment</option>
                                                <option value="profile">Profile</option>
                                                <option value="forum">Forum</option>
                                                <option value="guestposting">Guest Post</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Status
                                            </label>
                                            <select
                                                value={singleForm.status}
                                                onChange={(e) => setSingleForm({ ...singleForm, status: e.target.value })}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-900"
                                            >
                                                <option value="pending">Pending</option>
                                                <option value="submitted">Submitted</option>
                                                <option value="verified">Verified</option>
                                                <option value="error">Error</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Keyword
                                        </label>
                                        <Input
                                            type="text"
                                            value={singleForm.keyword}
                                            onChange={(e) => setSingleForm({ ...singleForm, keyword: e.target.value })}
                                            placeholder="Target keyword"
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Anchor Text
                                        </label>
                                        <Input
                                            type="text"
                                            value={singleForm.anchor_text}
                                            onChange={(e) => setSingleForm({ ...singleForm, anchor_text: e.target.value })}
                                            placeholder="Anchor text"
                                        />
                                    </div>

                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                PA (Page Authority)
                                            </label>
                                            <Input
                                                type="number"
                                                min="0"
                                                max="100"
                                                value={singleForm.pa}
                                                onChange={(e) => setSingleForm({ ...singleForm, pa: e.target.value })}
                                                placeholder="0-100"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                DA (Domain Authority)
                                            </label>
                                            <Input
                                                type="number"
                                                min="0"
                                                max="100"
                                                value={singleForm.da}
                                                onChange={(e) => setSingleForm({ ...singleForm, da: e.target.value })}
                                                placeholder="0-100"
                                            />
                                        </div>
                                    </div>

                                    <div className="flex gap-3 pt-4">
                                        <Button type="submit" variant="primary" className="flex-1">
                                            Add Backlink
                                        </Button>
                                        <Button type="button" variant="secondary" onClick={() => setShowModal(false)}>
                                            Cancel
                                        </Button>
                                    </div>
                                </form>
                            )}

                            {/* Bulk Import Form */}
                            {activeTab === 'bulk' && (
                                <form onSubmit={handleBulkSubmit} className="space-y-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            CSV File <span className="text-red-500">*</span>
                                        </label>
                                        <div className="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:border-gray-400 transition-colors">
                                            <div className="space-y-1 text-center">
                                                {bulkForm.csv_file ? (
                                                    <div>
                                                        <i className="bi bi-file-earmark-spreadsheet text-4xl text-gray-400"></i>
                                                        <p className="text-sm text-gray-600 mt-2">{bulkForm.csv_file.name}</p>
                                                        <button
                                                            type="button"
                                                            onClick={() => setBulkForm({ ...bulkForm, csv_file: null })}
                                                            className="text-sm text-gray-900 hover:text-gray-700 mt-1"
                                                        >
                                                            Remove
                                                        </button>
                                                    </div>
                                                ) : (
                                                    <>
                                                        <i className="bi bi-cloud-upload text-4xl text-gray-400"></i>
                                                        <div className="flex text-sm text-gray-600">
                                                            <label className="relative cursor-pointer bg-white rounded-md font-medium text-gray-900 hover:text-gray-700">
                                                                <span>Upload CSV file</span>
                                                                <input
                                                                    type="file"
                                                                    accept=".csv,.txt"
                                                                    className="sr-only"
                                                                    onChange={handleFileChange}
                                                                    required
                                                                />
                                                            </label>
                                                            <p className="pl-1">or drag and drop</p>
                                                        </div>
                                                        <p className="text-xs text-gray-500">CSV, TXT up to 10MB</p>
                                                    </>
                                                )}
                                            </div>
                                        </div>
                                    </div>

                                    <div className="bg-blue-50 border border-blue-200 p-3 rounded-md mb-4">
                                        <p className="text-xs text-blue-800">
                                            <i className="bi bi-info-circle mr-1"></i>
                                            <strong>Note:</strong> This imports actual backlinks. For bulk importing backlink opportunities (sites), use <strong>Backlink Opportunities</strong> page.
                                        </p>
                                    </div>

                                    <div className="bg-gray-50 p-4 rounded-md">
                                        <p className="text-sm font-medium text-gray-900 mb-2">CSV Format:</p>
                                        <p className="text-xs text-gray-600 mb-2">Required columns: <strong>url</strong></p>
                                        <p className="text-xs text-gray-600 mb-2">Optional columns: <strong>type</strong>, <strong>keyword</strong>, <strong>anchor_text</strong>, <strong>pa</strong>, <strong>da</strong>, <strong>status</strong></p>
                                        <p className="text-xs text-gray-500 mt-2">Example:</p>
                                        <pre className="text-xs bg-white p-2 rounded border border-gray-200 mt-1">
{`url,type,keyword,anchor_text,pa,da,status
https://example.com/page,comment,seo keyword,click here,45,60,pending`}
                                        </pre>
                                        <p className="text-xs text-gray-500 mt-2">
                                            <strong>Column names:</strong> Use lowercase (url, pa, da) or mixed case (PA, DA) - both work.
                                        </p>
                                    </div>

                                    <div className="flex gap-3 pt-4">
                                        <Button 
                                            type="submit" 
                                            variant="primary" 
                                            className="flex-1"
                                            disabled={uploading}
                                        >
                                            {uploading ? 'Uploading...' : 'Import CSV'}
                                        </Button>
                                        <Button type="button" variant="secondary" onClick={() => setShowModal(false)}>
                                            Cancel
                                        </Button>
                                    </div>
                                </form>
                            )}
                        </div>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}

