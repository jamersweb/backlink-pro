import { router } from '@inertiajs/react';
import { useState } from 'react';
import Button from '@/Components/Shared/Button';
import Input from '@/Components/Shared/Input';

export default function CreateReportModal({ domain, onClose }) {
    const [formData, setFormData] = useState({
        title: `SEO Report for ${domain.name}`,
        expires_at: '',
        password: '',
        sections: {
            analyzer: true,
            google: true,
            backlinks: true,
            meta: true,
            insights: true,
            content: false,
        },
        branding: {
            company_name: '',
            logo_url: '',
            accent_color: '#2E2E2E',
        },
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        router.post(`/domains/${domain.id}/reports`, formData, {
            onSuccess: () => {
                onClose();
            },
        });
    };

    const toggleSection = (section) => {
        setFormData(prev => ({
            ...prev,
            sections: {
                ...prev.sections,
                [section]: !prev.sections[section],
            },
        }));
    };

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div className="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div className="p-6">
                    <div className="flex justify-between items-center mb-6">
                        <h2 className="text-xl font-bold text-gray-900">Create Public Report</h2>
                        <button
                            onClick={onClose}
                            className="text-gray-400 hover:text-gray-600"
                        >
                            ✕
                        </button>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        {/* Basic Info */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Report Title
                            </label>
                            <Input
                                type="text"
                                value={formData.title}
                                onChange={(e) => setFormData(prev => ({ ...prev, title: e.target.value }))}
                                placeholder="Monthly SEO Report"
                            />
                        </div>

                        {/* Expiration */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Expiration Date (Optional)
                            </label>
                            <Input
                                type="date"
                                value={formData.expires_at}
                                onChange={(e) => setFormData(prev => ({ ...prev, expires_at: e.target.value }))}
                            />
                        </div>

                        {/* Password */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Password Protection (Optional)
                            </label>
                            <Input
                                type="password"
                                value={formData.password}
                                onChange={(e) => setFormData(prev => ({ ...prev, password: e.target.value }))}
                                placeholder="Leave empty for no password"
                            />
                        </div>

                        {/* Sections */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-3">
                                Include Sections
                            </label>
                            <div className="space-y-2">
                                {Object.keys(formData.sections).map((section) => (
                                    <label key={section} className="flex items-center">
                                        <input
                                            type="checkbox"
                                            checked={formData.sections[section]}
                                            onChange={() => toggleSection(section)}
                                            className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        />
                                        <span className="ml-2 text-sm text-gray-700 capitalize">{section}</span>
                                    </label>
                                ))}
                            </div>
                        </div>

                        {/* Branding */}
                        <div className="border-t pt-4">
                            <h3 className="text-sm font-medium text-gray-700 mb-3">Branding (Optional)</h3>
                            <div className="space-y-3">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Company Name
                                    </label>
                                    <Input
                                        type="text"
                                        value={formData.branding.company_name}
                                        onChange={(e) => setFormData(prev => ({
                                            ...prev,
                                            branding: { ...prev.branding, company_name: e.target.value }
                                        }))}
                                        placeholder="Your Company Name"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Logo URL
                                    </label>
                                    <Input
                                        type="url"
                                        value={formData.branding.logo_url}
                                        onChange={(e) => setFormData(prev => ({
                                            ...prev,
                                            branding: { ...prev.branding, logo_url: e.target.value }
                                        }))}
                                        placeholder="https://example.com/logo.png"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Accent Color
                                    </label>
                                    <Input
                                        type="color"
                                        value={formData.branding.accent_color}
                                        onChange={(e) => setFormData(prev => ({
                                            ...prev,
                                            branding: { ...prev.branding, accent_color: e.target.value }
                                        }))}
                                    />
                                </div>
                            </div>
                        </div>

                        {/* Actions */}
                        <div className="flex justify-end gap-3 pt-4 border-t">
                            <Button variant="outline" type="button" onClick={onClose}>
                                Cancel
                            </Button>
                            <Button variant="primary" type="submit">
                                Create Report
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
}


