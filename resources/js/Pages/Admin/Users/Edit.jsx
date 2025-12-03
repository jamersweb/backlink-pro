import { useState } from 'react';
import { useForm, router, usePage } from '@inertiajs/react';
import AdminLayout from '../../../Components/Layout/AdminLayout';
import Card from '../../../Components/Shared/Card';
import Button from '../../../Components/Shared/Button';
import Input from '../../../Components/Shared/Input';

export default function AdminUsersEdit({ user, plans }) {
    const { flash } = usePage().props;
    const { data, setData, put, processing, errors } = useForm({
        name: user.name || '',
        email: user.email || '',
        plan_id: user.plan_id || '',
        subscription_status: user.subscription_status || '',
    });

    const [showPasswordReset, setShowPasswordReset] = useState(false);
    const { data: passwordData, setData: setPasswordData, post: postPassword, processing: passwordProcessing } = useForm({
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();
        put(`/admin/users/${user.id}`);
    };

    const handlePasswordReset = (e) => {
        e.preventDefault();
        postPassword(`/admin/users/${user.id}/reset-password`, {
            onSuccess: () => {
                setShowPasswordReset(false);
                setPasswordData({ password: '', password_confirmation: '' });
            },
        });
    };

    return (
        <AdminLayout header={`Edit User: ${user.name}`}>
            <div className="space-y-6">
                {/* Flash Messages */}
                {flash?.success && (
                    <div className="p-4 bg-green-50 border border-green-200 rounded-md">
                        <p className="text-sm text-green-800">{flash.success}</p>
                    </div>
                )}
                {flash?.error && (
                    <div className="p-4 bg-red-50 border border-red-200 rounded-md">
                        <p className="text-sm text-red-800">{flash.error}</p>
                    </div>
                )}

                {/* Back Button */}
                <div>
                    <Button variant="secondary" onClick={() => router.visit(`/admin/users/${user.id}`)}>
                        ← Back to User Details
                    </Button>
                </div>

                {/* Edit Form */}
                <Card className="bg-white border border-gray-200 shadow-md">
                    <h3 className="text-lg font-bold text-gray-900 mb-6">Edit User Information</h3>
                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <Input
                                label="Name"
                                name="name"
                                type="text"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                error={errors.name}
                                required
                            />

                            <Input
                                label="Email"
                                name="email"
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                error={errors.email}
                                required
                            />

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Plan
                                </label>
                                <select
                                    value={data.plan_id || ''}
                                    onChange={(e) => setData('plan_id', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500"
                                >
                                    <option value="">No Plan</option>
                                    {plans?.map((plan) => (
                                        <option key={plan.id} value={plan.id}>
                                            {plan.name} (${plan.price}/{plan.billing_interval})
                                        </option>
                                    ))}
                                </select>
                                {errors.plan_id && (
                                    <p className="mt-1 text-sm text-red-600">{errors.plan_id}</p>
                                )}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Subscription Status
                                </label>
                                <select
                                    value={data.subscription_status || ''}
                                    onChange={(e) => setData('subscription_status', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500"
                                >
                                    <option value="">None</option>
                                    <option value="active">Active</option>
                                    <option value="cancelled">Cancelled</option>
                                    <option value="past_due">Past Due</option>
                                    <option value="trialing">Trialing</option>
                                </select>
                                {errors.subscription_status && (
                                    <p className="mt-1 text-sm text-red-600">{errors.subscription_status}</p>
                                )}
                            </div>
                        </div>

                        <div className="flex gap-4">
                            <Button
                                type="submit"
                                variant="primary"
                                disabled={processing}
                            >
                                {processing ? 'Saving...' : 'Save Changes'}
                            </Button>
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={() => router.visit(`/admin/users/${user.id}`)}
                            >
                                Cancel
                            </Button>
                        </div>
                    </form>
                </Card>

                {/* Password Reset Section */}
                <Card className="bg-white border border-gray-200 shadow-md">
                    <div className="flex items-center justify-between mb-4">
                        <h3 className="text-lg font-bold text-gray-900">Reset Password</h3>
                        <Button
                            variant={showPasswordReset ? "secondary" : "outline"}
                            onClick={() => setShowPasswordReset(!showPasswordReset)}
                        >
                            {showPasswordReset ? 'Cancel' : 'Reset Password'}
                        </Button>
                    </div>

                    {showPasswordReset && (
                        <form onSubmit={handlePasswordReset} className="space-y-4">
                            <div className="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                                <p className="text-sm text-yellow-800">
                                    ⚠️ This will reset the user's password. They will need to use the new password to log in.
                                </p>
                            </div>

                            <Input
                                label="New Password"
                                name="password"
                                type="password"
                                value={passwordData.password}
                                onChange={(e) => setPasswordData('password', e.target.value)}
                                error={errors.password}
                                required
                            />

                            <Input
                                label="Confirm New Password"
                                name="password_confirmation"
                                type="password"
                                value={passwordData.password_confirmation}
                                onChange={(e) => setPasswordData('password_confirmation', e.target.value)}
                                error={errors.password_confirmation}
                                required
                            />

                            <Button
                                type="submit"
                                variant="primary"
                                disabled={passwordProcessing}
                            >
                                {passwordProcessing ? 'Resetting...' : 'Reset Password'}
                            </Button>
                        </form>
                    )}
                </Card>
            </div>
        </AdminLayout>
    );
}


