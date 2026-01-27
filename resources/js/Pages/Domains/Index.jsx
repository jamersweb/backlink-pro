import { Link, router, useForm } from '@inertiajs/react';
import AppLayout from '../../Components/Layout/AppLayout';
import Card from '../../Components/Shared/Card';
import Button from '../../Components/Shared/Button';
import Input from '../../Components/Shared/Input';

export default function DomainsIndex({ domains, stats, filters }) {
    const { data, setData, get } = useForm({
        q: filters?.q || '',
    });

    const handleSearch = (e) => {
        e.preventDefault();
        get('/domains', {
            preserveState: true,
            preserveScroll: true,
        });
    };

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

    const getPlatformBadge = (platform) => {
        if (!platform) return null;
        const colors = {
            wordpress: 'bg-blue-100 text-blue-800',
            shopify: 'bg-green-100 text-green-800',
            custom: 'bg-gray-100 text-gray-800',
            webflow: 'bg-purple-100 text-purple-800',
            wix: 'bg-pink-100 text-pink-800',
            squarespace: 'bg-indigo-100 text-indigo-800',
            other: 'bg-yellow-100 text-yellow-800',
        };
        return (
            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${colors[platform] || colors.other}`}>
                {platform}
            </span>
        );
    };

    const getVerificationBadge = (status) => {
        const colors = {
            verified: 'bg-green-100 text-green-800',
            pending: 'bg-yellow-100 text-yellow-800',
            unverified: 'bg-gray-100 text-gray-800',
        };
        return (
            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${colors[status] || colors.unverified}`}>
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

                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <h1 className="text-2xl font-bold text-gray-900">Your Domains</h1>
                    <Link href="/domains/create">
                        <Button variant="primary" disabled={stats && !stats.can_add_more}>
                            {stats && !stats.can_add_more ? 'Limit Reached' : '➕ Add Domain'}
                        </Button>
                    </Link>
                </div>

                {/* Search Bar */}
                <Card>
                    <form onSubmit={handleSearch} className="flex gap-2">
                        <div className="flex-1">
                            <Input
                                type="text"
                                name="q"
                                value={data.q}
                                onChange={(e) => setData('q', e.target.value)}
                                placeholder="Search by domain name or host..."
                                className="mb-0"
                            />
                        </div>
                        <Button type="submit" variant="primary">
                            Search
                        </Button>
                        {filters?.q && (
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => {
                                    setData('q', '');
                                    router.get('/domains', {}, { preserveState: true });
                                }}
                            >
                                Clear
                            </Button>
                        )}
                    </form>
                </Card>

                {stats && !stats.can_add_more && (
                    <div className="p-4 bg-yellow-50 border border-yellow-200 rounded-md">
                        <p className="text-sm text-yellow-800">
                            ⚠️ You've reached your plan's domain limit ({stats.max_domains}). Upgrade your plan to add more domains.
                        </p>
                    </div>
                )}

                {domains && domains.data && domains.data.length > 0 ? (
                    <>
                        <div className="grid grid-cols-1 gap-6">
                            {domains.data.map((domain) => (
                                <Card key={domain.id}>
                                    <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                                        <div className="flex-1 w-full">
                                            <div className="flex flex-wrap items-center gap-2 mb-2">
                                                <Link href={`/domains/${domain.id}`} className="hover:underline">
                                                    <h3 className="text-lg font-semibold text-gray-900">{domain.name}</h3>
                                                </Link>
                                                {getStatusBadge(domain.status)}
                                                {getPlatformBadge(domain.platform)}
                                                {getVerificationBadge(domain.verification_status)}
                                            </div>
                                            {domain.host && (
                                                <p className="text-sm text-gray-500 mb-2">{domain.host}</p>
                                            )}
                                            <div className="text-sm text-gray-600 space-y-1">
                                                <p><strong>Campaigns:</strong> {domain.campaigns_count || 0}</p>
                                                <p><strong>Total Backlinks:</strong> {domain.total_backlinks || 0}</p>
                                                <p><strong>Created:</strong> {new Date(domain.created_at).toLocaleDateString()}</p>
                                            </div>
                                        </div>
                                        <div className="flex gap-2 w-full sm:w-auto">
                                            <Link href={`/domains/${domain.id}`} className="flex-1 sm:flex-none">
                                                <Button variant="primary" className="w-full sm:w-auto">View</Button>
                                            </Link>
                                            <Link href={`/domains/${domain.id}/edit`} className="flex-1 sm:flex-none">
                                                <Button variant="outline" className="w-full sm:w-auto">Edit</Button>
                                            </Link>
                                            <Button
                                                variant="outline"
                                                onClick={() => handleDelete(domain.id)}
                                                className="flex-1 sm:flex-none"
                                            >
                                                Delete
                                            </Button>
                                        </div>
                                    </div>
                                </Card>
                            ))}
                        </div>

                        {/* Pagination */}
                        {domains.links && domains.links.length > 3 && (
                            <div className="flex items-center justify-center gap-2 mt-6">
                                {domains.links.map((link, index) => (
                                    <Link
                                        key={index}
                                        href={link.url || '#'}
                                        className={`px-4 py-2 rounded-lg text-sm font-medium ${
                                            link.active
                                                ? 'bg-gray-900 text-white'
                                                : link.url
                                                ? 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'
                                                : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                        }`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        )}
                    </>
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

