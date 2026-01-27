import { useState } from 'react';
import AdminLayout from '@/Components/Layout/AdminLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';
import Input from '@/Components/Shared/Input';
import { Link, router, usePage } from '@inertiajs/react';

export default function AdminCampaignsEdit({ campaign, users, domains }) {
    const { flash, errors } = usePage().props;
    const [formData, setFormData] = useState({
        name: campaign.name || '',
        status: campaign.status || 'inactive',
        user_id: campaign.user_id || '',
        domain_id: campaign.domain_id || '',
        daily_limit: campaign.daily_limit || '',
        total_limit: campaign.total_limit || '',
        start_date: campaign.start_date ? campaign.start_date.split('T')[0] : '',
        end_date: campaign.end_date ? campaign.end_date.split('T')[0] : '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        router.put(`/admin/campaigns/${campaign.id}`, formData, {
            preserveScroll: true,
        });
    };

    return (
        <AdminLayout header={`Edit Campaign: ${campaign.name || 'Untitled'}`}>
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

                <form onSubmit={handleSubmit} className="space-y-6">
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <h3 className="text-lg font-bold text-gray-900 mb-4">Basic Information</h3>
                        <div className="space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Campaign Name *
                                </label>
                                <Input
                                    type="text"
                                    value={formData.name}
                                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                    required
                                    className={errors?.name ? 'border-red-500' : ''}
                                />
                                {errors?.name && (
                                    <p className="mt-1 text-sm text-red-600">{errors.name}</p>
                                )}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Status *
                                </label>
                                <select
                                    value={formData.status}
                                    onChange={(e) => setFormData({ ...formData, status: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500"
                                    required
                                >
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="paused">Paused</option>
                                    <option value="completed">Completed</option>
                                    <option value="error">Error</option>
                                </select>
                                {errors?.status && (
                                    <p className="mt-1 text-sm text-red-600">{errors.status}</p>
                                )}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    User *
                                </label>
                                <select
                                    value={formData.user_id}
                                    onChange={(e) => setFormData({ ...formData, user_id: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500"
                                    required
                                >
                                    <option value="">Select User</option>
                                    {users?.map((user) => (
                                        <option key={user.id} value={user.id}>
                                            {user.name} ({user.email})
                                        </option>
                                    ))}
                                </select>
                                {errors?.user_id && (
                                    <p className="mt-1 text-sm text-red-600">{errors.user_id}</p>
                                )}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Domain
                                </label>
                                <select
                                    value={formData.domain_id}
                                    onChange={(e) => setFormData({ ...formData, domain_id: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500"
                                >
                                    <option value="">No Domain</option>
                                    {domains?.map((domain) => (
                                        <option key={domain.id} value={domain.id}>
                                            {domain.name}
                                        </option>
                                    ))}
                                </select>
                                {errors?.domain_id && (
                                    <p className="mt-1 text-sm text-red-600">{errors.domain_id}</p>
                                )}
                            </div>
                        </div>
                    </Card>

                    <Card className="bg-white border border-gray-200 shadow-md">
                        <h3 className="text-lg font-bold text-gray-900 mb-4">Limits & Scheduling</h3>
                        <div className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Daily Limit
                                    </label>
                                    <Input
                                        type="number"
                                        min="1"
                                        value={formData.daily_limit}
                                        onChange={(e) => setFormData({ ...formData, daily_limit: e.target.value })}
                                        className={errors?.daily_limit ? 'border-red-500' : ''}
                                    />
                                    {errors?.daily_limit && (
                                        <p className="mt-1 text-sm text-red-600">{errors.daily_limit}</p>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Total Limit
                                    </label>
                                    <Input
                                        type="number"
                                        min="1"
                                        value={formData.total_limit}
                                        onChange={(e) => setFormData({ ...formData, total_limit: e.target.value })}
                                        className={errors?.total_limit ? 'border-red-500' : ''}
                                    />
                                    {errors?.total_limit && (
                                        <p className="mt-1 text-sm text-red-600">{errors.total_limit}</p>
                                    )}
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Start Date
                                    </label>
                                    <Input
                                        type="date"
                                        value={formData.start_date}
                                        onChange={(e) => setFormData({ ...formData, start_date: e.target.value })}
                                        className={errors?.start_date ? 'border-red-500' : ''}
                                    />
                                    {errors?.start_date && (
                                        <p className="mt-1 text-sm text-red-600">{errors.start_date}</p>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        End Date
                                    </label>
                                    <Input
                                        type="date"
                                        value={formData.end_date}
                                        onChange={(e) => setFormData({ ...formData, end_date: e.target.value })}
                                        min={formData.start_date}
                                        className={errors?.end_date ? 'border-red-500' : ''}
                                    />
                                    {errors?.end_date && (
                                        <p className="mt-1 text-sm text-red-600">{errors.end_date}</p>
                                    )}
                                </div>
                            </div>
                        </div>
                    </Card>

                    {/* Action Buttons */}
                    <div className="flex items-center gap-4">
                        <Button type="submit" variant="primary">
                            💾 Update Campaign
                        </Button>
                        <Link href={`/admin/campaigns/${campaign.id}`}>
                            <Button type="button" variant="secondary">
                                Cancel
                            </Button>
                        </Link>
                        <Link href="/admin/campaigns">
                            <Button type="button" variant="secondary">
                                ← Back to Campaigns
                            </Button>
                        </Link>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}

