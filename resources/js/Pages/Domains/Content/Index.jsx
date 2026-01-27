import { router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Components/Layout/AppLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';

export default function ContentIndex({ domain, opportunities, filters }) {
    const [localFilters, setLocalFilters] = useState(filters);

    const handleRefresh = () => {
        router.post(`/domains/${domain.id}/content/opportunities/refresh`);
    };

    const handleIgnore = (opportunityId) => {
        router.post(`/domains/${domain.id}/content/opportunities/${opportunityId}/ignore`);
    };

    const handleCreateBrief = (opportunity) => {
        router.visit(`/domains/${domain.id}/content/briefs/create?opportunity_id=${opportunity.id}`);
    };

    const handleFilterChange = (key, value) => {
        const newFilters = { ...localFilters, [key]: value };
        setLocalFilters(newFilters);
        router.get(`/domains/${domain.id}/content`, newFilters, { preserveState: true });
    };

    return (
        <AppLayout header="Content Opportunities">
            <div className="space-y-6">
                {/* Filters */}
                <Card>
                    <div className="p-6">
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Min Score
                                </label>
                                <input
                                    type="number"
                                    value={localFilters.min_score}
                                    onChange={(e) => handleFilterChange('min_score', parseInt(e.target.value))}
                                    className="w-full rounded-md border-gray-300"
                                    min="0"
                                    max="100"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Position Range
                                </label>
                                <div className="flex gap-2">
                                    <input
                                        type="number"
                                        value={localFilters.min_position}
                                        onChange={(e) => handleFilterChange('min_position', parseInt(e.target.value))}
                                        className="w-full rounded-md border-gray-300"
                                        min="1"
                                    />
                                    <span className="self-center">-</span>
                                    <input
                                        type="number"
                                        value={localFilters.max_position}
                                        onChange={(e) => handleFilterChange('max_position', parseInt(e.target.value))}
                                        className="w-full rounded-md border-gray-300"
                                        min="1"
                                    />
                                </div>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Max CTR
                                </label>
                                <input
                                    type="number"
                                    step="0.01"
                                    value={localFilters.max_ctr}
                                    onChange={(e) => handleFilterChange('max_ctr', parseFloat(e.target.value))}
                                    className="w-full rounded-md border-gray-300"
                                    min="0"
                                    max="1"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Status
                                </label>
                                <select
                                    value={localFilters.status}
                                    onChange={(e) => handleFilterChange('status', e.target.value)}
                                    className="w-full rounded-md border-gray-300"
                                >
                                    <option value="new">New</option>
                                    <option value="brief_created">Brief Created</option>
                                    <option value="ignored">Ignored</option>
                                    <option value="all">All</option>
                                </select>
                            </div>
                        </div>
                        <div className="mt-4">
                            <Button variant="primary" onClick={handleRefresh}>
                                Refresh Opportunities
                            </Button>
                        </div>
                    </div>
                </Card>

                {/* Opportunities Table */}
                <Card>
                    <div className="p-6">
                        <h3 className="text-lg font-semibold mb-4">Keyword Opportunities</h3>
                        {opportunities.data && opportunities.data.length > 0 ? (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Keyword</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Score</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Position</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Impressions</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Clicks</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">CTR</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {opportunities.data.map((opp) => (
                                            <tr key={opp.id}>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="text-sm font-medium text-gray-900">{opp.query}</div>
                                                    {opp.page_url && (
                                                        <div className="text-xs text-gray-500 truncate max-w-xs">{opp.page_url}</div>
                                                    )}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <span className="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                                        {opp.opportunity_score}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm">{opp.position.toFixed(1)}</td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm">{opp.impressions.toLocaleString()}</td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm">{opp.clicks.toLocaleString()}</td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm">{(opp.ctr * 100).toFixed(2)}%</td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <span className={`px-2 py-1 text-xs font-semibold rounded-full ${
                                                        opp.status === 'new' ? 'bg-green-100 text-green-800' :
                                                        opp.status === 'brief_created' ? 'bg-blue-100 text-blue-800' :
                                                        'bg-gray-100 text-gray-800'
                                                    }`}>
                                                        {opp.status}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm">
                                                    <div className="flex gap-2">
                                                        {opp.status === 'new' && (
                                                            <>
                                                                <Button variant="outline" size="sm" onClick={() => handleCreateBrief(opp)}>
                                                                    Create Brief
                                                                </Button>
                                                                <Button variant="outline" size="sm" onClick={() => handleIgnore(opp.id)}>
                                                                    Ignore
                                                                </Button>
                                                            </>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <p className="text-sm text-gray-500">No opportunities found. Click "Refresh Opportunities" to generate from GSC data.</p>
                        )}

                        {opportunities.links && (
                            <div className="mt-4 flex justify-center">
                                <nav className="flex gap-2">
                                    {opportunities.links.map((link, idx) => (
                                        <button
                                            key={idx}
                                            onClick={() => link.url && router.visit(link.url)}
                                            disabled={!link.url}
                                            className={`px-3 py-1 rounded ${
                                                link.active ? 'bg-blue-500 text-white' :
                                                link.url ? 'bg-gray-200 hover:bg-gray-300' :
                                                'bg-gray-100 text-gray-400 cursor-not-allowed'
                                            }`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </nav>
                            </div>
                        )}
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}

