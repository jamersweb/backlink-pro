import { useEffect } from 'react';
import { Link, usePage } from '@inertiajs/react';
import AppLayout from '../../Components/Layout/AppLayout';
import Card from '../../Components/Shared/Card';
import Button from '../../Components/Shared/Button';

export default function SubscriptionSuccess() {
    const { flash } = usePage().props;

    return (
        <AppLayout header="Subscription Success">
            <div className="max-w-2xl mx-auto">
                <Card>
                    <div className="text-center py-8">
                        <div className="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                            <svg className="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLineCap="round" strokeLineJoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                            </svg>
                        </div>

                        <h2 className="text-2xl font-bold text-gray-900 mb-2">
                            Subscription Activated Successfully!
                        </h2>

                        <p className="text-gray-600 mb-6">
                            Your subscription has been activated and you now have access to all premium features.
                        </p>

                        {flash?.success && (
                            <div className="mb-4 p-4 bg-green-50 border border-green-200 rounded-md">
                                <p className="text-sm text-green-800">{flash.success}</p>
                            </div>
                        )}

                        {flash?.error && (
                            <div className="mb-4 p-4 bg-red-50 border border-red-200 rounded-md">
                                <p className="text-sm text-red-800">{flash.error}</p>
                            </div>
                        )}

                        <div className="flex gap-4 justify-center">
                            <Link href="/dashboard">
                                <Button variant="primary">Go to Dashboard</Button>
                            </Link>
                            <Link href="/campaign/create">
                                <Button variant="secondary">Create Campaign</Button>
                            </Link>
                        </div>
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}

