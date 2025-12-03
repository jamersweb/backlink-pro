import { Link, useForm, Head } from '@inertiajs/react';
import Button from '../../Components/Shared/Button';
import Input from '../../Components/Shared/Input';

export default function ForgotPassword() {
    const { data, setData, post, processing, errors, wasSuccessful } = useForm({
        email: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post('/forgot-password');
    };

    return (
        <>
            <Head title="Forgot Password - Backlink Pro" />
            <div className="min-h-screen bg-gradient-to-br from-red-50 via-white to-green-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
                <div className="max-w-md w-full space-y-8">
                    <div>
                        <Link href="/" className="flex justify-center">
                            <span className="text-3xl font-bold bg-gradient-to-r from-red-600 to-green-600 bg-clip-text text-transparent">
                                ⚡ Backlink Pro
                            </span>
                        </Link>
                        <h2 className="mt-6 text-center text-3xl font-extrabold text-gray-900">
                            Forgot your password?
                        </h2>
                        <p className="mt-2 text-center text-sm text-gray-600">
                            No worries! Enter your email address and we'll send you a link to reset your password.
                        </p>
                    </div>

                    {wasSuccessful ? (
                        <div className="bg-white rounded-xl shadow-xl p-8 border-2 border-gray-100">
                            <div className="text-center">
                                <div className="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                                    <svg className="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <h3 className="mt-4 text-lg font-medium text-gray-900">Check your email</h3>
                                <p className="mt-2 text-sm text-gray-600">
                                    We've sent a password reset link to <strong>{data.email}</strong>
                                </p>
                                <p className="mt-2 text-xs text-gray-500">
                                    Didn't receive the email? Check your spam folder or try again.
                                </p>
                                <div className="mt-6">
                                    <Link href="/login">
                                        <Button variant="primary" className="w-full">
                                            Back to Login
                                        </Button>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    ) : (
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
                                    autoFocus
                                    placeholder="Enter your email address"
                                />

                                <div>
                                    <Button
                                        type="submit"
                                        variant="primary"
                                        className="w-full"
                                        disabled={processing}
                                    >
                                        {processing ? 'Sending...' : 'Send Reset Link'}
                                    </Button>
                                </div>

                                <div className="text-center">
                                    <Link href="/login" className="text-sm font-medium text-red-600 hover:text-red-500">
                                        ← Back to Login
                                    </Link>
                                </div>
                            </div>
                        </form>
                    )}
                </div>
            </div>
        </>
    );
}


