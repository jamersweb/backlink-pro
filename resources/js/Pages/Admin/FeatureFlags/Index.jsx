import AdminLayout from '@/Components/Layout/AdminLayout';
import Card from '@/Components/Shared/Card';

export default function FeatureFlagsIndex({ flags }) {
    return (
        <AdminLayout header="Feature Flags">
            <Card variant="elevated">
                <p className="text-sm text-[var(--admin-text-muted)] mb-4">
                    Flags are read from config (env). Set in .env to enable/disable. Default: risky features OFF.
                </p>
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b border-[var(--admin-border)]">
                                <th className="text-left py-2 font-medium text-[var(--admin-text)]">Key</th>
                                <th className="text-left py-2 font-medium text-[var(--admin-text)]">Status</th>
                                <th className="text-left py-2 font-medium text-[var(--admin-text)]">Env variable</th>
                            </tr>
                        </thead>
                        <tbody>
                            {(flags || []).map((f) => (
                                <tr key={f.key} className="border-b border-[var(--admin-border)]">
                                    <td className="py-2 text-[var(--admin-text)]">{f.key}</td>
                                    <td className="py-2">
                                        <span className={f.enabled ? 'admin-badge-success' : 'admin-badge-neutral'}>
                                            {f.enabled ? 'ON' : 'OFF'}
                                        </span>
                                    </td>
                                    <td className="py-2 font-mono text-xs text-[var(--admin-text-muted)]">{f.env_key}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </Card>
        </AdminLayout>
    );
}
