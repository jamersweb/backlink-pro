import { Link, useForm, usePage } from '@inertiajs/react';
import AppLayout from '../../Components/Layout/AppLayout';
import Card from '../../Components/Shared/Card';
import Button from '../../Components/Shared/Button';

export default function GmailIndex({ connectedAccounts }) {
    const { flash } = usePage().props;
    const { post, processing } = useForm();

    const handleDisconnect = (id) => {
        if (confirm('Are you sure you want to disconnect this Gmail account?')) {
            post(`/gmail/oauth/disconnect/${id}`);
        }
    };

    const getStatusBadge = (status) => {
        const colors = {
            active: 'bg-green-100 text-green-800',
            revoked: 'bg-red-100 text-red-800',
            expired: 'bg-yellow-100 text-yellow-800',
            error: 'bg-red-100 text-red-800',
        };
        return (
            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${colors[status] || colors.error}`}>
                {status}
            </span>
        );
    };

    return (
        <AppLayout header="Gmail Account Management">
            <div className="space-y-6">
                {/* Success Message */}
                {flash?.success && (
                    <div className="p-4 bg-green-50 border border-green-200 rounded-md">
                        <p className="text-sm text-green-800">{flash.success}</p>
                    </div>
                )}
                
                {/* Error Message */}
                {flash?.error && (
                    <div className="p-4 bg-red-50 border border-red-200 rounded-md">
                        <p className="text-sm text-red-800">{flash.error}</p>
                    </div>
                )}
                
                <div className="flex justify-between items-center">
                    <h1 className="text-2xl font-bold text-gray-900">Connected Gmail Accounts</h1>
                    <Link href="/gmail/oauth/connect">
                        <Button variant="primary">Connect Gmail Account</Button>
                    </Link>
                </div>

                {connectedAccounts && connectedAccounts.length > 0 ? (
                    <div className="grid grid-cols-1 gap-6">
                        {connectedAccounts.map((account) => (
                            <Card key={account.id}>
                                <div className="flex items-center justify-between">
                                    <div className="flex-1">
                                        <div className="flex items-center gap-3 mb-2">
                                            <h3 className="text-lg font-semibold text-gray-900">{account.email}</h3>
                                            {getStatusBadge(account.status)}
                                        </div>
                                        <div className="text-sm text-gray-600 space-y-1">
                                            <p><strong>Provider:</strong> {account.provider}</p>
                                            <p><strong>Used in campaigns:</strong> {account.campaigns_count || 0}</p>
                                            {account.expires_at && (
                                                <p><strong>Expires:</strong> {new Date(account.expires_at).toLocaleDateString()}</p>
                                            )}
                                        </div>
                                    </div>
                                    <div>
                                        {account.status === 'active' && (
                                            <Button
                                                variant="outline"
                                                onClick={() => handleDisconnect(account.id)}
                                                disabled={processing}
                                            >
                                                Disconnect
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            </Card>
                        ))}
                    </div>
                ) : (
                    <Card>
                        <div className="text-center py-12">
                            <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLineCap="round" strokeLineJoin="round" strokeWidth={2} d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <h3 className="mt-2 text-sm font-medium text-gray-900">No Gmail accounts</h3>
                            <p className="mt-1 text-sm text-gray-500">Get started by connecting a Gmail account.</p>
                            <div className="mt-6">
                                <Link href="/gmail/oauth/connect">
                                    <Button variant="primary">Connect Gmail Account</Button>
                                </Link>
                            </div>
                        </div>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}

