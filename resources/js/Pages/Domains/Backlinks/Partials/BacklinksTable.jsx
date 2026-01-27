import { Link } from '@inertiajs/react';
import Card from '@/Components/Shared/Card';

export default function BacklinksTable({ backlinks }) {
    if (!backlinks || !backlinks.data || backlinks.data.length === 0) {
        return (
            <div className="text-center py-8 text-gray-500">
                No backlinks found.
            </div>
        );
    }

    const getRelBadge = (rel) => {
        const colors = {
            follow: 'bg-green-100 text-green-800',
            nofollow: 'bg-yellow-100 text-yellow-800',
            ugc: 'bg-blue-100 text-blue-800',
            sponsored: 'bg-purple-100 text-purple-800',
        };
        return (
            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${colors[rel] || 'bg-gray-100 text-gray-800'}`}>
                {rel}
            </span>
        );
    };

    return (
        <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                    <tr>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Source Domain</th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Source URL</th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Target URL</th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Anchor</th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rel</th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">TLD</th>
                    </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                    {backlinks.data.map((backlink) => (
                        <tr key={backlink.id} className="hover:bg-gray-50">
                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {backlink.source_domain}
                            </td>
                            <td className="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                <a href={backlink.source_url} target="_blank" rel="noopener noreferrer" className="text-blue-600 hover:underline">
                                    {backlink.source_url}
                                </a>
                            </td>
                            <td className="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                <a href={backlink.target_url} target="_blank" rel="noopener noreferrer" className="text-blue-600 hover:underline">
                                    {backlink.target_url}
                                </a>
                            </td>
                            <td className="px-6 py-4 text-sm text-gray-500 max-w-xs">
                                {backlink.anchor || <span className="text-gray-400 italic">(empty)</span>}
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap">
                                {getRelBadge(backlink.rel)}
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {backlink.tld || '-'}
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>

            {/* Pagination */}
            {backlinks.links && backlinks.links.length > 3 && (
                <div className="mt-4 flex items-center justify-center gap-2">
                    {backlinks.links.map((link, index) => (
                        <Link
                            key={index}
                            href={link.url || '#'}
                            className={`px-4 py-2 rounded-lg text-sm font-medium ${
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


