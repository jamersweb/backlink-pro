import { Link, usePage } from '@inertiajs/react';
import AppLayout from '../../Components/Layout/AppLayout';
import Card from '../../Components/Shared/Card';
import Button from '../../Components/Shared/Button';

export default function SubscriptionCancel() {
    const { flash } = usePage().props;

    return (
        <AppLayout header="Subscription Cancelled">
            <div className="max-w-2xl mx-auto">
                <Card>
                    <div className="text-center py-8">
                        <div className="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-yellow-100 mb-4">
                            <svg className="h-8 w-8 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLineCap="round" strokeLineJoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        
                        <h2 className="text-2xl font-bold text-gray-900 mb-2">
                            Subscription Cancelled
                        </h2>
                        
                        <p className="text-gray-600 mb-6">
                            Your subscription checkout was cancelled. No charges were made to your account.
                        </p>

                        {flash?.info && (
                            <div className="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-md">
                                <p className="text-sm text-blue-800">{flash.info}</p>
                            </div>
                        )}

                        <div className="flex gap-4 justify-center">
                            <Link href="/pricing">
                                <Button variant="primary">View Plans Again</Button>
                            </Link>
                            <Link href="/dashboard">
                                <Button variant="outline">Go to Dashboard</Button>
                            </Link>
                        </div>
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}

