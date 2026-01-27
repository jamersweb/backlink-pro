import { Link } from '@inertiajs/react';

export default function RefDomainsTable({ refDomains }) {
    if (!refDomains || !refDomains.data || refDomains.data.length === 0) {
        return (
            <div className="text-center py-8 text-gray-500">
                No referring domains found.
            </div>
        );
    }

    const getRiskBadge = (score) => {
        if (!score) return null;
        const color = score >= 80 ? 'text-green-600' : score >= 60 ? 'text-yellow-600' : 'text-red-600';
        return (
            <span className={`text-sm font-semibold ${color}`}>
                {score}
            </span>
        );
    };

    return (
        <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                    <tr>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Domain</th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Backlinks</th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">TLD</th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Country</th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Risk Score</th>
                    </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                    {refDomains.data.map((refDomain) => (
                        <tr key={refDomain.id} className="hover:bg-gray-50">
                            <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {refDomain.domain}
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {refDomain.backlinks_count}
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {refDomain.tld || '-'}
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {refDomain.country || '-'}
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap">
                                {getRiskBadge(refDomain.risk_score)}
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>

            {/* Pagination */}
            {refDomains.links && refDomains.links.length > 3 && (
                <div className="mt-4 flex items-center justify-center gap-2">
                    {refDomains.links.map((link, index) => (
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


