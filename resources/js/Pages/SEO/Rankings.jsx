import { useState } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Components/Layout/AppLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';

export default function SeoRankings({ organization, projects }) {
    const [showCreateModal, setShowCreateModal] = useState(false);
    const [selectedProject, setSelectedProject] = useState(null);
    const [showKeywordsModal, setShowKeywordsModal] = useState(false);

    const projectForm = useForm({
        name: '',
        target_domain: '',
        country_code: 'PK',
        language_code: 'en',
    });

    const keywordsForm = useForm({
        keywords: '',
        device: 'desktop',
        location: '',
    });

    const handleCreateProject = (e) => {
        e.preventDefault();
        projectForm.post(route('seo.rank-projects.create', { organization: organization.id }), {
            preserveScroll: true,
            onSuccess: () => {
                setShowCreateModal(false);
                projectForm.reset();
            },
        });
    };

    const handleAddKeywords = (projectId) => {
        setSelectedProject(projectId);
        setShowKeywordsModal(true);
    };

    const handleSubmitKeywords = (e) => {
        e.preventDefault();
        const keywords = keywordsForm.data.keywords.split('\n').filter(k => k.trim());
        
        router.post(route('seo.rank-projects.keywords', {
            organization: organization.id,
            project: selectedProject,
        }), {
            keywords: keywords,
            device: keywordsForm.data.device,
            location: keywordsForm.data.location || null,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                setShowKeywordsModal(false);
                keywordsForm.reset();
                setSelectedProject(null);
            },
        });
    };

    const handleRunChecks = (projectId) => {
        if (confirm('Run rank checks for all keywords in this project?')) {
            router.post(route('seo.rank-projects.run-checks', {
                organization: organization.id,
                project: projectId,
            }), {}, {
                preserveScroll: true,
            });
        }
    };

    return (
        <AppLayout header={`Rankings - ${organization.name}`}>
            <div className="space-y-6">
                {/* Header */}
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Rank Tracking</h1>
                        <p className="text-sm text-gray-500 mt-1">Monitor keyword rankings across search engines</p>
                    </div>
                    <div className="flex gap-2">
                        <Link href={route('seo.dashboard', { organization: organization.id })}>
                            <Button variant="outline">← Dashboard</Button>
                        </Link>
                        <Button variant="primary" onClick={() => setShowCreateModal(true)}>
                            + New Project
                        </Button>
                    </div>
                </div>

                {/* Projects List */}
                {projects && projects.length > 0 ? (
                    <div className="grid grid-cols-1 gap-4">
                        {projects.map((project) => (
                            <Card key={project.id}>
                                <div className="p-6">
                                    <div className="flex items-start justify-between mb-4">
                                        <div>
                                            <h3 className="text-lg font-semibold text-gray-900">{project.name}</h3>
                                            <p className="text-sm text-gray-500 mt-1">
                                                {project.target_domain} • {project.country_code.toUpperCase()} • {project.language_code.toUpperCase()}
                                            </p>
                                        </div>
                                        <span className={`px-2 py-1 text-xs font-semibold rounded-full ${
                                            project.status === 'active' 
                                                ? 'bg-green-100 text-green-800' 
                                                : 'bg-gray-100 text-gray-800'
                                        }`}>
                                            {project.status.toUpperCase()}
                                        </span>
                                    </div>

                                    <div className="flex items-center justify-between">
                                        <div className="text-sm text-gray-600">
                                            <strong>{project.keywords_count || 0}</strong> keywords tracked
                                        </div>
                                        <div className="flex gap-2">
                                            <Link href={route('seo.rank-projects.show', {
                                                organization: organization.id,
                                                project: project.id,
                                            })}>
                                                <Button variant="outline" size="sm">View Keywords</Button>
                                            </Link>
                                            <Button 
                                                variant="outline" 
                                                size="sm"
                                                onClick={() => handleAddKeywords(project.id)}
                                            >
                                                Add Keywords
                                            </Button>
                                            {project.status === 'active' && (
                                                <Button 
                                                    variant="primary" 
                                                    size="sm"
                                                    onClick={() => handleRunChecks(project.id)}
                                                >
                                                    Run Checks
                                                </Button>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </Card>
                        ))}
                    </div>
                ) : (
                    <Card>
                        <div className="text-center py-12">
                            <div className="text-4xl mb-4">📈</div>
                            <h3 className="text-lg font-medium text-gray-900 mb-2">No Rank Projects Yet</h3>
                            <p className="text-sm text-gray-500 mb-6">
                                Create a rank tracking project to monitor keyword positions over time.
                            </p>
                            <Button variant="primary" onClick={() => setShowCreateModal(true)}>
                                Create First Project
                            </Button>
                        </div>
                    </Card>
                )}

                {/* Create Project Modal */}
                {showCreateModal && (
                    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                        <Card className="w-full max-w-md">
                            <div className="p-6">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">Create Rank Project</h3>
                                <form onSubmit={handleCreateProject} className="space-y-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Project Name
                                        </label>
                                        <input
                                            type="text"
                                            value={projectForm.data.name}
                                            onChange={(e) => projectForm.setData('name', e.target.value)}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Target Domain
                                        </label>
                                        <input
                                            type="text"
                                            value={projectForm.data.target_domain}
                                            onChange={(e) => projectForm.setData('target_domain', e.target.value)}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                            placeholder="example.com"
                                            required
                                        />
                                    </div>
                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Country Code
                                            </label>
                                            <input
                                                type="text"
                                                value={projectForm.data.country_code}
                                                onChange={(e) => projectForm.setData('country_code', e.target.value.toUpperCase())}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                                maxLength="2"
                                                required
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Language Code
                                            </label>
                                            <input
                                                type="text"
                                                value={projectForm.data.language_code}
                                                onChange={(e) => projectForm.setData('language_code', e.target.value.toLowerCase())}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                                maxLength="5"
                                                required
                                            />
                                        </div>
                                    </div>
                                    <div className="flex gap-2 justify-end">
                                        <Button 
                                            type="button" 
                                            variant="outline" 
                                            onClick={() => setShowCreateModal(false)}
                                        >
                                            Cancel
                                        </Button>
                                        <Button type="submit" variant="primary" disabled={projectForm.processing}>
                                            {projectForm.processing ? 'Creating...' : 'Create Project'}
                                        </Button>
                                    </div>
                                </form>
                            </div>
                        </Card>
                    </div>
                )}

                {/* Add Keywords Modal */}
                {showKeywordsModal && (
                    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                        <Card className="w-full max-w-md">
                            <div className="p-6">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">Add Keywords</h3>
                                <form onSubmit={handleSubmitKeywords} className="space-y-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Keywords (one per line)
                                        </label>
                                        <textarea
                                            value={keywordsForm.data.keywords}
                                            onChange={(e) => keywordsForm.setData('keywords', e.target.value)}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                            rows="6"
                                            placeholder="keyword 1&#10;keyword 2&#10;keyword 3"
                                            required
                                        />
                                    </div>
                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Device
                                            </label>
                                            <select
                                                value={keywordsForm.data.device}
                                                onChange={(e) => keywordsForm.setData('device', e.target.value)}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                            >
                                                <option value="desktop">Desktop</option>
                                                <option value="mobile">Mobile</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Location (optional)
                                            </label>
                                            <input
                                                type="text"
                                                value={keywordsForm.data.location}
                                                onChange={(e) => keywordsForm.setData('location', e.target.value)}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-md"
                                                placeholder="City/Region"
                                            />
                                        </div>
                                    </div>
                                    <div className="flex gap-2 justify-end">
                                        <Button 
                                            type="button" 
                                            variant="outline" 
                                            onClick={() => {
                                                setShowKeywordsModal(false);
                                                keywordsForm.reset();
                                            }}
                                        >
                                            Cancel
                                        </Button>
                                        <Button type="submit" variant="primary" disabled={keywordsForm.processing}>
                                            {keywordsForm.processing ? 'Adding...' : 'Add Keywords'}
                                        </Button>
                                    </div>
                                </form>
                            </div>
                        </Card>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
