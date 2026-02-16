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
                    <div className="p-4 rounded-lg bg-[#12B76A]/10 border border-[#12B76A]/30">
                        <p className="text-sm text-[#12B76A] font-medium">{flash.success}</p>
                    </div>
                )}

                {/* Stats Cards - Dashboard-like 3×2 grid */}
                <div className="stats-grid stats-grid-proxy">
                    <div className="stat-card">
                        <div>
                            <p className="stat-title">Total</p>
                            <p className="stat-value">{stats?.total || 0}</p>
                        </div>
                        <div className="stat-iconWrap stat-iconWrap-neutral">
                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                        </div>
                    </div>
                    <div className="stat-card">
                        <div>
                            <p className="stat-title">Active</p>
                            <p className="stat-value stat-value-success">{stats?.active || 0}</p>
                        </div>
                        <div className="stat-iconWrap stat-iconWrap-success">
                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                    </div>
                    <div className="stat-card">
                        <div>
                            <p className="stat-title">Disabled</p>
                            <p className="stat-value stat-value-warning">{stats?.disabled || 0}</p>
                        </div>
                        <div className="stat-iconWrap stat-iconWrap-warning">
                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                        </div>
                    </div>
                    <div className="stat-card">
                        <div>
                            <p className="stat-title">Blacklisted</p>
                            <p className="stat-value stat-value-danger">{stats?.blacklisted || 0}</p>
                        </div>
                        <div className="stat-iconWrap stat-iconWrap-danger">
                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                        </div>
                    </div>
                    <div className="stat-card">
                        <div>
                            <p className="stat-title">Healthy</p>
                            <p className="stat-value stat-value-success">{stats?.healthy || 0}</p>
                        </div>
                        <div className="stat-iconWrap stat-iconWrap-success">
                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                        </div>
                    </div>
                    <div className="stat-card">
                        <div>
                            <p className="stat-title">Unhealthy</p>
                            <p className="stat-value stat-value-danger">{stats?.unhealthy || 0}</p>
                        </div>
                        <div className="stat-iconWrap stat-iconWrap-danger">
                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                    </div>
                </div>

                {/* Filters & Add Button - Panel card */}
                <Card variant="elevated">
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
                            className="admin-select px-3 py-2 rounded-lg"
                        >
                            <option value="">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="disabled">Disabled</option>
                            <option value="blacklisted">Blacklisted</option>
                        </select>
                        <select
                            value={typeFilter}
                            onChange={(e) => setTypeFilter(e.target.value)}
                            className="admin-select px-3 py-2 rounded-lg"
                        >
                            <option value="">All Types</option>
                            <option value="http">HTTP</option>
                            <option value="https">HTTPS</option>
                            <option value="socks5">SOCKS5</option>
                        </select>
                        <select
                            value={countryFilter}
                            onChange={(e) => setCountryFilter(e.target.value)}
                            className="admin-select px-3 py-2 rounded-lg"
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
                </Card>

                {/* Proxies Table */}
                <Card variant="elevated">
                    {proxies?.data && proxies.data.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="admin-table min-w-full">
                                <thead>
                                    <tr>
                                        <th>Host:Port</th>
                                        <th>Type</th>
                                        <th>Country</th>
                                        <th>Status</th>
                                        <th>Errors</th>
                                        <th>Last Used</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {proxies.data.map((proxy) => (
                                        <tr key={proxy.id}>
                                            <td className="whitespace-nowrap">
                                                <div className="text-sm font-medium text-[var(--admin-text)]">{proxy.host}:{proxy.port}</div>
                                                {proxy.username && (
                                                    <div className="text-xs text-[var(--admin-text-muted)]">{proxy.username}</div>
                                                )}
                                            </td>
                                            <td className="whitespace-nowrap text-sm text-[var(--admin-text-muted)] uppercase">{proxy.type}</td>
                                            <td className="whitespace-nowrap text-sm text-[var(--admin-text-muted)]">{proxy.country || '-'}</td>
                                            <td className="whitespace-nowrap">
                                                <span className={`admin-badge ${
                                                    proxy.status === 'active' ? 'admin-badge-success' :
                                                    proxy.status === 'disabled' ? 'admin-badge-warning' :
                                                    'admin-badge-danger'
                                                }`}>
                                                    {proxy.status}
                                                </span>
                                            </td>
                                            <td className="whitespace-nowrap">
                                                <span className={`text-sm font-semibold ${
                                                    proxy.error_count >= 10 ? 'admin-text-danger' :
                                                    proxy.error_count >= 3 ? 'admin-text-warning' :
                                                    'admin-text-success'
                                                }`}>
                                                    {proxy.error_count || 0}
                                                </span>
                                            </td>
                                            <td className="whitespace-nowrap text-sm text-[var(--admin-text-muted)]">
                                                {proxy.last_used_at ? new Date(proxy.last_used_at).toLocaleDateString() : 'Never'}
                                            </td>
                                            <td className="whitespace-nowrap text-sm">
                                                <div className="flex items-center gap-2">
                                                    <button onClick={() => handleOpenModal(proxy)} className="admin-link" title="Edit">✏️</button>
                                                    {proxy.error_count > 0 && (
                                                        <button onClick={() => handleResetErrors(proxy.id)} className="admin-text-success hover:opacity-80" title="Reset Errors">🔄</button>
                                                    )}
                                                    <button onClick={() => handleTest(proxy.id)} className="text-purple-600 dark:text-purple-400 hover:opacity-80" title="Test">🧪</button>
                                                    <button onClick={() => handleDelete(proxy.id)} disabled={deletingId === proxy.id} className="admin-text-danger hover:opacity-80 disabled:opacity-50" title="Delete">🗑️</button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <div className="text-center py-16">
                            <div className="inline-block p-6 rounded-full mb-4 bg-[var(--admin-hover-bg)]">
                                <span className="text-5xl">🔌</span>
                            </div>
                            <p className="text-[var(--admin-text)] font-medium">No proxies found</p>
                            <p className="text-[var(--admin-text-muted)] text-sm mt-2">Add your first proxy to get started</p>
                        </div>
                    )}

                    {/* Pagination */}
                    {proxies?.links && proxies.links.length > 3 && (
                        <div className="px-6 py-4 border-t border-[var(--admin-border)] bg-[var(--admin-surface-2)]">
                            <div className="flex flex-col sm:flex-row items-center justify-between gap-4">
                                <div className="text-sm text-[var(--admin-text-muted)]">
                                    Showing <span className="font-medium text-[var(--admin-text)]">{proxies.from || 0}</span> to <span className="font-medium text-[var(--admin-text)]">{proxies.to || 0}</span> of <span className="font-medium text-[var(--admin-text)]">{proxies.total || 0}</span> results
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    {proxies.links.map((link, index) => (
                                        <a
                                            key={index}
                                            href={link.url || '#'}
                                            className={`px-3 py-2 text-sm font-medium rounded-lg transition-colors ${
                                                link.active ? 'bg-[var(--admin-primary)] text-white' : 'bg-[var(--admin-surface)] text-[var(--admin-text)] hover:bg-[var(--admin-hover-bg)] border border-[var(--admin-border)]'
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
                        <h3 className="text-lg font-bold text-[var(--admin-text)] mb-4">
                            {editingProxy ? 'Edit Proxy' : 'Add New Proxy'}
                        </h3>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-[var(--admin-text)] mb-1">Host *</label>
                                <Input
                                    type="text"
                                    value={formData.host}
                                    onChange={(e) => setFormData({ ...formData, host: e.target.value })}
                                    required
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-[var(--admin-text)] mb-1">Port *</label>
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
                                <label className="block text-sm font-medium text-[var(--admin-text)] mb-1">Username</label>
                                <Input
                                    type="text"
                                    value={formData.username}
                                    onChange={(e) => setFormData({ ...formData, username: e.target.value })}
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-[var(--admin-text)] mb-1">Password</label>
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
                                <label className="block text-sm font-medium text-[var(--admin-text)] mb-1">Type *</label>
                                <select
                                    value={formData.type}
                                    onChange={(e) => setFormData({ ...formData, type: e.target.value })}
                                    className="admin-select w-full px-3 py-2 rounded-lg"
                                    required
                                >
                                    <option value="http">HTTP</option>
                                    <option value="https">HTTPS</option>
                                    <option value="socks5">SOCKS5</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-[var(--admin-text)] mb-1">Country</label>
                                <Input
                                    type="text"
                                    value={formData.country}
                                    onChange={(e) => setFormData({ ...formData, country: e.target.value })}
                                    placeholder="e.g., US, UK, DE"
                                />
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-[var(--admin-text)] mb-1">Status *</label>
                            <select
                                value={formData.status}
                                onChange={(e) => setFormData({ ...formData, status: e.target.value })}
                                className="admin-select w-full px-3 py-2 rounded-lg"
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

