import { router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Components/Layout/AppLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';

export default function BriefCreate({ domain, opportunity, prefill }) {
    const [formData, setFormData] = useState({
        primary_keyword: prefill?.primary_keyword || '',
        target_type: prefill?.target_type || 'new_page',
        target_url: prefill?.target_url || '',
        suggested_slug: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        router.post(`/domains/${domain.id}/content/briefs`, {
            ...formData,
            opportunity_id: opportunity?.id,
        });
    };

    return (
        <AppLayout header="Create Content Brief">
            <form onSubmit={handleSubmit} className="space-y-6">
                <Card>
                    <div className="p-6 space-y-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Primary Keyword *
                            </label>
                            <input
                                type="text"
                                value={formData.primary_keyword}
                                onChange={(e) => setFormData({...formData, primary_keyword: e.target.value})}
                                className="w-full rounded-md border-gray-300"
                                required
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Target Type *
                            </label>
                            <select
                                value={formData.target_type}
                                onChange={(e) => setFormData({...formData, target_type: e.target.value})}
                                className="w-full rounded-md border-gray-300"
                                required
                            >
                                <option value="existing_page">Existing Page</option>
                                <option value="new_page">New Page</option>
                            </select>
                        </div>

                        {formData.target_type === 'existing_page' && (
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Target URL *
                                </label>
                                <input
                                    type="url"
                                    value={formData.target_url}
                                    onChange={(e) => setFormData({...formData, target_url: e.target.value})}
                                    className="w-full rounded-md border-gray-300"
                                    required
                                />
                            </div>
                        )}

                        {formData.target_type === 'new_page' && (
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Suggested Slug
                                </label>
                                <input
                                    type="text"
                                    value={formData.suggested_slug}
                                    onChange={(e) => setFormData({...formData, suggested_slug: e.target.value})}
                                    className="w-full rounded-md border-gray-300"
                                    placeholder="auto-generated from keyword"
                                />
                            </div>
                        )}

                        {opportunity && (
                            <div className="bg-blue-50 p-4 rounded">
                                <p className="text-sm text-blue-800">
                                    Creating brief from opportunity: <strong>{opportunity.query}</strong>
                                    <br />
                                    Score: {opportunity.opportunity_score} | Position: {opportunity.position.toFixed(1)}
                                </p>
                            </div>
                        )}

                        <div className="flex gap-3">
                            <Button type="submit" variant="primary">
                                Generate Brief
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => router.visit(`/domains/${domain.id}/content`)}
                            >
                                Cancel
                            </Button>
                        </div>
                    </div>
                </Card>
            </form>
        </AppLayout>
    );
}

