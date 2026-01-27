import { useState } from 'react';
import AdminLayout from '@/Components/Layout/AdminLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';
import Input from '@/Components/Shared/Input';
import Modal from '@/Components/Shared/Modal';
import { router, usePage } from '@inertiajs/react';

export default function AdminProxiesIndex({ proxies, stats, countries, filters = {} }) {
    const { flash } = usePage().props;
    const [showModal, setShowModal] = useState(false);
    const [editingProxy, setEditingProxy] = useState(null);
    const [deletingId, setDeletingId] = useState(null);
    const [formData, setFormData] = useState({
        host: '',
        port: '',
        username: '',
        password: '',
        type: 'http',
        country: '',
        status: 'active',
    });
    const [search, setSearch] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || '');
    const [typeFilter, setTypeFilter] = useState(filters.type || '');
    const [countryFilter, setCountryFilter] = useState(filters.country || '');

    const handleFilter = () => {
        router.get('/admin/proxies', {
            search: search || undefined,
            status: statusFilter || undefined,
            type: typeFilter || undefined,
            country: countryFilter || undefined,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleOpenModal = (proxy = null) => {
        if (proxy) {
            setEditingProxy(proxy);
            setFormData({
                host: proxy.host,
                port: proxy.port,
                username: proxy.username || '',
                password: '', // Don't show password
                type: proxy.type,
                country: proxy.country || '',
                status: proxy.status,
            });
        } else {
            setEditingProxy(null);
            setFormData({
                host: '',
                port: '',
                username: '',
                password: '',
                type: 'http',
                country: '',
                status: 'active',
            });
        }
        setShowModal(true);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        const url = editingProxy 
            ? `/admin/proxies/${editingProxy.id}`
            : '/admin/proxies';
        const method = editingProxy ? 'put' : 'post';

        router[method](url, formData, {
            preserveScroll: true,
            onSuccess: () => {
                setShowModal(false);
                setEditingProxy(null);
            },
        });
    };

    const handleDelete = (proxyId) => {
        if (window.confirm('Are you sure you want to delete this proxy?')) {
            setDeletingId(proxyId);
            router.delete(`/admin/proxies/${proxyId}`, {
                onFinish: () => setDeletingId(null),
            });
        }
    };

    const handleResetErrors = (proxyId) => {
        router.post(`/admin/proxies/${proxyId}/reset-errors`, {}, {
            preserveScroll: true,
        });
    };

    const handleTest = (proxyId) => {
        router.post(`/admin/proxies/${proxyId}/test`, {}, {
            preserveScroll: true,
        });
    };

    return (
        <AdminLayout header="Proxy Management">
            <div className="space-y-6">
                {/* Success/Error Messages */}
                {flash?.success && (
                    <div className="p-4 bg-green-50 border border-green-200 rounded-md">
                        <p className="text-sm text-green-800">{flash.success}</p>
                    </div>
                )}

                {/* Stats Cards */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6">
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
                    <Card className="bg-white border border-yellow-200 shadow-md">
                        <div className="p-4">
                            <p className="text-yellow-600 text-xs font-medium mb-1">Disabled</p>
                            <p className="text-2xl font-bold text-yellow-900">{stats?.disabled || 0}</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-red-200 shadow-md">
                        <div className="p-4">
                            <p className="text-red-600 text-xs font-medium mb-1">Blacklisted</p>
                            <p className="text-2xl font-bold text-red-900">{stats?.blacklisted || 0}</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-green-200 shadow-md">
                        <div className="p-4">
                            <p className="text-green-600 text-xs font-medium mb-1">Healthy</p>
                            <p className="text-2xl font-bold text-green-900">{stats?.healthy || 0}</p>
                        </div>
                    </Card>
                    <Card className="bg-white border border-red-200 shadow-md">
                        <div className="p-4">
                            <p className="text-red-600 text-xs font-medium mb-1">Unhealthy</p>
                            <p className="text-2xl font-bold text-red-900">{stats?.unhealthy || 0}</p>
                        </div>
                    </Card>
                </div>

                {/* Filters & Add Button */}
                <div className="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
                    <div className="flex-1 grid grid-cols-1 md:grid-cols-4 gap-4">
                        <Input
                            type="text"
                            placeholder="Search host, username..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            onKeyPress={(e) => e.key === 'Enter' && handleFilter()}
                        />
                        <select
                            value={statusFilter}
                            onChange={(e) => setStatusFilter(e.target.value)}
                            className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500"
                        >
                            <option value="">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="disabled">Disabled</option>
                            <option value="blacklisted">Blacklisted</option>
                        </select>
                        <select
                            value={typeFilter}
                            onChange={(e) => setTypeFilter(e.target.value)}
                            className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500"
                        >
                            <option value="">All Types</option>
                            <option value="http">HTTP</option>
                            <option value="https">HTTPS</option>
                            <option value="socks5">SOCKS5</option>
                        </select>
                        <select
                            value={countryFilter}
                            onChange={(e) => setCountryFilter(e.target.value)}
                            className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500"
                        >
                            <option value="">All Countries</option>
                            {countries?.map((country) => (
                                <option key={country} value={country}>{country}</option>
                            ))}
                        </select>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="secondary" onClick={handleFilter}>🔍 Filter</Button>
                        <Button variant="primary" onClick={() => handleOpenModal()}>➕ Add Proxy</Button>
                    </div>
                </div>

                {/* Proxies Table */}
                <Card className="bg-white border border-gray-200 shadow-md">
                    {proxies?.data && proxies.data.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Host:Port</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Type</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Country</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Status</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Errors</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Last Used</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {proxies.data.map((proxy) => (
                                        <tr key={proxy.id} className="hover:bg-gray-50 transition-colors">
                                            <td className="px-4 py-3 whitespace-nowrap">
                                                <div className="text-sm font-medium text-gray-900">{proxy.host}:{proxy.port}</div>
                                                {proxy.username && (
                                                    <div className="text-xs text-gray-500">{proxy.username}</div>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-600 uppercase">{proxy.type}</td>
                                            <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-600">{proxy.country || '-'}</td>
                                            <td className="px-4 py-3 whitespace-nowrap">
                                                <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                                                    proxy.status === 'active' ? 'bg-green-100 text-green-800' :
                                                    proxy.status === 'disabled' ? 'bg-yellow-100 text-yellow-800' :
                                                    'bg-red-100 text-red-800'
                                                }`}>
                                                    {proxy.status}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 whitespace-nowrap">
                                                <span className={`text-sm font-semibold ${
                                                    proxy.error_count >= 10 ? 'text-red-600' :
                                                    proxy.error_count >= 3 ? 'text-yellow-600' :
                                                    'text-green-600'
                                                }`}>
                                                    {proxy.error_count || 0}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                                {proxy.last_used_at ? new Date(proxy.last_used_at).toLocaleDateString() : 'Never'}
                                            </td>
                                            <td className="px-4 py-3 whitespace-nowrap text-sm">
                                                <div className="flex items-center gap-2">
                                                    <button
                                                        onClick={() => handleOpenModal(proxy)}
                                                        className="text-blue-600 hover:text-blue-900"
                                                        title="Edit"
                                                    >
                                                        ✏️
                                                    </button>
                                                    {proxy.error_count > 0 && (
                                                        <button
                                                            onClick={() => handleResetErrors(proxy.id)}
                                                            className="text-green-600 hover:text-green-900"
                                                            title="Reset Errors"
                                                        >
                                                            🔄
                                                        </button>
                                                    )}
                                                    <button
                                                        onClick={() => handleTest(proxy.id)}
                                                        className="text-purple-600 hover:text-purple-900"
                                                        title="Test"
                                                    >
                                                        🧪
                                                    </button>
                                                    <button
                                                        onClick={() => handleDelete(proxy.id)}
                                                        disabled={deletingId === proxy.id}
                                                        className="text-red-600 hover:text-red-900 disabled:opacity-50"
                                                        title="Delete"
                                                    >
                                                        🗑️
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <div className="text-center py-16">
                            <div className="inline-block p-6 bg-gray-100 rounded-full mb-4">
                                <span className="text-5xl">🔌</span>
                            </div>
                            <p className="text-gray-500 font-medium">No proxies found</p>
                            <p className="text-gray-400 text-sm mt-2">Add your first proxy to get started</p>
                        </div>
                    )}

                    {/* Pagination */}
                    {proxies?.links && proxies.links.length > 3 && (
                        <div className="px-6 py-4 border-t border-gray-200 bg-gray-50">
                            <div className="flex flex-col sm:flex-row items-center justify-between gap-4">
                                <div className="text-sm text-gray-700">
                                    Showing <span className="font-medium">{proxies.from || 0}</span> to <span className="font-medium">{proxies.to || 0}</span> of <span className="font-medium">{proxies.total || 0}</span> results
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    {proxies.links.map((link, index) => (
                                        <a
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

                {/* Add/Edit Modal */}
                <Modal show={showModal} onClose={() => setShowModal(false)}>
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <h3 className="text-lg font-bold text-gray-900 mb-4">
                            {editingProxy ? 'Edit Proxy' : 'Add New Proxy'}
                        </h3>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Host *</label>
                                <Input
                                    type="text"
                                    value={formData.host}
                                    onChange={(e) => setFormData({ ...formData, host: e.target.value })}
                                    required
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Port *</label>
                                <Input
                                    type="number"
                                    min="1"
                                    max="65535"
                                    value={formData.port}
                                    onChange={(e) => setFormData({ ...formData, port: e.target.value })}
                                    required
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Username</label>
                                <Input
                                    type="text"
                                    value={formData.username}
                                    onChange={(e) => setFormData({ ...formData, username: e.target.value })}
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Password</label>
                                <Input
                                    type="password"
                                    value={formData.password}
                                    onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                                    placeholder={editingProxy ? 'Leave blank to keep current' : ''}
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Type *</label>
                                <select
                                    value={formData.type}
                                    onChange={(e) => setFormData({ ...formData, type: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500"
                                    required
                                >
                                    <option value="http">HTTP</option>
                                    <option value="https">HTTPS</option>
                                    <option value="socks5">SOCKS5</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Country</label>
                                <Input
                                    type="text"
                                    value={formData.country}
                                    onChange={(e) => setFormData({ ...formData, country: e.target.value })}
                                    placeholder="e.g., US, UK, DE"
                                />
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                            <select
                                value={formData.status}
                                onChange={(e) => setFormData({ ...formData, status: e.target.value })}
                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500"
                                required
                            >
                                <option value="active">Active</option>
                                <option value="disabled">Disabled</option>
                                <option value="blacklisted">Blacklisted</option>
                            </select>
                        </div>

                        <div className="flex gap-2 pt-4">
                            <Button type="submit" variant="primary" className="flex-1">
                                {editingProxy ? '💾 Update Proxy' : '➕ Add Proxy'}
                            </Button>
                            <Button type="button" variant="secondary" onClick={() => setShowModal(false)}>
                                Cancel
                            </Button>
                        </div>
                    </form>
                </Modal>
            </div>
        </AdminLayout>
    );
}

