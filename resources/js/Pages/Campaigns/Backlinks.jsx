import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '../../Components/Layout/AppLayout';
import Card from '../../Components/Shared/Card';
import Button from '../../Components/Shared/Button';
import Input from '../../Components/Shared/Input';

export default function CampaignBacklinks({ campaign, backlinks, stats, filters }) {
    const [search, setSearch] = useState(filters?.search || '');
    const [statusFilter, setStatusFilter] = useState(filters?.status || '');
    const [typeFilter, setTypeFilter] = useState(filters?.type || '');

    const handleFilter = () => {
        router.get(`/campaign/${campaign.id}/backlinks`, {
            search: search || undefined,
            status: statusFilter || undefined,
            type: typeFilter || undefined,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const getStatusBadge = (status) => {
        const colors = {
            verified: 'bg-green-100 text-green-800',
            pending: 'bg-yellow-100 text-yellow-800',
            submitted: 'bg-blue-100 text-blue-800',
            error: 'bg-red-100 text-red-800',
        };
        return (
            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${colors[status] || colors.pending}`}>
                {status}
            </span>
        );
    };

    const getTypeBadge = (type) => {
        return (
            <span className="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 capitalize">
                {type}
            </span>
        );
    };

    return (
        <AppLayout header={`Backlinks - ${campaign.name}`}>
            <div className="space-y-6">
                {/* Stats Cards */}
                <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-5">
                    <Card>
                        <div className="text-center">
                            <div className="text-2xl font-bold text-gray-900">{stats.total}</div>
                            <div className="text-sm text-gray-500">Total</div>
                        </div>
                    </Card>
                    <Card>
                        <div className="text-center">
                            <div className="text-2xl font-bold text-green-600">{stats.verified}</div>
                            <div className="text-sm text-gray-500">Verified</div>
                        </div>
                    </Card>
                    <Card>
                        <div className="text-center">
                            <div className="text-2xl font-bold text-yellow-600">{stats.pending}</div>
                            <div className="text-sm text-gray-500">Pending</div>
                        </div>
                    </Card>
                    <Card>
                        <div className="text-center">
                            <div className="text-2xl font-bold text-blue-600">{stats.submitted}</div>
                            <div className="text-sm text-gray-500">Submitted</div>
                        </div>
                    </Card>
                    <Card>
                        <div className="text-center">
                            <div className="text-2xl font-bold text-red-600">{stats.error}</div>
                            <div className="text-sm text-gray-500">Errors</div>
                        </div>
                    </Card>
                </div>

                {/* Filters */}
                <Card title="Filters">
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <Input
                            label="Search"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="URL, keyword, anchor text..."
                        />
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select
                                value={statusFilter}
                                onChange={(e) => setStatusFilter(e.target.value)}
                                className="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            >
                                <option value="">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="submitted">Submitted</option>
                                <option value="verified">Verified</option>
                                <option value="error">Error</option>
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Type</label>
                            <select
                                value={typeFilter}
                                onChange={(e) => setTypeFilter(e.target.value)}
                                className="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            >
                                <option value="">All Types</option>
                                <option value="comment">Comment</option>
                                <option value="profile">Profile</option>
                                <option value="forum">Forum</option>
                                <option value="guestposting">Guest Posting</option>
                            </select>
                        </div>
                        <div className="flex items-end">
                            <Button variant="primary" onClick={handleFilter} className="w-full">
                                Apply Filters
                            </Button>
                        </div>
                    </div>
                </Card>

                {/* Backlinks Table */}
                <Card title="Backlinks">
                    {backlinks.data && backlinks.data.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">URL</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Keyword</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Anchor Text</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {backlinks.data.map((backlink) => (
                                        <tr key={backlink.id}>
                                            <td className="px-6 py-4">
                                                <a href={backlink.url} target="_blank" rel="noopener noreferrer" className="text-indigo-600 hover:underline text-sm">
                                                    {backlink.url.length > 50 ? backlink.url.substring(0, 50) + '...' : backlink.url}
                                                </a>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                {getTypeBadge(backlink.type)}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-gray-900">{backlink.keyword || '-'}</td>
                                            <td className="px-6 py-4 text-sm text-gray-900">{backlink.anchor_text || '-'}</td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                {getStatusBadge(backlink.status)}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {new Date(backlink.created_at).toLocaleDateString()}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <p className="text-gray-500 text-center py-8">No backlinks found.</p>
                    )}

                    {/* Pagination */}
                    {backlinks.links && backlinks.links.length > 3 && (
                        <div className="mt-4 flex justify-center">
                            <div className="flex gap-2">
                                {backlinks.links.map((link, index) => (
                                    <Link
                                        key={index}
                                        href={link.url || '#'}
                                        className={`px-3 py-2 rounded-md text-sm ${
                                            link.active
                                                ? 'bg-indigo-600 text-white'
                                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                        } ${!link.url ? 'opacity-50 cursor-not-allowed' : ''}`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </Card>

                {/* Back Button */}
                <div>
                    <Link href={`/campaign/${campaign.id}`}>
                        <Button variant="outline">Back to Campaign</Button>
                    </Link>
                </div>
            </div>
        </AppLayout>
    );
}

