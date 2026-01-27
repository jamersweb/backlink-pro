import { router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Components/Layout/AppLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';

export default function BriefShow({ domain, brief }) {
    const [outline, setOutline] = useState(brief.outline_json || []);
    const [metaSuggestion, setMetaSuggestion] = useState(brief.meta_suggestion_json || {});

    const handleSave = () => {
        router.post(`/domains/${domain.id}/content/briefs/${brief.id}`, {
            outline_json: outline,
            meta_suggestion_json: metaSuggestion,
        });
    };

    const handleExport = () => {
        window.location.href = `/domains/${domain.id}/content/briefs/${brief.id}/export`;
    };

    const handleSendToMeta = () => {
        router.post(`/domains/${domain.id}/content/briefs/${brief.id}/meta`);
    };

    const handleStatusChange = (status) => {
        router.post(`/domains/${domain.id}/content/briefs/${brief.id}`, { status });
    };

    return (
        <AppLayout header={brief.title}>
            <div className="space-y-6">
                {/* Header */}
                <Card>
                    <div className="p-6">
                        <div className="flex justify-between items-start">
                            <div>
                                <h2 className="text-2xl font-bold">{brief.title}</h2>
                                <p className="text-sm text-gray-600 mt-1">
                                    Primary Keyword: <strong>{brief.primary_keyword}</strong> | 
                                    Intent: <strong>{brief.intent}</strong> | 
                                    Status: <strong>{brief.status}</strong>
                                </p>
                            </div>
                            <div className="flex gap-2">
                                <Button variant="outline" onClick={handleExport}>Export Markdown</Button>
                                {brief.target_type === 'existing_page' && (
                                    <Button variant="outline" onClick={handleSendToMeta}>Send to Meta Editor</Button>
                                )}
                                <Button variant="primary" onClick={handleSave}>Save Changes</Button>
                            </div>
                        </div>
                    </div>
                </Card>

                {/* Meta Suggestions */}
                <Card>
                    <div className="p-6">
                        <h3 className="text-lg font-semibold mb-4">Meta Suggestions</h3>
                        <div className="space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Meta Title
                                </label>
                                <input
                                    type="text"
                                    value={metaSuggestion.title || ''}
                                    onChange={(e) => setMetaSuggestion({...metaSuggestion, title: e.target.value})}
                                    className="w-full rounded-md border-gray-300"
                                    maxLength={60}
                                />
                                <p className="text-xs text-gray-500 mt-1">
                                    {metaSuggestion.title?.length || 0} / 60 characters
                                </p>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Meta Description
                                </label>
                                <textarea
                                    value={metaSuggestion.description || ''}
                                    onChange={(e) => setMetaSuggestion({...metaSuggestion, description: e.target.value})}
                                    className="w-full rounded-md border-gray-300"
                                    rows={3}
                                    maxLength={160}
                                />
                                <p className="text-xs text-gray-500 mt-1">
                                    {metaSuggestion.description?.length || 0} / 160 characters
                                </p>
                            </div>
                        </div>
                    </div>
                </Card>

                {/* Outline */}
                <Card>
                    <div className="p-6">
                        <h3 className="text-lg font-semibold mb-4">Content Outline</h3>
                        <div className="space-y-4">
                            {outline.map((section, idx) => (
                                <div key={idx} className="border-l-4 border-blue-500 pl-4">
                                    <div className="flex items-start gap-2">
                                        <div className="flex-1">
                                            <h4 className={`font-semibold ${
                                                section.level === 1 ? 'text-xl' :
                                                section.level === 2 ? 'text-lg' :
                                                'text-base'
                                            }`}>
                                                {section.heading}
                                            </h4>
                                            {section.content && (
                                                <p className="text-sm text-gray-600 mt-1">{section.content}</p>
                                            )}
                                        </div>
                                    </div>
                                    {section.subsections && (
                                        <div className="mt-2 ml-4 space-y-2">
                                            {section.subsections.map((sub, subIdx) => (
                                                <div key={subIdx} className="border-l-2 border-gray-300 pl-3">
                                                    <h5 className="font-medium">{sub.heading}</h5>
                                                    {sub.content && (
                                                        <p className="text-sm text-gray-600 mt-1">{sub.content}</p>
                                                    )}
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>
                    </div>
                </Card>

                {/* FAQs */}
                {brief.faq_json && brief.faq_json.length > 0 && (
                    <Card>
                        <div className="p-6">
                            <h3 className="text-lg font-semibold mb-4">Frequently Asked Questions</h3>
                            <div className="space-y-4">
                                {brief.faq_json.map((faq, idx) => (
                                    <div key={idx} className="border-b border-gray-200 pb-4 last:border-0">
                                        <h4 className="font-medium text-gray-900">{faq.question}</h4>
                                        <p className="text-sm text-gray-600 mt-1">{faq.answer}</p>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </Card>
                )}

                {/* Internal Links */}
                {brief.internal_links_json && brief.internal_links_json.length > 0 && (
                    <Card>
                        <div className="p-6">
                            <h3 className="text-lg font-semibold mb-4">Suggested Internal Links</h3>
                            <ul className="space-y-2">
                                {brief.internal_links_json.map((link, idx) => (
                                    <li key={idx} className="text-sm">
                                        <a href={link.url} target="_blank" rel="noopener" className="text-blue-600 hover:underline">
                                            {link.anchor}
                                        </a>
                                        <span className="text-gray-500 ml-2">→ {link.url}</span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </Card>
                )}

                {/* Status Actions */}
                <Card>
                    <div className="p-6">
                        <h3 className="text-lg font-semibold mb-4">Status</h3>
                        <div className="flex gap-2">
                            {brief.status === 'draft' && (
                                <Button variant="outline" onClick={() => handleStatusChange('writing')}>
                                    Mark as Writing
                                </Button>
                            )}
                            {brief.status === 'writing' && (
                                <Button variant="outline" onClick={() => handleStatusChange('published')}>
                                    Mark as Published
                                </Button>
                            )}
                            {brief.status === 'published' && (
                                <Button variant="outline" onClick={() => handleStatusChange('archived')}>
                                    Archive
                                </Button>
                            )}
                        </div>
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}

