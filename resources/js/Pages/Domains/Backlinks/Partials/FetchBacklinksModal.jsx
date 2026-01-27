import { useForm } from '@inertiajs/react';
import Button from '@/Components/Shared/Button';
import Select from '@/Components/Shared/Select';

export default function FetchBacklinksModal({ domain, onClose }) {
    const { data, setData, post, processing, errors } = useForm({
        limit_backlinks: 1000,
        limit_ref_domains: 500,
        limit_anchors: 200,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(`/domains/${domain.id}/backlinks`, {
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
                        <h2 className="text-2xl font-bold text-gray-900">Fetch Backlinks</h2>
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
                            label="Backlinks Limit"
                            name="limit_backlinks"
                            value={data.limit_backlinks}
                            onChange={(e) => setData('limit_backlinks', parseInt(e.target.value))}
                            error={errors.limit_backlinks}
                            required
                        >
                            <option value={100}>100 backlinks</option>
                            <option value={1000}>1,000 backlinks</option>
                            <option value={5000}>5,000 backlinks</option>
                        </Select>

                        <Select
                            label="Referring Domains Limit"
                            name="limit_ref_domains"
                            value={data.limit_ref_domains}
                            onChange={(e) => setData('limit_ref_domains', parseInt(e.target.value))}
                            error={errors.limit_ref_domains}
                            required
                        >
                            <option value={100}>100 domains</option>
                            <option value={500}>500 domains</option>
                            <option value={2000}>2,000 domains</option>
                        </Select>

                        <Select
                            label="Anchors Limit"
                            name="limit_anchors"
                            value={data.limit_anchors}
                            onChange={(e) => setData('limit_anchors', parseInt(e.target.value))}
                            error={errors.limit_anchors}
                            required
                        >
                            <option value={50}>50 anchors</option>
                            <option value={200}>200 anchors</option>
                            <option value={1000}>1,000 anchors</option>
                        </Select>

                        <div className="p-4 bg-blue-50 border border-blue-200 rounded-md">
                            <p className="text-sm text-blue-800">
                                <strong>Note:</strong> Larger limits will take longer to process. The fetch will run in the background and you'll be notified when it completes.
                            </p>
                        </div>

                        <div className="flex gap-4 pt-4">
                            <Button type="submit" variant="primary" disabled={processing}>
                                {processing ? 'Starting...' : 'Start Fetch'}
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


