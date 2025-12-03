import { useState } from 'react';
import AppLayout from '../../Components/Layout/AppLayout';
import Card from '../../Components/Shared/Card';
import Button from '../../Components/Shared/Button';
import { Link, router, usePage } from '@inertiajs/react';

export default function CampaignsIndex({ campaigns }) {
    const { flash } = usePage().props;
    const [deletingId, setDeletingId] = useState(null);
    const [processingId, setProcessingId] = useState(null);

    const handleDelete = (campaignId, campaignName) => {
        if (window.confirm(`Are you sure you want to delete "${campaignName}"? This action cannot be undone.`)) {
            setDeletingId(campaignId);
            router.delete(`/campaign/${campaignId}`, {
                onFinish: () => setDeletingId(null),
            });
        }
    };

    const handlePause = (campaignId) => {
        setProcessingId(campaignId);
        router.post(`/campaign/${campaignId}/pause`, {}, {
            onFinish: () => setProcessingId(null),
        });
    };

    const handleResume = (campaignId) => {
        setProcessingId(campaignId);
        router.post(`/campaign/${campaignId}/resume`, {}, {
            onFinish: () => setProcessingId(null),
        });
    };

    return (
        <AppLayout header="Campaigns">
            <div className="space-y-6">
                {/* Flash Messages */}
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
                {flash?.info && (
                    <div className="p-4 bg-blue-50 border border-blue-200 rounded-md">
                        <p className="text-sm text-blue-800">{flash.info}</p>
                    </div>
                )}
                
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                    <div>
                        <h1 className="text-3xl font-bold bg-gradient-to-r from-red-600 to-green-600 bg-clip-text text-transparent">Your Campaigns</h1>
                        <p className="text-gray-600 mt-1">Manage and track all your backlink campaigns</p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button
                            variant="secondary"
                            onClick={() => window.location.href = '/campaigns/export?format=csv'}
                            className="px-4 py-2"
                        >
                            📥 Export CSV
                        </Button>
                        <Button
                            variant="secondary"
                            onClick={() => window.location.href = '/campaigns/export?format=json'}
                            className="px-4 py-2"
                        >
                            📥 Export JSON
                        </Button>
                        <Link href="/campaign/create">
                            <Button variant="primary" className="px-6 py-3 shadow-lg hover:shadow-xl">
                                <span className="mr-2">➕</span> Create New Campaign
                            </Button>
                        </Link>
                    </div>
                </div>

                {campaigns && campaigns.length > 0 ? (
                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                        {campaigns.map((campaign) => (
                            <Card key={campaign.id} className="hover:scale-105 transition-transform duration-300">
                                <div className="relative">
                                    {/* Status Badge */}
                                    <div className="absolute top-4 right-4">
                                        <span className={`px-3 py-1.5 text-xs font-bold rounded-full shadow-md ${
                                            campaign.status === 'active' ? 'bg-gradient-to-r from-green-400 to-green-600 text-white' :
                                            campaign.status === 'paused' ? 'bg-gradient-to-r from-yellow-400 to-yellow-600 text-white' :
                                            'bg-gradient-to-r from-gray-400 to-gray-600 text-white'
                                        }`}>
                                            {campaign.status === 'active' && '✓ '}
                                            {campaign.status}
                                        </span>
                                    </div>
                                    
                                    {/* Campaign Icon */}
                                    <div className="mb-4 flex items-center gap-3">
                                        <div className="w-12 h-12 rounded-lg bg-gradient-to-br from-red-500 to-green-500 flex items-center justify-center text-white text-xl font-bold shadow-lg">
                                            📊
                                        </div>
                                        <div className="flex-1">
                                            <h3 className="text-lg font-bold text-gray-900 line-clamp-1">{campaign.name || 'Untitled Campaign'}</h3>
                                            <p className="text-xs text-gray-500 mt-0.5">Campaign ID: #{campaign.id}</p>
                                        </div>
                                    </div>
                                    
                                    {/* Campaign Details */}
                                    <div className="space-y-3 mb-4">
                                        <div className="flex items-start gap-2 text-sm">
                                            <span className="text-gray-400 mt-0.5">🌐</span>
                                            <div className="flex-1">
                                                <p className="text-gray-500 font-medium">Domain</p>
                                                <p className="text-gray-900 font-semibold truncate">{campaign.web_url || 'N/A'}</p>
                                            </div>
                                        </div>
                                        <div className="flex items-start gap-2 text-sm">
                                            <span className="text-gray-400 mt-0.5">🔗</span>
                                            <div className="flex-1">
                                                <p className="text-gray-500 font-medium">Backlinks</p>
                                                <p className="text-gray-900 font-bold text-lg">{campaign.backlinks_count || 0}</p>
                                            </div>
                                        </div>
                                        <div className="flex items-start gap-2 text-sm">
                                            <span className="text-gray-400 mt-0.5">📅</span>
                                            <div className="flex-1">
                                                <p className="text-gray-500 font-medium">Created</p>
                                                <p className="text-gray-900 font-semibold">{new Date(campaign.created_at).toLocaleDateString()}</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    {/* Action Buttons */}
                                    <div className="mt-6 pt-4 border-t border-gray-200 space-y-2">
                                        {/* Pause/Resume Button */}
                                        {campaign.status === 'active' ? (
                                            <button
                                                onClick={() => handlePause(campaign.id)}
                                                disabled={processingId === campaign.id}
                                                className="w-full px-4 py-2 text-yellow-700 hover:text-white hover:bg-yellow-600 border-2 border-yellow-300 hover:border-yellow-600 rounded-lg transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 font-medium"
                                                title="Pause campaign"
                                            >
                                                {processingId === campaign.id ? (
                                                    <svg className="h-5 w-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                ) : (
                                                    <>
                                                        <span>⏸️</span> Pause
                                                    </>
                                                )}
                                            </button>
                                        ) : campaign.status === 'paused' ? (
                                            <button
                                                onClick={() => handleResume(campaign.id)}
                                                disabled={processingId === campaign.id}
                                                className="w-full px-4 py-2 text-green-700 hover:text-white hover:bg-green-600 border-2 border-green-300 hover:border-green-600 rounded-lg transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 font-medium"
                                                title="Resume campaign"
                                            >
                                                {processingId === campaign.id ? (
                                                    <svg className="h-5 w-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                ) : (
                                                    <>
                                                        <span>▶️</span> Resume
                                                    </>
                                                )}
                                            </button>
                                        ) : null}
                                        
                                        {/* Other Actions */}
                                        <div className="flex gap-2">
                                            <Link href={`/campaign/${campaign.id}`} className="flex-1">
                                                <Button variant="secondary" className="w-full flex items-center justify-center gap-2">
                                                    <span>👁️</span> View
                                                </Button>
                                            </Link>
                                            <Link href={`/campaign/${campaign.id}/edit`} className="flex-1">
                                                <Button variant="outline" className="w-full flex items-center justify-center gap-2">
                                                    <span>✏️</span> Edit
                                                </Button>
                                            </Link>
                                            <button
                                                onClick={() => handleDelete(campaign.id, campaign.name)}
                                                disabled={deletingId === campaign.id}
                                                className="px-4 py-2 text-red-600 hover:text-white hover:bg-red-600 border-2 border-red-300 hover:border-red-600 rounded-lg transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
                                                title="Delete campaign"
                                            >
                                                {deletingId === campaign.id ? (
                                                    <svg className="h-5 w-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                ) : (
                                                    <span className="text-lg">🗑️</span>
                                                )}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </Card>
                        ))}
                    </div>
                ) : (
                    <Card className="bg-gradient-to-br from-gray-50 to-white">
                        <div className="text-center py-16">
                            <div className="mx-auto w-24 h-24 rounded-full bg-gradient-to-br from-red-100 to-green-100 flex items-center justify-center mb-6">
                                <span className="text-5xl">📋</span>
                            </div>
                            <h3 className="text-xl font-bold text-gray-900 mb-2">No campaigns yet</h3>
                            <p className="text-gray-600 mb-8 max-w-md mx-auto">Get started by creating your first campaign. Build quality backlinks and grow your SEO effortlessly.</p>
                            <Link href="/campaign/create">
                                <Button variant="primary" className="px-8 py-3 text-lg shadow-lg hover:shadow-xl">
                                    <span className="mr-2">➕</span> Create Your First Campaign
                                </Button>
                            </Link>
                        </div>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}

