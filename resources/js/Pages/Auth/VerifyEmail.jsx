import { Head, useForm, Link } from '@inertiajs/react';
import Button from '../../Components/Shared/Button';
import Card from '../../Components/Shared/Card';

export default function VerifyEmail({ status }) {
    const { post, processing } = useForm({});

    const submit = (e) => {
        e.preventDefault();
        post('/email/verification-notification');
    };

    return (
        <>
            <Head title="Verify Email - Backlink Pro" />
            <div className="min-h-screen bg-gradient-to-br from-red-50 via-white to-green-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
                <div className="max-w-md w-full">
                    <Card className="p-8">
                        <div className="text-center mb-6">
                            <div className="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-gradient-to-br from-red-500 to-green-500 mb-4">
                                <svg className="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <h2 className="text-3xl font-bold text-gray-900 mb-2">
                                Verify Your Email
                            </h2>
                            <p className="text-gray-600">
                                Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you?
                            </p>
                        </div>

                        {status === 'verification-link-sent' && (
                            <div className="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                                <p className="text-sm text-green-800">
                                    A new verification link has been sent to your email address.
                                </p>
                            </div>
                        )}

                        {status === 'email-verified' && (
                            <div className="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                                <p className="text-sm text-green-800">
                                    Your email has been verified! You can now access all features.
                                </p>
                            </div>
                        )}

                        <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                            <p className="text-sm text-red-800 font-semibold mb-2">
                                ⚠️ Email Verification Required
                            </p>
                            <p className="text-sm text-red-700">
                                You must verify your email address before you can access any features of the application. 
                                Please check your email inbox and click the verification link.
                            </p>
                        </div>

                        <div className="space-y-4">
                            <p className="text-sm text-gray-600 text-center">
                                If you didn't receive the email, we'll gladly send you another.
                            </p>

                            <form onSubmit={submit}>
                                <Button
                                    type="submit"
                                    variant="primary"
                                    className="w-full"
                                    disabled={processing}
                                >
                                    {processing ? 'Sending...' : 'Resend Verification Email'}
                                </Button>
                            </form>

                            <div className="text-center">
                                <Link
                                    href="/logout"
                                    method="post"
                                    className="text-sm text-gray-600 hover:text-red-600"
                                >
                                    Sign out
                                </Link>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>
        </>
    );
}

