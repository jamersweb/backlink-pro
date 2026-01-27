import { useForm } from '@inertiajs/react';
import Input from '@/Components/Shared/Input';
import Button from '@/Components/Shared/Button';
import Card from '@/Components/Shared/Card';

export default function MetaEditor({ page, domain, onSaveDraft, onPublish }) {
    const { data, setData, processing } = useForm({
        title: page?.meta_current_json?.title || page?.meta_published_json?.title || '',
        description: page?.meta_current_json?.description || page?.meta_published_json?.description || '',
        og_title: page?.meta_current_json?.og_title || page?.meta_published_json?.og_title || '',
        og_description: page?.meta_current_json?.og_description || page?.meta_published_json?.og_description || '',
        og_image: page?.meta_current_json?.og_image || page?.meta_published_json?.og_image || '',
        canonical: page?.meta_current_json?.canonical || page?.meta_published_json?.canonical || '',
        robots: page?.meta_current_json?.robots || page?.meta_published_json?.robots || 'index,follow',
    });

    if (!page) {
        return (
            <Card>
                <div className="text-center py-12">
                    <p className="text-gray-500">Select a page from the list to edit meta tags</p>
                </div>
            </Card>
        );
    }

    return (
        <div className="space-y-6">
            {/* Editor Form */}
            <Card>
                <div className="p-6">
                    <h3 className="text-lg font-semibold text-gray-900 mb-4">
                        {page.title_current || page.path || 'Edit Meta'}
                    </h3>
                    <div className="space-y-4">
                        <Input
                            label="Title"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            maxLength={60}
                            helpText={`${data.title.length}/60 characters`}
                        />
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Description
                            </label>
                            <textarea
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                maxLength={160}
                                rows={3}
                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                            />
                            <p className="text-xs text-gray-500 mt-1">
                                {data.description.length}/160 characters
                            </p>
                        </div>
                        <Input
                            label="OG Title"
                            value={data.og_title}
                            onChange={(e) => setData('og_title', e.target.value)}
                            maxLength={60}
                            helpText="Open Graph title for social sharing"
                        />
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                OG Description
                            </label>
                            <textarea
                                value={data.og_description}
                                onChange={(e) => setData('og_description', e.target.value)}
                                maxLength={160}
                                rows={2}
                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                            />
                            <p className="text-xs text-gray-500 mt-1">
                                Open Graph description for social sharing
                            </p>
                        </div>
                        <Input
                            label="OG Image URL"
                            type="url"
                            value={data.og_image}
                            onChange={(e) => setData('og_image', e.target.value)}
                            placeholder="https://example.com/image.jpg"
                            helpText="Full URL to the Open Graph image"
                        />
                        <Input
                            label="Canonical URL"
                            type="url"
                            value={data.canonical}
                            onChange={(e) => setData('canonical', e.target.value)}
                            placeholder="https://example.com/page"
                            helpText="Canonical URL for this page"
                        />
                        <Input
                            label="Robots"
                            value={data.robots}
                            onChange={(e) => setData('robots', e.target.value)}
                            placeholder="index,follow"
                            helpText="e.g., index,follow or noindex,nofollow"
                        />
                    </div>
                    <div className="flex gap-3 mt-6">
                        <Button variant="outline" onClick={() => onSaveDraft(data)} disabled={processing}>
                            Save Draft
                        </Button>
                        <Button variant="primary" onClick={() => onPublish(data)} disabled={processing}>
                            Publish
                        </Button>
                    </div>
                </div>
            </Card>

            {/* Google Preview */}
            <Card>
                <div className="p-4">
                    <h4 className="text-sm font-semibold text-gray-900 mb-3">Google Search Preview</h4>
                    <div className="border border-gray-200 rounded p-4 bg-white">
                        <div className="text-blue-600 text-lg font-medium mb-1">
                            {data.title || 'Page Title'}
                        </div>
                        <div className="text-green-700 text-sm mb-2">
                            {page.url || 'https://example.com/page'}
                        </div>
                        <div className="text-gray-600 text-sm">
                            {data.description || 'Page description will appear here...'}
                        </div>
                    </div>
                </div>
            </Card>
        </div>
    );
}


