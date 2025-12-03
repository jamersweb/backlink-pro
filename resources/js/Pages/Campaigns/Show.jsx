import { Link } from '@inertiajs/react';
import AppLayout from '../../Components/Layout/AppLayout';
import Card from '../../Components/Shared/Card';
import Button from '../../Components/Shared/Button';

export default function CampaignShow({ campaign }) {
    if (!campaign) {
        return (
            <AppLayout header="Campaign Not Found">
                <Card>
                    <p className="text-gray-600">Campaign not found.</p>
                    <Link href="/campaign">
                        <Button variant="primary" className="mt-4">Back to Campaigns</Button>
                    </Link>
                </Card>
            </AppLayout>
        );
    }

    return (
        <AppLayout header={`Campaign: ${campaign.name || campaign.web_name || 'Untitled'}`}>
            <div className="space-y-6">
                {/* Campaign Info */}
                <Card title="Campaign Information">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label className="text-sm font-medium text-gray-500">Campaign Name</label>
                            <p className="text-gray-900">{campaign.name || 'N/A'}</p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-500">Status</label>
                            <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                                campaign.status === 'active' ? 'bg-green-100 text-green-800' :
                                campaign.status === 'paused' ? 'bg-yellow-100 text-yellow-800' :
                                'bg-gray-100 text-gray-800'
                            }`}>
                                {campaign.status}
                            </span>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-500">Domain</label>
                            <p className="text-gray-900">{campaign.domain?.name || 'N/A'}</p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-500">Created</label>
                            <p className="text-gray-900">{new Date(campaign.created_at).toLocaleDateString()}</p>
                        </div>
                    </div>
                </Card>

                {/* Website Information */}
                <Card title="Website Information">
                    <div className="space-y-4">
                        <div>
                            <label className="text-sm font-medium text-gray-500">Website Name</label>
                            <p className="text-gray-900">{campaign.web_name || 'N/A'}</p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-500">Website URL</label>
                            <p className="text-gray-900">
                                {campaign.web_url ? (
                                    <a href={campaign.web_url} target="_blank" rel="noopener noreferrer" className="text-indigo-600 hover:underline">
                                        {campaign.web_url}
                                    </a>
                                ) : 'N/A'}
                            </p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-500">Keywords</label>
                            <p className="text-gray-900">{campaign.web_keyword || 'N/A'}</p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-500">About</label>
                            <p className="text-gray-900 whitespace-pre-wrap">{campaign.web_about || 'N/A'}</p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-500">Target</label>
                            <p className="text-gray-900">{campaign.web_target || 'N/A'}</p>
                        </div>
                    </div>
                </Card>

                {/* Company Information */}
                <Card title="Company Information">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label className="text-sm font-medium text-gray-500">Company Name</label>
                            <p className="text-gray-900">{campaign.company_name || 'N/A'}</p>
                        </div>
                        {campaign.company_logo && (
                            <div>
                                <label className="text-sm font-medium text-gray-500">Company Logo</label>
                                <div className="mt-2">
                                    <img 
                                        src={`/${campaign.company_logo}`} 
                                        alt="Company Logo" 
                                        className="h-32 w-32 object-contain border border-gray-200 rounded"
                                    />
                                </div>
                            </div>
                        )}
                        <div>
                            <label className="text-sm font-medium text-gray-500">Email</label>
                            <p className="text-gray-900">{campaign.company_email_address || 'N/A'}</p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-500">Phone</label>
                            <p className="text-gray-900">{campaign.company_number || 'N/A'}</p>
                        </div>
                        <div className="md:col-span-2">
                            <label className="text-sm font-medium text-gray-500">Address</label>
                            <p className="text-gray-900">{campaign.company_address || 'N/A'}</p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-500">Location</label>
                            <p className="text-gray-900">
                                {[campaign.country?.name, campaign.state?.name, campaign.city?.name]
                                    .filter(Boolean)
                                    .join(', ') || 'N/A'}
                            </p>
                        </div>
                    </div>
                </Card>

                {/* Settings */}
                {campaign.settings && (
                    <Card title="Campaign Settings">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="text-sm font-medium text-gray-500">Backlink Types</label>
                                <p className="text-gray-900">
                                    {campaign.settings.backlink_types?.join(', ') || 'N/A'}
                                </p>
                            </div>
                            <div>
                                <label className="text-sm font-medium text-gray-500">Daily Limit</label>
                                <p className="text-gray-900">{campaign.settings.daily_limit || 'N/A'}</p>
                            </div>
                            <div>
                                <label className="text-sm font-medium text-gray-500">Total Limit</label>
                                <p className="text-gray-900">{campaign.settings.total_limit || 'N/A'}</p>
                            </div>
                            <div>
                                <label className="text-sm font-medium text-gray-500">Content Tone</label>
                                <p className="text-gray-900 capitalize">{campaign.settings.content_tone || 'N/A'}</p>
                            </div>
                        </div>
                    </Card>
                )}

                {/* Actions */}
                <div className="flex gap-4">
                    <Link href="/campaign">
                        <Button variant="outline">Back to Campaigns</Button>
                    </Link>
                    <Link href={`/campaign/${campaign.id}/backlinks`}>
                        <Button variant="secondary">View Backlinks</Button>
                    </Link>
                    <Link href={`/campaign/${campaign.id}/edit`}>
                        <Button variant="primary">Edit Campaign</Button>
                    </Link>
                </div>
            </div>
        </AppLayout>
    );
}

