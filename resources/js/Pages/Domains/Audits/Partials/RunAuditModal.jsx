import { useForm, router } from '@inertiajs/react';
import Button from '@/Components/Shared/Button';
import Input from '@/Components/Shared/Input';
import Select from '@/Components/Shared/Select';

export default function RunAuditModal({ domain, onClose }) {
    const { data, setData, post, processing, errors } = useForm({
        crawl_limit: 100,
        max_depth: 3,
        include_sitemap: true,
        include_cwv: false,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(`/domains/${domain.id}/audits`, {
            onSuccess: () => {
                onClose();
            },
        });
    };

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div className="p-6">
                    <div className="flex justify-between items-center mb-6">
                        <h2 className="text-2xl font-bold text-gray-900">Run New Audit</h2>
                        <button
                            onClick={onClose}
                            className="text-gray-400 hover:text-gray-600"
                        >
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLineCap="round" strokeLineJoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        <Select
                            label="Crawl Limit"
                            name="crawl_limit"
                            value={data.crawl_limit}
                            onChange={(e) => setData('crawl_limit', parseInt(e.target.value))}
                            error={errors.crawl_limit}
                            required
                        >
                            <option value={20}>20 pages</option>
                            <option value={100}>100 pages</option>
                            <option value={500}>500 pages</option>
                        </Select>

                        <Select
                            label="Max Depth"
                            name="max_depth"
                            value={data.max_depth}
                            onChange={(e) => setData('max_depth', parseInt(e.target.value))}
                            error={errors.max_depth}
                            required
                        >
                            <option value={0}>0 (seed URLs only)</option>
                            <option value={1}>1 level</option>
                            <option value={2}>2 levels</option>
                            <option value={3}>3 levels</option>
                            <option value={4}>4 levels</option>
                            <option value={5}>5 levels</option>
                        </Select>

                        <div className="flex items-center">
                            <input
                                type="checkbox"
                                id="include_sitemap"
                                checked={data.include_sitemap}
                                onChange={(e) => setData('include_sitemap', e.target.checked)}
                                className="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                            />
                            <label htmlFor="include_sitemap" className="ml-2 block text-sm text-gray-700">
                                Include sitemap discovery
                            </label>
                        </div>

                        <div className="flex items-center">
                            <input
                                type="checkbox"
                                id="include_cwv"
                                checked={data.include_cwv}
                                onChange={(e) => setData('include_cwv', e.target.checked)}
                                className="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                            />
                            <label htmlFor="include_cwv" className="ml-2 block text-sm text-gray-700">
                                Include Core Web Vitals (requires PAGESPEED_API_KEY)
                            </label>
                        </div>

                        {errors.rate_limit && (
                            <div className="p-4 bg-red-50 border border-red-200 rounded-md">
                                <p className="text-sm text-red-800">{errors.rate_limit}</p>
                            </div>
                        )}

                        <div className="flex gap-4 pt-4">
                            <Button type="submit" variant="primary" disabled={processing}>
                                {processing ? 'Starting...' : 'Start Audit'}
                            </Button>
                            <Button type="button" variant="outline" onClick={onClose}>
                                Cancel
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
}


