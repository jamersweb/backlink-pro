import Card from '@/Components/Shared/Card';

export default function BacklinksSection({ data }) {
    if (!data) return null;

    return (
        <Card>
            <div className="p-6">
                <h2 className="text-xl font-bold text-gray-900 mb-4">Backlinks Overview</h2>
                
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div>
                        <p className="text-sm text-gray-600">Total Backlinks</p>
                        <p className="text-2xl font-bold text-gray-900">{data.total_backlinks?.toLocaleString() || 0}</p>
                    </div>
                    <div>
                        <p className="text-sm text-gray-600">Referring Domains</p>
                        <p className="text-2xl font-bold text-gray-900">{data.ref_domains?.toLocaleString() || 0}</p>
                    </div>
                    <div>
                        <p className="text-sm text-gray-600">Follow</p>
                        <p className="text-2xl font-bold text-green-600">{data.follow?.toLocaleString() || 0}</p>
                    </div>
                    <div>
                        <p className="text-sm text-gray-600">NoFollow</p>
                        <p className="text-2xl font-bold text-gray-600">{data.nofollow?.toLocaleString() || 0}</p>
                    </div>
                </div>

                {/* Delta */}
                {data.delta && (
                    <div className="mb-6 p-4 bg-gray-50 rounded-lg">
                        <h3 className="text-sm font-semibold text-gray-900 mb-3">Recent Changes</h3>
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <p className="text-xs text-gray-600">New Links</p>
                                <p className="text-lg font-bold text-green-600">+{data.delta.new_links || 0}</p>
                            </div>
                            <div>
                                <p className="text-xs text-gray-600">Lost Links</p>
                                <p className="text-lg font-bold text-red-600">-{data.delta.lost_links || 0}</p>
                            </div>
                            <div>
                                <p className="text-xs text-gray-600">New Domains</p>
                                <p className="text-lg font-bold text-green-600">+{data.delta.new_ref_domains || 0}</p>
                            </div>
                            <div>
                                <p className="text-xs text-gray-600">Lost Domains</p>
                                <p className="text-lg font-bold text-red-600">-{data.delta.lost_ref_domains || 0}</p>
                            </div>
                        </div>
                    </div>
                )}

                {/* Top Referring Domains */}
                {data.top_ref_domains && data.top_ref_domains.length > 0 && (
                    <div>
                        <h3 className="text-lg font-semibold text-gray-900 mb-3">Top Referring Domains</h3>
                        <div className="space-y-2">
                            {data.top_ref_domains.map((ref, idx) => (
                                <div key={idx} className="flex justify-between items-center p-2 bg-gray-50 rounded">
                                    <span className="text-sm text-gray-900">{ref.domain}</span>
                                    <span className="text-sm font-semibold text-gray-700">{ref.backlinks_count} links</span>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </Card>
    );
}


