import { useEffect, useMemo, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Components/Layout/AppLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';

export default function BacklinksQualityIndex({ domain, latestRun, backlinks, tags, filters, stats }) {
    const { errors } = usePage().props;
    const [localFilters, setLocalFilters] = useState({
        action_status: filters?.action_status || '',
        risk_min: filters?.risk_min || '',
        risk_max: filters?.risk_max || '',
        tag_id: filters?.tag_id || '',
        search: filters?.search || '',
    });

    const initialSelections = useMemo(() => {
        const map = {};
        if (backlinks?.data) {
            backlinks.data.forEach((item) => {
                map[item.id] = item.tags?.map((t) => t.id) || [];
            });
        }
        return map;
    }, [backlinks]);

    const [tagSelections, setTagSelections] = useState(initialSelections);

    useEffect(() => {
        setTagSelections(initialSelections);
    }, [initialSelections]);

    const applyFilters = () => {
        router.get(`/domains/${domain.id}/backlinks/quality`, localFilters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const updateAction = (backlinkId, actionStatus) => {
        router.patch(`/domains/${domain.id}/backlinks/quality/${backlinkId}`, {
            action_status: actionStatus,
        }, { preserveScroll: true });
    };

    const saveTags = (backlinkId) => {
        router.post(`/domains/${domain.id}/backlinks/quality/${backlinkId}/tags`, {
            tag_ids: tagSelections[backlinkId] || [],
        }, { preserveScroll: true });
    };

    const onTagChange = (backlinkId, event) => {
        const selected = Array.from(event.target.selectedOptions).map((opt) => parseInt(opt.value, 10));
        setTagSelections((prev) => ({ ...prev, [backlinkId]: selected }));
    };

    const createTag = (e) => {
        e.preventDefault();
        const form = e.target;
        const name = form.name.value.trim();
        const color = form.color.value.trim();
        if (!name) return;
        router.post(`/domains/${domain.id}/backlinks/tags`, { name, color }, { preserveScroll: true });
        form.reset();
    };

    const deleteTag = (tagId) => {
        if (!confirm('Delete this tag?')) return;
        router.delete(`/domains/${domain.id}/backlinks/tags/${tagId}`, { preserveScroll: true });
    };

    return (
        <AppLayout header={`Backlink Quality • ${domain.host}`}>
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div className="text-sm text-gray-600">
                        {latestRun ? `Latest run #${latestRun.id} • ${latestRun.status}` : 'No completed runs yet'}
                    </div>
                    <div className="flex items-center gap-2">
                        <Button variant="secondary" href={`/domains/${domain.id}/backlinks`}>
                            Backlink Runs
                        </Button>
                        <Button href={`/domains/${domain.id}/backlinks/disavow`}>
                            Disavow
                        </Button>
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <Card title="Total">
                        <div className="text-3xl font-bold text-white">{stats.total}</div>
                    </Card>
                    <Card title="Toxic (80+)">
                        <div className="text-3xl font-bold text-red-400">{stats.toxic}</div>
                    </Card>
                    <Card title="Review (55-79)">
                        <div className="text-3xl font-bold text-yellow-400">{stats.review}</div>
                    </Card>
                    <Card title="Keep">
                        <div className="text-3xl font-bold text-green-400">{stats.keep}</div>
                    </Card>
                    <Card title="Disavow">
                        <div className="text-3xl font-bold text-orange-400">{stats.disavow}</div>
                    </Card>
                </div>

                <Card title="Filters">
                    <div className="grid grid-cols-1 md:grid-cols-5 gap-3">
                        <input
                            className="rounded-lg border-gray-300 text-sm"
                            placeholder="Search domain/url/anchor"
                            value={localFilters.search}
                            onChange={(e) => setLocalFilters({ ...localFilters, search: e.target.value })}
                        />
                        <select
                            className="rounded-lg border-gray-300 text-sm"
                            value={localFilters.action_status}
                            onChange={(e) => setLocalFilters({ ...localFilters, action_status: e.target.value })}
                        >
                            <option value="">All actions</option>
                            <option value="keep">Keep</option>
                            <option value="review">Review</option>
                            <option value="remove">Remove</option>
                            <option value="disavow">Disavow</option>
                        </select>
                        <input
                            className="rounded-lg border-gray-300 text-sm"
                            placeholder="Min risk"
                            value={localFilters.risk_min}
                            onChange={(e) => setLocalFilters({ ...localFilters, risk_min: e.target.value })}
                        />
                        <input
                            className="rounded-lg border-gray-300 text-sm"
                            placeholder="Max risk"
                            value={localFilters.risk_max}
                            onChange={(e) => setLocalFilters({ ...localFilters, risk_max: e.target.value })}
                        />
                        <select
                            className="rounded-lg border-gray-300 text-sm"
                            value={localFilters.tag_id}
                            onChange={(e) => setLocalFilters({ ...localFilters, tag_id: e.target.value })}
                        >
                            <option value="">All tags</option>
                            {tags.map((tag) => (
                                <option key={tag.id} value={tag.id}>{tag.name}</option>
                            ))}
                        </select>
                    </div>
                    <div className="mt-4">
                        <Button onClick={applyFilters}>Apply Filters</Button>
                    </div>
                </Card>

                <Card title="Tags">
                    <form onSubmit={createTag} className="flex flex-wrap items-end gap-3">
                        <div>
                            <label className="block text-xs text-gray-400">Name</label>
                            <input name="name" className="rounded-lg border-gray-300 text-sm" placeholder="e.g. toxic" />
                        </div>
                        <div>
                            <label className="block text-xs text-gray-400">Color</label>
                            <input name="color" className="rounded-lg border-gray-300 text-sm" placeholder="#F59E0B" />
                        </div>
                        <Button type="submit">Add Tag</Button>
                    </form>
                    {tags.length > 0 && (
                        <div className="mt-4 flex flex-wrap gap-2">
                            {tags.map((tag) => (
                                <span key={tag.id} className="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs bg-white/10 text-gray-200">
                                    <span className="inline-block w-2 h-2 rounded-full" style={{ backgroundColor: tag.color || '#6B7280' }} />
                                    {tag.name}
                                    <button className="text-gray-400 hover:text-red-400" onClick={() => deleteTag(tag.id)}>×</button>
                                </span>
                            ))}
                        </div>
                    )}
                </Card>

                {errors?.disavow && (
                    <div className="text-sm text-red-600">{errors.disavow}</div>
                )}

                <Card title="Backlinks">
                    {!backlinks?.data || backlinks.data.length === 0 ? (
                        <div className="text-gray-400">No backlinks found.</div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-white/10 text-sm">
                                <thead>
                                    <tr className="text-left text-gray-400">
                                        <th className="py-2 pr-4">Source</th>
                                        <th className="py-2 pr-4">Anchor</th>
                                        <th className="py-2 pr-4">Rel</th>
                                        <th className="py-2 pr-4">Risk</th>
                                        <th className="py-2 pr-4">Quality</th>
                                        <th className="py-2 pr-4">Action</th>
                                        <th className="py-2 pr-4">Tags</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-white/10">
                                    {backlinks.data.map((item) => (
                                        <tr key={item.id} className="text-gray-200">
                                            <td className="py-2 pr-4">
                                                <div className="font-medium">{item.source_domain}</div>
                                                <a className="text-xs text-blue-400 hover:underline" href={item.source_url} target="_blank" rel="noreferrer">
                                                    {item.source_url}
                                                </a>
                                            </td>
                                            <td className="py-2 pr-4 text-gray-300">{item.anchor || '-'}</td>
                                            <td className="py-2 pr-4">{item.rel || '-'}</td>
                                            <td className="py-2 pr-4">
                                                <span className={`px-2 py-1 rounded-full text-xs ${
                                                    item.risk_score >= 80 ? 'bg-red-500/20 text-red-300' :
                                                    item.risk_score >= 55 ? 'bg-yellow-500/20 text-yellow-300' :
                                                    'bg-green-500/20 text-green-300'
                                                }`}>
                                                    {item.risk_score ?? 0}
                                                </span>
                                            </td>
                                            <td className="py-2 pr-4">
                                                <span className="px-2 py-1 rounded-full text-xs bg-blue-500/20 text-blue-300">
                                                    {item.quality_score ?? 0}
                                                </span>
                                            </td>
                                            <td className="py-2 pr-4">
                                                <select
                                                    className="rounded-lg border-gray-600 bg-gray-900 text-gray-200 text-xs"
                                                    value={item.action_status || 'keep'}
                                                    onChange={(e) => updateAction(item.id, e.target.value)}
                                                >
                                                    <option value="keep">Keep</option>
                                                    <option value="review">Review</option>
                                                    <option value="remove">Remove</option>
                                                    <option value="disavow">Disavow</option>
                                                </select>
                                            </td>
                                            <td className="py-2 pr-4">
                                                <div className="flex items-center gap-2">
                                                    <select
                                                        multiple
                                                        className="rounded-lg border-gray-600 bg-gray-900 text-gray-200 text-xs"
                                                        value={tagSelections[item.id] || []}
                                                        onChange={(e) => onTagChange(item.id, e)}
                                                    >
                                                        {tags.map((tag) => (
                                                            <option key={tag.id} value={tag.id}>{tag.name}</option>
                                                        ))}
                                                    </select>
                                                    <Button size="xs" variant="secondary" onClick={() => saveTags(item.id)}>
                                                        Save
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    {backlinks?.links && backlinks.links.length > 3 && (
                        <div className="mt-4 flex flex-wrap gap-2">
                            {backlinks.links.map((link, index) => (
                                <Link
                                    key={index}
                                    href={link.url || '#'}
                                    className={`px-3 py-1 rounded text-sm ${
                                        link.active
                                            ? 'bg-blue-600 text-white'
                                            : link.url
                                            ? 'bg-gray-800 text-gray-200 hover:bg-gray-700'
                                            : 'bg-gray-900 text-gray-600 cursor-not-allowed'
                                    }`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    )}
                </Card>
            </div>
        </AppLayout>
    );
}
