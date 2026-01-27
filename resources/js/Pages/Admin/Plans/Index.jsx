import AdminLayout from '@/Components/Layout/AdminLayout';
import Card from '@/Components/Shared/Card';
import { Link } from '@inertiajs/react';
import Button from '@/Components/Shared/Button';

export default function PlansIndex({ plans, total }) {
    return (
        <AdminLayout header="Subscription Plans">
            <div className="space-y-6">
                {/* Action Button */}
                <div className="flex justify-end">
                    <Link href="/admin/plans/create">
                        <Button variant="primary">➕ Create New Plan</Button>
                    </Link>
                </div>

                {/* Stats Card */}
                <Card className="p-6 bg-white border border-gray-200 shadow-md">
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-gray-600 text-sm font-medium mb-2 flex items-center gap-2">
                                <span className="text-xl">💳</span>
                                Total Plans
                            </p>
                            <p className="text-4xl font-bold text-gray-900 mt-2">{total || 0}</p>
                            <p className="text-gray-500 text-xs mt-2">Available subscription plans</p>
                        </div>
                        <div className="h-20 w-20 rounded-lg bg-gray-100 flex items-center justify-center">
                            <svg className="h-10 w-10 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </div>
                    </div>
                </Card>

                {/* Plans Grid */}
                {plans && plans.length > 0 ? (
                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                        {plans.map((plan) => (
                            <Card key={plan.id} className="bg-white border border-gray-200 shadow-md hover:shadow-lg transition-shadow">
                                <div className="p-6">
                                    <div className="flex items-center justify-between mb-4">
                                        <h3 className="text-xl font-bold text-gray-900">{plan.name}</h3>
                                        <span className={`px-2 py-1 text-xs font-medium rounded ${
                                            plan.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                                        }`}>
                                            {plan.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                    </div>
                                    
                                    <p className="text-gray-600 text-sm mb-4">{plan.description}</p>
                                    
                                    <div className="mb-4">
                                        <span className="text-3xl font-bold text-gray-900">${plan.price}</span>
                                        <span className="text-gray-600 ml-1">/{plan.billing_interval}</span>
                                    </div>

                                    <div className="mb-4 space-y-2">
                                        <div className="text-xs text-gray-600">
                                            <strong>Max Domains:</strong> {plan.max_domains === -1 ? 'Unlimited' : plan.max_domains}
                                        </div>
                                        <div className="text-xs text-gray-600">
                                            <strong>Max Campaigns:</strong> {plan.max_campaigns === -1 ? 'Unlimited' : plan.max_campaigns}
                                        </div>
                                        <div className="text-xs text-gray-600">
                                            <strong>Daily Backlinks:</strong> {plan.daily_backlink_limit === -1 ? 'Unlimited' : plan.daily_backlink_limit}
                                        </div>
                                    </div>

                                    <Link href={`/admin/plans/${plan.id}`}>
                                        <Button variant="primary" className="w-full">
                                            View Details
                                        </Button>
                                    </Link>
                                </div>
                            </Card>
                        ))}
                    </div>
                ) : (
                    <Card className="bg-white border border-gray-200 shadow-md">
                        <div className="text-center py-16">
                            <div className="inline-block p-6 bg-gray-100 rounded-full mb-4">
                                <span className="text-5xl">💳</span>
                            </div>
                            <p className="text-gray-500 font-medium text-lg">No plans found</p>
                            <p className="text-gray-400 text-sm mt-2">Plans will appear here once created</p>
                        </div>
                    </Card>
                )}
            </div>
        </AdminLayout>
    );
}

