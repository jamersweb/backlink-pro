import { Link } from '@inertiajs/react';

export default function AnchorsTable({ anchors }) {
    if (!anchors || !anchors.data || anchors.data.length === 0) {
        return (
            <div className="text-center py-8 text-gray-500">
                No anchor text found.
            </div>
        );
    }

    const getTypeBadge = (type) => {
        const colors = {
            brand: 'bg-blue-100 text-blue-800',
            exact: 'bg-green-100 text-green-800',
            partial: 'bg-yellow-100 text-yellow-800',
            generic: 'bg-gray-100 text-gray-800',
            url: 'bg-purple-100 text-purple-800',
            empty: 'bg-red-100 text-red-800',
        };
        return (
            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${colors[type] || 'bg-gray-100 text-gray-800'}`}>
                {type}
            </span>
        );
    };

    return (
        <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                    <tr>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Anchor Text</th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Count</th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                    {anchors.data.map((anchor) => (
                        <tr key={anchor.id} className="hover:bg-gray-50">
                            <td className="px-6 py-4 text-sm text-gray-900 max-w-md">
                                {anchor.anchor || <span className="text-gray-400 italic">(empty)</span>}
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {anchor.count}
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap">
                                {getTypeBadge(anchor.type)}
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>

            {/* Pagination */}
            {anchors.links && anchors.links.length > 3 && (
                <div className="mt-4 flex items-center justify-center gap-2">
                    {anchors.links.map((link, index) => (
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


