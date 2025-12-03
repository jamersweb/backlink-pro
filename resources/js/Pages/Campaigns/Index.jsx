import { useState } from 'react';
import AppLayout from '../../Components/Layout/AppLayout';
import Card from '../../Components/Shared/Card';
import Button from '../../Components/Shared/Button';
import { Link, router, usePage } from '@inertiajs/react';

export default function CampaignsIndex({ campaigns }) {
    const { flash } = usePage().props;
    const [deletingId, setDeletingId] = useState(null);

    const handleDelete = (campaignId, campaignName) => {
        if (window.confirm(`Are you sure you want to delete "${campaignName}"? This action cannot be undone.`)) {
            setDeletingId(campaignId);
            router.delete(`/campaign/${campaignId}`, {
                onFinish: () => setDeletingId(null),
            });
        }
    };

    return (
        <AppLayout header="Campaigns">
            <div className="space-y-6">
                {/* Success Message */}
                {flash?.success && (
                    <div className="p-4 bg-green-50 border border-green-200 rounded-md">
                        <p className="text-sm text-green-800">{flash.success}</p>
                    </div>
                )}
                
                <div className="flex justify-between items-center">
                    <h1 className="text-2xl font-bold text-gray-900">Your Campaigns</h1>
                    <Link href="/campaign/create">
                        <Button variant="primary">Create New Campaign</Button>
                    </Link>
                </div>

                {campaigns && campaigns.length > 0 ? (
                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                        {campaigns.map((campaign) => (
                            <Card key={campaign.id}>
                                <div className="flex items-center justify-between mb-4">
                                    <h3 className="text-lg font-medium text-gray-900">{campaign.name}</h3>
                                    <div className="flex items-center gap-2">
                                        <span className={`px-2 py-1 text-xs font-semibold rounded-full ${
                                            campaign.status === 'active' ? 'bg-green-100 text-green-800' :
                                            campaign.status === 'paused' ? 'bg-yellow-100 text-yellow-800' :
                                            'bg-gray-100 text-gray-800'
                                        }`}>
                                            {campaign.status}
                                        </span>
                                        <button
                                            onClick={() => handleDelete(campaign.id, campaign.name)}
                                            disabled={deletingId === campaign.id}
                                            className="p-1 text-red-600 hover:text-red-800 hover:bg-red-50 rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                            title="Delete campaign"
                                        >
                                            {deletingId === campaign.id ? (
                                                <svg className="h-5 w-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                            ) : (
                                                <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            )}
                                        </button>
                                    </div>
                                </div>
                                <div className="space-y-2 text-sm text-gray-600">
                                    <p><strong>Domain:</strong> {campaign.web_url || 'N/A'}</p>
                                    <p><strong>Backlinks:</strong> {campaign.backlinks_count || 0}</p>
                                    <p><strong>Created:</strong> {new Date(campaign.created_at).toLocaleDateString()}</p>
                                </div>
                                <div className="mt-4 flex gap-2">
                                    <Link href={`/campaign/${campaign.id}`} className="flex-1">
                                        <Button variant="secondary" className="w-full">View</Button>
                                    </Link>
                                    <Link href={`/campaign/${campaign.id}/edit`} className="flex-1">
                                        <Button variant="outline" className="w-full">Edit</Button>
                                    </Link>
                                </div>
                            </Card>
                        ))}
                    </div>
                ) : (
                    <Card>
                        <div className="text-center py-12">
                            <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLineCap="round" strokeLineJoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 className="mt-2 text-sm font-medium text-gray-900">No campaigns</h3>
                            <p className="mt-1 text-sm text-gray-500">Get started by creating a new campaign.</p>
                            <div className="mt-6">
                                <Link href="/campaign/create">
                                    <Button variant="primary">Create Campaign</Button>
                                </Link>
                            </div>
                        </div>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}

