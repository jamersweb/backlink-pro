import { Link, router, useForm } from '@inertiajs/react';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';
import Input from '@/Components/Shared/Input';
import Select from '@/Components/Shared/Select';

export default function PagesTable({ audit, pages, filters }) {
    const { data, setData, get } = useForm({
        status_code: filters.status_code || '',
        indexable: filters.indexable || '',
        search: filters.search || '',
    });

    const handleFilter = () => {
        get(`/domains/${audit.domain_id}/audits/${audit.id}`, {
            data: {
                tab: 'pages',
                status_code: data.status_code || undefined,
                indexable: data.indexable || undefined,
                search: data.search || undefined,
            },
            preserveState: true,
        });
    };

    const getStatusBadge = (code) => {
        if (!code) return <span className="text-gray-400">-</span>;
        const colors = {
            200: 'bg-green-100 text-green-800',
            301: 'bg-blue-100 text-blue-800',
            302: 'bg-blue-100 text-blue-800',
            404: 'bg-red-100 text-red-800',
            500: 'bg-red-100 text-red-800',
        };
        const color = colors[code] || 'bg-gray-100 text-gray-800';
        return (
            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${color}`}>
                {code}
            </span>
        );
    };

    return (
        <Card>
            <div className="mb-4">
                <h3 className="text-lg font-semibold text-gray-900 mb-4">Pages</h3>
                
                {/* Filters */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                    <Select
                        label="Status Code"
                        name="status_code"
                        value={data.status_code}
                        onChange={(e) => setData('status_code', e.target.value)}
                    >
                        <option value="">All</option>
                        <option value="200">200 OK</option>
                        <option value="301">301 Redirect</option>
                        <option value="302">302 Redirect</option>
                        <option value="404">404 Not Found</option>
                        <option value="500">500 Error</option>
                    </Select>

                    <Select
                        label="Indexable"
                        name="indexable"
                        value={data.indexable}
                        onChange={(e) => setData('indexable', e.target.value)}
                    >
                        <option value="">All</option>
                        <option value="1">Indexable</option>
                        <option value="0">Not Indexable</option>
                    </Select>

                    <div>
                        <Input
                            label="Search"
                            name="search"
                            value={data.search}
                            onChange={(e) => setData('search', e.target.value)}
                            placeholder="Search URL or title..."
                            className="mb-0"
                        />
                    </div>

                    <div className="flex items-end">
                        <Button variant="primary" onClick={handleFilter} className="w-full">
                            Filter
                        </Button>
                    </div>
                </div>
            </div>

            {pages && pages.data && pages.data.length > 0 ? (
                <>
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">URL</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">H1</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Issues</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Indexable</th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {pages.data.map((page) => (
                                    <tr key={page.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4">
                                            <a href={page.url} target="_blank" rel="noopener noreferrer" className="text-blue-600 hover:underline text-sm truncate block max-w-md">
                                                {page.url}
                                            </a>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            {getStatusBadge(page.status_code)}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-900 max-w-xs truncate">
                                            {page.title || <span className="text-gray-400">-</span>}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {page.h1_count || 0}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {page.issues_count || 0}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            {page.is_indexable ? (
                                                <span className="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Yes</span>
                                            ) : (
                                                <span className="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">No</span>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {pages.links && pages.links.length > 3 && (
                        <div className="mt-4 flex items-center justify-center gap-2">
                            {pages.links.map((link, index) => (
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
                </>
            ) : (
                <div className="text-center py-12 text-gray-500">
                    No pages found
                </div>
            )}
        </Card>
    );
}

