import { router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Components/Layout/AppLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';

export default function KeywordMap({ domain, keywordMap }) {
    const [formData, setFormData] = useState({
        keyword: '',
        url: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        router.post(`/domains/${domain.id}/content/keyword-map`, formData, {
            onSuccess: () => {
                setFormData({ keyword: '', url: '' });
            },
        });
    };

    const handleDelete = (id) => {
        if (confirm('Delete this keyword mapping?')) {
            router.delete(`/domains/${domain.id}/content/keyword-map/${id}`);
        }
    };

    return (
        <AppLayout header="Keyword Map">
            <div className="space-y-6">
                {/* Add Mapping Form */}
                <Card>
                    <div className="p-6">
                        <h3 className="text-lg font-semibold mb-4">Add Keyword Mapping</h3>
                        <form onSubmit={handleSubmit} className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Keyword *
                                </label>
                                <input
                                    type="text"
                                    value={formData.keyword}
                                    onChange={(e) => setFormData({...formData, keyword: e.target.value})}
                                    className="w-full rounded-md border-gray-300"
                                    required
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    URL *
                                </label>
                                <input
                                    type="url"
                                    value={formData.url}
                                    onChange={(e) => setFormData({...formData, url: e.target.value})}
                                    className="w-full rounded-md border-gray-300"
                                    required
                                />
                            </div>
                            <div className="flex items-end">
                                <Button type="submit" variant="primary" className="w-full">
                                    Add Mapping
                                </Button>
                            </div>
                        </form>
                    </div>
                </Card>

                {/* Keyword Map Table */}
                <Card>
                    <div className="p-6">
                        <h3 className="text-lg font-semibold mb-4">Keyword Mappings</h3>
                        {keywordMap.data && keywordMap.data.length > 0 ? (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Keyword</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">URL</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Source</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {keywordMap.data.map((mapping) => (
                                            <tr key={mapping.id}>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    {mapping.keyword}
                                                </td>
                                                <td className="px-6 py-4 text-sm">
                                                    <a href={mapping.url} target="_blank" rel="noopener" className="text-blue-600 hover:underline truncate max-w-md block">
                                                        {mapping.url}
                                                    </a>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <span className={`px-2 py-1 text-xs font-semibold rounded-full ${
                                                        mapping.source === 'brief' ? 'bg-blue-100 text-blue-800' :
                                                        mapping.source === 'gsc' ? 'bg-green-100 text-green-800' :
                                                        'bg-gray-100 text-gray-800'
                                                    }`}>
                                                        {mapping.source}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm">
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => handleDelete(mapping.id)}
                                                    >
                                                        Delete
                                                    </Button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <p className="text-sm text-gray-500">No keyword mappings yet.</p>
                        )}

                        {keywordMap.links && (
                            <div className="mt-4 flex justify-center">
                                <nav className="flex gap-2">
                                    {keywordMap.links.map((link, idx) => (
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

