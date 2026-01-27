import { router } from '@inertiajs/react';
import { useState } from 'react';
import Button from '@/Components/Shared/Button';

export default function PagesList({ pages, selectedPage, domain, onImport }) {
    const [searchQuery, setSearchQuery] = useState('');

    const filteredPages = pages?.data?.filter(page => {
        if (!searchQuery) return true;
        const query = searchQuery.toLowerCase();
        return (
            (page.title_current || '').toLowerCase().includes(query) ||
            (page.path || '').toLowerCase().includes(query) ||
            (page.url || '').toLowerCase().includes(query)
        );
    }) || [];

    return (
        <div className="space-y-4">
            {/* Search */}
            <div>
                <input
                    type="text"
                    placeholder="Search pages..."
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                />
            </div>

            {/* Pages List */}
            <div className="space-y-2 max-h-[600px] overflow-y-auto">
                {filteredPages.length > 0 ? (
                    filteredPages.map((page) => (
                        <button
                            key={page.id}
                            onClick={() => router.get(`/domains/${domain.id}/meta`, { page_id: page.id })}
                            className={`w-full text-left p-3 rounded-lg border transition-colors ${
                                selectedPage?.id === page.id
                                    ? 'border-blue-500 bg-blue-50'
                                    : 'border-gray-200 hover:bg-gray-50'
                            }`}
                        >
                            <div className="font-medium text-sm text-gray-900 truncate">
                                {page.title_current || page.path || page.url}
                            </div>
                            <div className="text-xs text-gray-500 mt-1 truncate">
                                {page.path || page.url}
                            </div>
                            <div className="flex items-center gap-2 mt-2">
                                {page.latest_draft && (
                                    <span className="px-2 py-0.5 text-xs bg-yellow-100 text-yellow-800 rounded">
                                        Draft
                                    </span>
                                )}
                                {page.latest_published && (
                                    <span className="px-2 py-0.5 text-xs bg-green-100 text-green-800 rounded">
                                        Published
                                    </span>
                                )}
                                {page.resource_type && (
                                    <span className="px-2 py-0.5 text-xs bg-gray-100 text-gray-600 rounded capitalize">
                                        {page.resource_type.replace('_', ' ')}
                                    </span>
                                )}
                            </div>
                        </button>
                    ))
                ) : (
                    <div className="text-center py-8 text-gray-500 text-sm">
                        {searchQuery ? 'No pages match your search.' : 'No pages. Import pages to get started.'}
                    </div>
                )}
            </div>

            {/* Pagination */}
            {pages?.links && pages.links.length > 3 && (
                <div className="flex items-center justify-center gap-2 pt-4 border-t">
                    {pages.links.map((link, index) => (
                        <button
                            key={index}
                            onClick={() => link.url && router.get(link.url)}
                            disabled={!link.url}
                            className={`px-3 py-1 text-sm rounded ${
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
        </div>
    );
}


