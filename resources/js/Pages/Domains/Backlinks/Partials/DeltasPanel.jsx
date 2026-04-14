import Card from '@/Components/Shared/Card';

export default function DeltasPanel({ delta, deltaDetails, detailed = false }) {
    if (!delta) return null;

    const newLinks = deltaDetails?.new_links || [];
    const lostLinks = deltaDetails?.lost_links || [];
    const newRefDomains = deltaDetails?.new_ref_domains || [];
    const lostRefDomains = deltaDetails?.lost_ref_domains || [];

    return (
        <Card>
            <div className="p-4">
                <h3 className="text-lg font-semibold text-gray-900 mb-4">Changes Since Last Run</h3>
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div className="text-center">
                        <p className="text-gray-600 text-xs font-medium mb-1">New Links</p>
                        <p className="text-2xl font-bold text-green-600">+{delta.new_links || 0}</p>
                    </div>
                    <div className="text-center">
                        <p className="text-gray-600 text-xs font-medium mb-1">Lost Links</p>
                        <p className="text-2xl font-bold text-red-600">-{delta.lost_links || 0}</p>
                    </div>
                    <div className="text-center">
                        <p className="text-gray-600 text-xs font-medium mb-1">New Ref Domains</p>
                        <p className="text-2xl font-bold text-green-600">+{delta.new_ref_domains || 0}</p>
                    </div>
                    <div className="text-center">
                        <p className="text-gray-600 text-xs font-medium mb-1">Lost Ref Domains</p>
                        <p className="text-2xl font-bold text-red-600">-{delta.lost_ref_domains || 0}</p>
                    </div>
                </div>
                {detailed && delta.previous_run_id && (
                    <div className="mt-4 pt-4 border-t border-gray-200">
                        <p className="text-sm text-gray-600">
                            Compared to run #{delta.previous_run_id}
                        </p>

                        <div className="grid grid-cols-1 xl:grid-cols-2 gap-6 mt-4">
                            <div>
                                <h4 className="text-sm font-semibold text-gray-900 mb-2">New Links</h4>
                                {newLinks.length === 0 ? (
                                    <p className="text-xs text-gray-500">No new links in this snapshot.</p>
                                ) : (
                                    <div className="space-y-2 max-h-64 overflow-y-auto pr-1">
                                        {newLinks.map((item, index) => (
                                            <div key={`new-link-${index}`} className="rounded border border-green-200 bg-green-50 px-3 py-2">
                                                <div className="text-xs font-medium text-gray-800">{item.source_domain || '-'}</div>
                                                <div className="text-xs text-gray-600 truncate">{item.source_url}</div>
                                                <div className="text-xs text-gray-700 mt-1">Anchor: {item.anchor || '(empty)'}</div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                            <div>
                                <h4 className="text-sm font-semibold text-gray-900 mb-2">Lost Links</h4>
                                {lostLinks.length === 0 ? (
                                    <p className="text-xs text-gray-500">No lost links in this snapshot.</p>
                                ) : (
                                    <div className="space-y-2 max-h-64 overflow-y-auto pr-1">
                                        {lostLinks.map((item, index) => (
                                            <div key={`lost-link-${index}`} className="rounded border border-red-200 bg-red-50 px-3 py-2">
                                                <div className="text-xs font-medium text-gray-800">{item.source_domain || '-'}</div>
                                                <div className="text-xs text-gray-600 truncate">{item.source_url}</div>
                                                <div className="text-xs text-gray-700 mt-1">Anchor: {item.anchor || '(empty)'}</div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>

                        <div className="grid grid-cols-1 xl:grid-cols-2 gap-6 mt-6">
                            <div>
                                <h4 className="text-sm font-semibold text-gray-900 mb-2">New Ref Domains</h4>
                                {newRefDomains.length === 0 ? (
                                    <p className="text-xs text-gray-500">No new referring domains.</p>
                                ) : (
                                    <div className="flex flex-wrap gap-2">
                                        {newRefDomains.map((name) => (
                                            <span key={`new-ref-${name}`} className="px-2 py-1 rounded bg-green-100 text-green-800 text-xs">
                                                {name}
                                            </span>
                                        ))}
                                    </div>
                                )}
                            </div>
                            <div>
                                <h4 className="text-sm font-semibold text-gray-900 mb-2">Lost Ref Domains</h4>
                                {lostRefDomains.length === 0 ? (
                                    <p className="text-xs text-gray-500">No lost referring domains.</p>
                                ) : (
                                    <div className="flex flex-wrap gap-2">
                                        {lostRefDomains.map((name) => (
                                            <span key={`lost-ref-${name}`} className="px-2 py-1 rounded bg-red-100 text-red-800 text-xs">
                                                {name}
                                            </span>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </Card>
    );
}


