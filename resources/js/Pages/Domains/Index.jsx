import { Link, router } from '@inertiajs/react';
import AppLayout from '../../Components/Layout/AppLayout';
import Card from '../../Components/Shared/Card';
import Button from '../../Components/Shared/Button';

export default function DomainsIndex({ domains, stats }) {
    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this domain?')) {
            router.delete(`/domains/${id}`);
        }
    };

    const getStatusBadge = (status) => {
        return (
            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${
                status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
            }`}>
                {status}
            </span>
        );
    };

    return (
        <AppLayout header="Domain Management">
            <div className="space-y-6">
                {/* Statistics */}
                {stats && (
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <Card className="bg-white border border-gray-200 shadow-md">
                            <div className="p-4">
                                <p className="text-gray-600 text-xs font-medium mb-1">Total Domains</p>
                                <p className="text-2xl font-bold text-gray-900">{stats.total_domains || 0}</p>
                                {stats.max_domains !== null && (
                                    <p className="text-gray-500 text-xs mt-1">of {stats.max_domains} allowed</p>
                                )}
                            </div>
                        </Card>
                    </div>
                )}

                <div className="flex justify-between items-center">
                    <h1 className="text-2xl font-bold text-gray-900">Your Domains</h1>
                    <Link href="/domains/create">
                        <Button variant="primary" disabled={stats && !stats.can_add_more}>
                            {stats && !stats.can_add_more ? 'Limit Reached' : '➕ Add Domain'}
                        </Button>
                    </Link>
                </div>

                {stats && !stats.can_add_more && (
                    <div className="p-4 bg-yellow-50 border border-yellow-200 rounded-md">
                        <p className="text-sm text-yellow-800">
                            ⚠️ You've reached your plan's domain limit ({stats.max_domains}). Upgrade your plan to add more domains.
                        </p>
                    </div>
                )}

                {domains && domains.length > 0 ? (
                    <div className="grid grid-cols-1 gap-6">
                        {domains.map((domain) => (
                            <Card key={domain.id}>
                                <div className="flex items-center justify-between">
                                    <div className="flex-1">
                                        <div className="flex items-center gap-3 mb-2">
                                            <h3 className="text-lg font-semibold text-gray-900">{domain.name}</h3>
                                            {getStatusBadge(domain.status)}
                                        </div>
                                        <div className="text-sm text-gray-600 space-y-1">
                                            <p><strong>Campaigns:</strong> {domain.campaigns_count || 0}</p>
                                            <p><strong>Total Backlinks:</strong> {domain.total_backlinks || 0}</p>
                                            <p><strong>Created:</strong> {new Date(domain.created_at).toLocaleDateString()}</p>
                                        </div>
                                    </div>
                                    <div className="flex gap-2">
                                        <Link href={`/domains/${domain.id}/edit`}>
                                            <Button variant="outline">Edit</Button>
                                        </Link>
                                        <Button
                                            variant="outline"
                                            onClick={() => handleDelete(domain.id)}
                                        >
                                            Delete
                                        </Button>
                                    </div>
                                </div>
                            </Card>
                        ))}
                    </div>
                ) : (
                    <Card>
                        <div className="text-center py-12">
                            <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLineCap="round" strokeLineJoin="round" strokeWidth={2} d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                            </svg>
                            <h3 className="mt-2 text-sm font-medium text-gray-900">No domains</h3>
                            <p className="mt-1 text-sm text-gray-500">Get started by adding your first domain.</p>
                            <div className="mt-6">
                                <Link href="/domains/create">
                                    <Button variant="primary">Add Domain</Button>
                                </Link>
                            </div>
                        </div>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}

