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

    const dashboardActions = (
        <>
            <Link href="/notifications" className="bp-topbar-btn-secondary">
                <i className="bi bi-bell"></i>
                <span>Notifications</span>
            </Link>
            <Link href="/campaign/create" className="bp-topbar-btn-primary">
                <i className="bi bi-plus-lg"></i>
                <span>New Campaign</span>
            </Link>
        </>
    );

    return (
        <AppLayout header="Campaigns" subtitle="Manage and track all your backlink campaigns" actions={dashboardActions}>
            <div className="space-y-6">
                {/* Flash Messages */}
                {flash?.success && (
                    <div className="bp-flash bp-flash-success">
                        <i className="bi bi-check-circle"></i>
                        <p>{flash.success}</p>
                    </div>
                )}
                {flash?.error && (
                    <div className="bp-flash bp-flash-error">
                        <i className="bi bi-x-circle"></i>
                        <p>{flash.error}</p>
                    </div>
                )}
                {flash?.info && (
                    <div className="bp-flash bp-flash-info">
                        <i className="bi bi-info-circle"></i>
                        <p>{flash.info}</p>
                    </div>
                )}

                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                    <div>
                        <h1 className="text-2xl font-bold" style={{ color: 'var(--bp-text)' }}>Your Campaigns</h1>
                        <p style={{ color: 'var(--bp-text-muted)', fontSize: 14, marginTop: 4 }}>Manage and track all your backlink campaigns</p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button
                            variant="secondary"
                            onClick={() => window.location.href = '/campaigns/export?format=csv'}
                            className="bp-btn-secondary"
                        >
                            <i className="bi bi-download"></i> Export CSV
                        </Button>
                        <Button
                            variant="secondary"
                            onClick={() => window.location.href = '/campaigns/export?format=json'}
                            className="bp-btn-secondary"
                        >
                            <i className="bi bi-filetype-json"></i> Export JSON
                        </Button>
                    </div>
                </div>

                {campaigns && campaigns.length > 0 ? (
                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                        {campaigns.map((campaign) => (
                            <div key={campaign.id} className="bp-campaign-card">
                                <div className="bp-campaign-card-inner">
                                    {/* Status Badge */}
                                    <div style={{ position: 'absolute', top: 16, right: 16 }}>
                                        <span className={`bp-badge ${
                                            campaign.status === 'active' ? 'bp-badge-active' :
                                            campaign.status === 'paused' ? 'bp-badge-paused' :
                                            'bp-badge-pending'
                                        }`}>
                                            {campaign.status === 'active' && <i className="bi bi-check-circle-fill" style={{ marginRight: 4, fontSize: 10 }}></i>}
                                            {campaign.status}
                                        </span>
                                    </div>

                                    {/* Campaign Icon + Name */}
                                    <div style={{ display: 'flex', alignItems: 'center', gap: 12, marginBottom: 16 }}>
                                        <div className="bp-feature-icon purple" style={{ width: 44, height: 44, flexShrink: 0 }}>
                                            <i className="bi bi-megaphone"></i>
                                        </div>
                                        <div style={{ flex: 1, minWidth: 0 }}>
                                            <h3 style={{ fontSize: 16, fontWeight: 600, color: 'var(--bp-text)', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', margin: 0 }}>{campaign.name || 'Untitled Campaign'}</h3>
                                            <p style={{ fontSize: 12, color: 'var(--bp-text-dim)', marginTop: 2 }}>Campaign ID: #{campaign.id}</p>
                                        </div>
                                    </div>

                                    {/* Campaign Details */}
                                    <div className="bp-campaign-details">
                                        <div className="bp-campaign-detail-row">
                                            <i className="bi bi-globe2" style={{ color: 'var(--bp-text-dim)', fontSize: 14 }}></i>
                                            <div>
                                                <p className="bp-campaign-detail-label">Domain</p>
                                                <p className="bp-campaign-detail-value">{campaign.web_url || 'N/A'}</p>
                                            </div>
                                        </div>
                                        <div className="bp-campaign-detail-row">
                                            <i className="bi bi-link-45deg" style={{ color: 'var(--bp-text-dim)', fontSize: 14 }}></i>
                                            <div>
                                                <p className="bp-campaign-detail-label">Backlinks</p>
                                                <p className="bp-campaign-detail-value" style={{ fontSize: 18, fontWeight: 700 }}>{campaign.backlinks_count || 0}</p>
                                            </div>
                                        </div>
                                        <div className="bp-campaign-detail-row">
                                            <i className="bi bi-calendar3" style={{ color: 'var(--bp-text-dim)', fontSize: 14 }}></i>
                                            <div>
                                                <p className="bp-campaign-detail-label">Created</p>
                                                <p className="bp-campaign-detail-value">{new Date(campaign.created_at).toLocaleDateString()}</p>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Action Buttons */}
                                    <div className="bp-campaign-actions">
                                        {/* Pause/Resume Button */}
                                        {campaign.status === 'active' ? (
                                            <button
                                                onClick={() => handlePause(campaign.id)}
                                                disabled={processingId === campaign.id}
                                                className="bp-campaign-action-btn bp-campaign-action-warn"
                                                title="Pause campaign"
                                            >
                                                {processingId === campaign.id ? (
                                                    <i className="bi bi-arrow-repeat bp-spin"></i>
                                                ) : (
                                                    <><i className="bi bi-pause-fill"></i> Pause</>
                                                )}
                                            </button>
                                        ) : campaign.status === 'paused' ? (
                                            <button
                                                onClick={() => handleResume(campaign.id)}
                                                disabled={processingId === campaign.id}
                                                className="bp-campaign-action-btn bp-campaign-action-success"
                                                title="Resume campaign"
                                            >
                                                {processingId === campaign.id ? (
                                                    <i className="bi bi-arrow-repeat bp-spin"></i>
                                                ) : (
                                                    <><i className="bi bi-play-fill"></i> Resume</>
                                                )}
                                            </button>
                                        ) : null}

                                        {/* Other Actions */}
                                        <div style={{ display: 'flex', gap: 8 }}>
                                            <Link href={`/campaign/${campaign.id}`} style={{ flex: 1 }}>
                                                <button className="bp-campaign-action-btn bp-campaign-action-secondary" style={{ width: '100%' }}>
                                                    <i className="bi bi-eye"></i> View
                                                </button>
                                            </Link>
                                            <Link href={`/campaign/${campaign.id}/edit`} style={{ flex: 1 }}>
                                                <button className="bp-campaign-action-btn bp-campaign-action-secondary" style={{ width: '100%' }}>
                                                    <i className="bi bi-pencil"></i> Edit
                                                </button>
                                            </Link>
                                            <button
                                                onClick={() => handleDelete(campaign.id, campaign.name)}
                                                disabled={deletingId === campaign.id}
                                                className="bp-campaign-action-btn bp-campaign-action-danger"
                                                title="Delete campaign"
                                            >
                                                {deletingId === campaign.id ? (
                                                    <i className="bi bi-arrow-repeat bp-spin"></i>
                                                ) : (
                                                    <i className="bi bi-trash3"></i>
                                                )}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                ) : (
                    <div className="bp-campaign-empty">
                        <div className="bp-empty-icon" style={{ width: 72, height: 72, fontSize: 28 }}>
                            <i className="bi bi-megaphone"></i>
                        </div>
                        <h3>No campaigns yet</h3>
                        <p>Get started by creating your first campaign. Build quality backlinks and grow your SEO effortlessly.</p>
                        <Link href="/campaign/create" className="bp-topbar-btn-primary" style={{ marginTop: 8 }}>
                            <i className="bi bi-plus-lg"></i> Create Your First Campaign
                        </Link>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
