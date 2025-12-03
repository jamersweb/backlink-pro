import { Link, useForm, Head } from '@inertiajs/react';
import Button from '../../Components/Shared/Button';
import Input from '../../Components/Shared/Input';

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();
        post('/login');
    };

    return (
        <>
            <Head title="Login - Backlink Pro" />
            <div className="min-h-screen bg-gradient-to-br from-red-50 via-white to-green-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
                <div className="max-w-md w-full space-y-8">
                    <div>
                        <Link href="/" className="flex justify-center">
                            <span className="text-3xl font-bold bg-gradient-to-r from-red-600 to-green-600 bg-clip-text text-transparent">
                                ⚡ Backlink Pro
                            </span>
                        </Link>
                        <h2 className="mt-6 text-center text-3xl font-extrabold text-gray-900">
                            Sign in to your account
                        </h2>
                        <p className="mt-2 text-center text-sm text-gray-600">
                            Or{' '}
                            <Link href="/register" className="font-medium text-red-600 hover:text-red-500">
                                create a new account
                            </Link>
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
                                autoFocus
                            />

                            <Input
                                label="Password"
                                name="password"
                                type="password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                error={errors.password}
                                required
                            />

                            <div className="flex items-center justify-between">
                                <div className="flex items-center">
                                    <input
                                        id="remember"
                                        name="remember"
                                        type="checkbox"
                                        checked={data.remember}
                                        onChange={(e) => setData('remember', e.target.checked)}
                                        className="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded"
                                    />
                                    <label htmlFor="remember" className="ml-2 block text-sm text-gray-900">
                                        Remember me
                                    </label>
                                </div>
                                <div className="text-sm">
                                    <Link href="/forgot-password" className="font-medium text-red-600 hover:text-red-500">
                                        Forgot password?
                                    </Link>
                                </div>
                            </div>

                            <div>
                                <Button
                                    type="submit"
                                    variant="primary"
                                    className="w-full"
                                    disabled={processing}
                                >
                                    {processing ? 'Signing in...' : 'Sign In'}
                                </Button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </>
    );
}

