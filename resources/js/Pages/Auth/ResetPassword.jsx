import { Link, useForm, Head } from '@inertiajs/react';
import Button from '../../Components/Shared/Button';
import Input from '../../Components/Shared/Input';

export default function ResetPassword({ email, token }) {
    const { data, setData, post, processing, errors } = useForm({
        token: token || '',
        email: email || '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post('/reset-password');
    };

    return (
        <>
            <Head title="Reset Password - Backlink Pro" />
            <div className="min-h-screen bg-gradient-to-br from-red-50 via-white to-green-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
                <div className="max-w-md w-full space-y-8">
                    <div>
                        <Link href="/" className="flex justify-center">
                            <span className="text-3xl font-bold bg-gradient-to-r from-red-600 to-green-600 bg-clip-text text-transparent">
                                ⚡ Backlink Pro
                            </span>
                        </Link>
                        <h2 className="mt-6 text-center text-3xl font-extrabold text-gray-900">
                            Reset your password
                        </h2>
                        <p className="mt-2 text-center text-sm text-gray-600">
                            Enter your new password below
                        </p>
                    </div>

                    <form className="mt-8 space-y-6" onSubmit={submit}>
                        <div className="bg-white rounded-xl shadow-xl p-8 space-y-6 border-2 border-gray-100">
                            <Input
                                label="Email Address"
                                name="email"
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                error={errors.email}
                                required
                                disabled
                                className="bg-gray-50"
                            />

                            <Input
                                label="New Password"
                                name="password"
                                type="password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                error={errors.password}
                                required
                                autoFocus
                                placeholder="Enter your new password"
                            />

                            <Input
                                label="Confirm New Password"
                                name="password_confirmation"
                                type="password"
                                value={data.password_confirmation}
                                onChange={(e) => setData('password_confirmation', e.target.value)}
                                error={errors.password_confirmation}
                                required
                                placeholder="Confirm your new password"
                            />

                            <div>
                                <Button
                                    type="submit"
                                    variant="primary"
                                    className="w-full"
                                    disabled={processing}
                                >
                                    {processing ? 'Resetting...' : 'Reset Password'}
                                </Button>
                            </div>

                            <div className="text-center">
                                <Link href="/login" className="text-sm font-medium text-red-600 hover:text-red-500">
                                    ← Back to Login
                                </Link>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </>
    );
}


