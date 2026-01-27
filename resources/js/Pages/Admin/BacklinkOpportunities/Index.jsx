import { useState } from 'react';
import AdminLayout from '@/Components/Layout/AdminLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';
import Input from '@/Components/Shared/Input';
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

                {/* Stats Cards */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <div className="p-4">
                            <p className="text-gray-600 text-xs font-medium mb-1">Total</p>
                            <p className="text-2xl font-bold text-gray-900">{stats?.total || 0}</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-green-200 shadow-md">
                        <div className="p-4">
                            <p className="text-green-600 text-xs font-medium mb-1">Active</p>
                            <p className="text-2xl font-bold text-green-900">{stats?.active || 0}</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <div className="p-4">
                            <p className="text-gray-600 text-xs font-medium mb-1">Inactive</p>
                            <p className="text-2xl font-bold text-gray-900">{stats?.inactive || 0}</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-red-200 shadow-md">
                        <div className="p-4">
                            <p className="text-red-600 text-xs font-medium mb-1">Banned</p>
                            <p className="text-2xl font-bold text-red-900">{stats?.banned || 0}</p>
                        </div>
                    </Card>
                </div>

                {/* Action Bar */}
                <div className="flex justify-between items-center">
                    <h2 className="text-xl font-semibold text-gray-900">Backlink Opportunities</h2>
                    <div className="flex gap-2">
                        <Button variant="secondary" onClick={() => setShowModal(true)}>
                            📥 Import CSV
                        </Button>
                        <Link href="/admin/backlink-opportunities/create">
                            <Button variant="primary">➕ Add Opportunity</Button>
                        </Link>
                    </div>
                </div>

                {/* Filters */}
                <Card className="bg-white border border-gray-200 shadow-md">
                    <div className="p-4">
                        <div className="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-8 gap-4">
                            <div className="md:col-span-2">
                                <Input
                                    type="text"
                                    placeholder="Search URLs..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyPress={(e) => e.key === 'Enter' && handleFilter()}
                                />
                            </div>
                            <div>
                                <select
                                    value={categoryFilter}
                                    onChange={(e) => setCategoryFilter(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-900"
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
                                <Input
                                    type="number"
                                    placeholder="PA Min"
                                    value={paMin}
                                    onChange={(e) => setPaMin(e.target.value)}
                                    min="0"
                                    max="100"
                                />
                            </div>
                            <div>
                                <Input
                                    type="number"
                                    placeholder="PA Max"
                                    value={paMax}
                                    onChange={(e) => setPaMax(e.target.value)}
                                    min="0"
                                    max="100"
                                />
                            </div>
                            <div>
                                <Input
                                    type="number"
                                    placeholder="DA Min"
                                    value={daMin}
                                    onChange={(e) => setDaMin(e.target.value)}
                                    min="0"
                                    max="100"
                                />
                            </div>
                            <div>
                                <Input
                                    type="number"
                                    placeholder="DA Max"
                                    value={daMax}
                                    onChange={(e) => setDaMax(e.target.value)}
                                    min="0"
                                    max="100"
                                />
                            </div>
                            <div>
                                <select
                                    value={statusFilter}
                                    onChange={(e) => setStatusFilter(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-900"
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
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-900"
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
                            <Button variant="primary" onClick={handleFilter} className="flex-1">
                                🔍 Filter
                            </Button>
                            <Button variant="secondary" onClick={handleExport}>
                                📥 Export CSV
                            </Button>
                        </div>
                    </div>
                </Card>

                {/* Opportunities Table */}
                <Card className="bg-white border border-gray-200 shadow-md">
                    {opportunities?.data && opportunities.data.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">URL</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">PA</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">DA</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Categories</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Type</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Status</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {opportunities.data.map((opp) => (
                                        <tr key={opp.id} className="hover:bg-gray-50 transition-colors">
                                            <td className="px-4 py-3 text-sm">
                                                <a href={opp.url} target="_blank" rel="noopener noreferrer" className="text-gray-900 hover:text-gray-700 break-all max-w-xs truncate block">
                                                    {opp.url}
                                                </a>
                                            </td>
                                            <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{opp.pa !== null ? opp.pa : 'N/A'}</td>
                                            <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{opp.da !== null ? opp.da : 'N/A'}</td>
                                            <td className="px-4 py-3 text-sm">
                                                <div className="flex flex-wrap gap-1">
                                                    {opp.categories?.slice(0, 2).map((cat) => (
                                                        <span key={cat.id} className="px-2 py-1 text-xs font-medium rounded bg-gray-100 text-gray-800">
                                                            {cat.name}
                                                        </span>
                                                    ))}
                                                    {opp.categories?.length > 2 && (
                                                        <span className="px-2 py-1 text-xs font-medium rounded bg-gray-100 text-gray-800">
                                                            +{opp.categories.length - 2}
                                                        </span>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-600 capitalize">{opp.site_type}</td>
                                            <td className="px-4 py-3 whitespace-nowrap">
                                                <span className={`px-2 py-1 text-xs font-medium rounded ${
                                                    opp.status === 'active' ? 'bg-green-100 text-green-800' :
                                                    opp.status === 'inactive' ? 'bg-gray-100 text-gray-800' :
                                                    'bg-red-100 text-red-800'
                                                }`}>
                                                    {opp.status}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 whitespace-nowrap text-sm">
                                                <Link href={`/admin/backlink-opportunities/${opp.id}/edit`} className="text-gray-900 hover:text-gray-700">
                                                    ✏️ Edit
                                                </Link>
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
                            <p className="text-gray-500 font-medium">No opportunities found</p>
                            <p className="text-gray-400 text-sm mt-2">Import CSV or add manually to get started</p>
                        </div>
                    )}

                    {/* Pagination */}
                    {opportunities?.links && opportunities.links.length > 3 && (
                        <div className="px-6 py-4 border-t border-gray-200 bg-gray-50">
                            <div className="flex flex-col sm:flex-row items-center justify-between gap-4">
                                <div className="text-sm text-gray-700">
                                    Showing <span className="font-medium">{opportunities.from || 0}</span> to <span className="font-medium">{opportunities.to || 0}</span> of <span className="font-medium">{opportunities.total || 0}</span> results
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    {opportunities.links.map((link, index) => (
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

            {/* CSV Import Modal */}
            {showModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
                    <div className="bg-white rounded-lg shadow-xl max-w-2xl w-full">
                        <div className="p-6">
                            <div className="flex justify-between items-center mb-6">
                                <h3 className="text-xl font-bold text-gray-900">Import CSV</h3>
                                <button
                                    onClick={() => {
                                        setShowModal(false);
                                        setCsvFile(null);
                                    }}
                                    className="text-gray-400 hover:text-gray-600"
                                >
                                    <i className="bi bi-x-lg text-xl"></i>
                                </button>
                            </div>

                            <form onSubmit={handleCsvImport} className="space-y-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        CSV File <span className="text-red-500">*</span>
                                    </label>
                                    <div className="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:border-gray-400 transition-colors">
                                        <div className="space-y-1 text-center">
                                            {csvFile ? (
                                                <div>
                                                    <i className="bi bi-file-earmark-spreadsheet text-4xl text-gray-400"></i>
                                                    <p className="text-sm text-gray-600 mt-2">{csvFile.name}</p>
                                                    <button
                                                        type="button"
                                                        onClick={() => setCsvFile(null)}
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
                                                                onChange={(e) => setCsvFile(e.target.files[0])}
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

                                <div className="bg-gray-50 p-4 rounded-md">
                                    <p className="text-sm font-medium text-gray-900 mb-2">CSV Format:</p>
                                    <p className="text-xs text-gray-600 mb-2">Required columns: <strong>url</strong>, <strong>categories</strong></p>
                                    <p className="text-xs text-gray-600 mb-2">Optional columns: <strong>pa</strong>, <strong>da</strong>, <strong>site_type</strong>, <strong>status</strong></p>
                                    <p className="text-xs text-gray-500 mt-2">Example:</p>
                                    <pre className="text-xs bg-white p-2 rounded border border-gray-200 mt-1">
{`url,pa,da,categories,site_type,status
https://example.com/page,45,60,Business|SEO,comment,active`}
                                    </pre>
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
                                    <Button 
                                        type="button" 
                                        variant="secondary" 
                                        onClick={() => {
                                            setShowModal(false);
                                            setCsvFile(null);
                                        }}
                                    >
                                        Cancel
                                    </Button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}

