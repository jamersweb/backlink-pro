import { useForm } from '@inertiajs/react';
import AppLayout from '@/Components/Layout/AppLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';

export default function DisavowIndex({ domain, files, latestRun, pendingCount }) {
    const { data, setData, post, processing, errors } = useForm({
        notes: '',
    });

    const generateFile = (e) => {
        e.preventDefault();
        post(`/domains/${domain.id}/backlinks/disavow/generate`, {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout header={`Disavow • ${domain.host}`}>
            <div className="space-y-6">
                <div className="flex items-center justify-between text-sm text-gray-600">
                    <div>
                        {latestRun ? `Latest run #${latestRun.id}` : 'No completed runs yet'}
                    </div>
                    <div>Marked for disavow: {pendingCount}</div>
                </div>

                <Card title="Generate Disavow File">
                    <form onSubmit={generateFile} className="space-y-3">
                        <textarea
                            className="w-full rounded-lg border-gray-300 text-sm"
                            rows={3}
                            placeholder="Notes (optional)"
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                        />
                        {errors.disavow && (
                            <div className="text-sm text-red-600">{errors.disavow}</div>
                        )}
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Generating...' : 'Generate Disavow File'}
                        </Button>
                    </form>
                </Card>

                <Card title="Disavow Files">
                    {files.length === 0 ? (
                        <div className="text-gray-400">No disavow files yet.</div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-white/10 text-sm">
                                <thead>
                                    <tr className="text-left text-gray-400">
                                        <th className="py-2 pr-4">Version</th>
                                        <th className="py-2 pr-4">Status</th>
                                        <th className="py-2 pr-4">Entries</th>
                                        <th className="py-2 pr-4">Generated</th>
                                        <th className="py-2 pr-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-white/10">
                                    {files.map((file) => (
                                        <tr key={file.id} className="text-gray-200">
                                            <td className="py-2 pr-4">v{file.version}</td>
                                            <td className="py-2 pr-4">{file.status}</td>
                                            <td className="py-2 pr-4">{file.entries_count}</td>
                                            <td className="py-2 pr-4">{file.generated_at || '-'}</td>
                                            <td className="py-2 pr-4">
                                                <Button
                                                    variant="secondary"
                                                    size="xs"
                                                    href={`/domains/${domain.id}/backlinks/disavow/${file.id}/export`}
                                                >
                                                    Download
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </Card>
            </div>
        </AppLayout>
    );
}
