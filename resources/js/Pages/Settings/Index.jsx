import { useForm, usePage } from '@inertiajs/react';
import AppLayout from '../../Components/Layout/AppLayout';
import Card from '../../Components/Shared/Card';
import Button from '../../Components/Shared/Button';
import Input from '../../Components/Shared/Input';

export default function SettingsIndex({ user }) {
    const { flash } = usePage().props;

    const profileForm = useForm({
        name: user.name || '',
        email: user.email || '',
    });

    const passwordForm = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const handleProfileSubmit = (e) => {
        e.preventDefault();
        profileForm.put('/settings/profile');
    };

    const handlePasswordSubmit = (e) => {
        e.preventDefault();
        passwordForm.put('/settings/password', {
            onSuccess: () => {
                passwordForm.reset();
            },
        });
    };

    return (
        <AppLayout header="Settings">
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

                {/* Profile Settings */}
                <Card title="Profile Information">
                    <form onSubmit={handleProfileSubmit} className="space-y-4">
                        <Input
                            label="Name"
                            name="name"
                            value={profileForm.data.name}
                            onChange={(e) => profileForm.setData('name', e.target.value)}
                            error={profileForm.errors.name}
                            required
                        />

                        <Input
                            label="Email"
                            name="email"
                            type="email"
                            value={profileForm.data.email}
                            onChange={(e) => profileForm.setData('email', e.target.value)}
                            error={profileForm.errors.email}
                            required
                        />

                        <div>
                            <Button type="submit" variant="primary" disabled={profileForm.processing}>
                                {profileForm.processing ? 'Updating...' : 'Update Profile'}
                            </Button>
                        </div>
                    </form>
                </Card>

                {/* Password Settings */}
                <Card title="Change Password">
                    <form onSubmit={handlePasswordSubmit} className="space-y-4">
                        <Input
                            label="Current Password"
                            name="current_password"
                            type="password"
                            value={passwordForm.data.current_password}
                            onChange={(e) => passwordForm.setData('current_password', e.target.value)}
                            error={passwordForm.errors.current_password}
                            required
                        />

                        <Input
                            label="New Password"
                            name="password"
                            type="password"
                            value={passwordForm.data.password}
                            onChange={(e) => passwordForm.setData('password', e.target.value)}
                            error={passwordForm.errors.password}
                            required
                        />

                        <Input
                            label="Confirm New Password"
                            name="password_confirmation"
                            type="password"
                            value={passwordForm.data.password_confirmation}
                            onChange={(e) => passwordForm.setData('password_confirmation', e.target.value)}
                            error={passwordForm.errors.password_confirmation}
                            required
                        />

                        <div>
                            <Button type="submit" variant="primary" disabled={passwordForm.processing}>
                                {passwordForm.processing ? 'Updating...' : 'Update Password'}
                            </Button>
                        </div>
                    </form>
                </Card>

                {/* Account Info */}
                <Card title="Account Information">
                    <div className="space-y-2 text-sm">
                        <p><strong>Member since:</strong> {new Date(user.created_at).toLocaleDateString()}</p>
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}

