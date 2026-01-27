import { Link } from '@inertiajs/react';
import AppLayout from '@/Components/Layout/AppLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';

export default function AutomationIndex({ domain, campaigns }) {
    return (
        <AppLayout header="Automation Campaigns">
            <div className="space-y-6">
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Automation Campaigns</h1>
                        <p className="text-sm text-gray-500 mt-1">Manage backlink automation campaigns</p>
                    </div>
                    <Link href={`/domains/${domain.id}/automation/create`}>
                        <Button variant="primary">Create Campaign</Button>
                    </Link>
                </div>

                <Card>
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Targets</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jobs</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {campaigns.data.map((campaign) => (
                                    <tr key={campaign.id}>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <Link href={`/domains/${domain.id}/automation/${campaign.id}`} className="text-blue-600 hover:text-blue-800">
                                                {campaign.name}
                                            </Link>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${
                                                campaign.status === 'running' ? 'bg-green-100 text-green-800' :
                                                campaign.status === 'completed' ? 'bg-blue-100 text-blue-800' :
                                                campaign.status === 'failed' ? 'bg-red-100 text-red-800' :
                                                campaign.status === 'paused' ? 'bg-yellow-100 text-yellow-800' :
                                                'bg-gray-100 text-gray-800'
                                            }`}>
                                                {campaign.status}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {campaign.targets_count}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {campaign.jobs_count}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {new Date(campaign.created_at).toLocaleDateString()}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm">
                                            <Link href={`/domains/${domain.id}/automation/${campaign.id}`} className="text-blue-600 hover:text-blue-800">
                                                View
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}


