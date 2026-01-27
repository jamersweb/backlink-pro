import { Link, useForm, Head } from '@inertiajs/react';
import { useState } from 'react';

export default function Register() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });
    const [showPassword, setShowPassword] = useState(false);
    const [showConfirmPassword, setShowConfirmPassword] = useState(false);

    const submit = (e) => {
        e.preventDefault();
        post('/register');
    };

    // Password strength indicator
    const getPasswordStrength = (password) => {
        let strength = 0;
        if (password.length >= 8) strength++;
        if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
        if (password.match(/\d/)) strength++;
        if (password.match(/[^a-zA-Z\d]/)) strength++;
        return strength;
    };

    const passwordStrength = getPasswordStrength(data.password);
    const strengthLabels = ['Weak', 'Fair', 'Good', 'Strong'];
    const strengthColors = ['#F04438', '#F79009', '#2F6BFF', '#12B76A'];

    return (
        <>
            <Head title="Register - BacklinkPro" />
            <div className="min-h-screen bg-[#0B0F14] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
                {/* Background Effects */}
                <div className="absolute inset-0 overflow-hidden">
                    {/* Gradient orbs */}
                    <div className="absolute top-1/4 -right-32 w-96 h-96 bg-[#2F6BFF] rounded-full mix-blend-multiply filter blur-[128px] opacity-20 animate-pulse"></div>
                    <div className="absolute bottom-1/4 -left-32 w-96 h-96 bg-[#B6F400] rounded-full mix-blend-multiply filter blur-[128px] opacity-10 animate-pulse" style={{animationDelay: '1s'}}></div>
                    <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-[#B6F400] rounded-full mix-blend-multiply filter blur-[200px] opacity-5"></div>
                    
                    {/* Grid pattern */}
                    <div className="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAwIDEwIEwgNDAgMTAgTSAxMCAwIEwgMTAgNDAgTSAwIDIwIEwgNDAgMjAgTSAyMCAwIEwgMjAgNDAgTSAwIDMwIEwgNDAgMzAgTSAzMCAwIEwgMzAgNDAiIGZpbGw9Im5vbmUiIHN0cm9rZT0icmdiYSgyNTUsMjU1LDI1NSwwLjAzKSIgc3Ryb2tlLXdpZHRoPSIxIi8+PC9wYXR0ZXJuPjwvZGVmcz48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSJ1cmwoI2dyaWQpIi8+PC9zdmc+')] opacity-50"></div>
                </div>

                <div className="max-w-md w-full space-y-8 relative z-10">
                    {/* Logo & Header */}
                    <div className="text-center">
                        <Link href="/" className="inline-flex items-center gap-3 group">
                            <div className="w-12 h-12 rounded-xl bg-gradient-to-br from-[#B6F400] to-[#2F6BFF] p-[2px] group-hover:shadow-lg group-hover:shadow-[#B6F400]/25 transition-all duration-300">
                                <div className="w-full h-full rounded-[10px] bg-[#0B0F14] flex items-center justify-center">
                                    <svg className="w-6 h-6 text-[#B6F400]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                    </svg>
                                </div>
                            </div>
                            <span className="text-2xl font-bold text-[#E5E7EB] group-hover:text-white transition-colors">
                                BacklinkPro
                            </span>
                        </Link>
                        <h1 className="mt-8 text-3xl font-bold text-white tracking-tight">
                            Start building backlinks
                        </h1>
                        <p className="mt-2 text-[#9CA3AF]">
                            Create your free account in seconds
                        </p>
                    </div>

                    {/* Register Form Card */}
                    <form onSubmit={submit} className="mt-8">
                        <div className="bg-[#111827]/80 backdrop-blur-xl rounded-2xl border border-white/10 p-8 shadow-2xl shadow-black/50">
                            <div className="space-y-5">
                                {/* Name Field */}
                                <div>
                                    <label htmlFor="name" className="block text-sm font-medium text-[#E5E7EB] mb-2">
                                        Full Name
                                    </label>
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <svg className="h-5 w-5 text-[#9CA3AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </div>
                                        <input
                                            id="name"
                                            name="name"
                                            type="text"
                                            autoComplete="name"
                                            required
                                            autoFocus
                                            value={data.name}
                                            onChange={(e) => setData('name', e.target.value)}
                                            className={`block w-full pl-12 pr-4 py-3.5 bg-[#0B0F14] border ${errors.name ? 'border-red-500' : 'border-white/10'} rounded-xl text-white placeholder-[#6B7280] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF] focus:border-transparent transition-all duration-200`}
                                            placeholder="John Doe"
                                        />
                                    </div>
                                    {errors.name && (
                                        <p className="mt-2 text-sm text-red-400 flex items-center gap-1">
                                            <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                            </svg>
                                            {errors.name}
                                        </p>
                                    )}
                                </div>

                                {/* Email Field */}
                                <div>
                                    <label htmlFor="email" className="block text-sm font-medium text-[#E5E7EB] mb-2">
                                        Email Address
                                    </label>
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <svg className="h-5 w-5 text-[#9CA3AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <input
                                            id="email"
                                            name="email"
                                            type="email"
                                            autoComplete="email"
                                            required
                                            value={data.email}
                                            onChange={(e) => setData('email', e.target.value)}
                                            className={`block w-full pl-12 pr-4 py-3.5 bg-[#0B0F14] border ${errors.email ? 'border-red-500' : 'border-white/10'} rounded-xl text-white placeholder-[#6B7280] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF] focus:border-transparent transition-all duration-200`}
                                            placeholder="you@example.com"
                                        />
                                    </div>
                                    {errors.email && (
                                        <p className="mt-2 text-sm text-red-400 flex items-center gap-1">
                                            <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                            </svg>
                                            {errors.email}
                                        </p>
                                    )}
                                </div>

                                {/* Password Field */}
                                <div>
                                    <label htmlFor="password" className="block text-sm font-medium text-[#E5E7EB] mb-2">
                                        Password
                                    </label>
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <svg className="h-5 w-5 text-[#9CA3AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                            </svg>
                                        </div>
                                        <input
                                            id="password"
                                            name="password"
                                            type={showPassword ? 'text' : 'password'}
                                            autoComplete="new-password"
                                            required
                                            value={data.password}
                                            onChange={(e) => setData('password', e.target.value)}
                                            className={`block w-full pl-12 pr-12 py-3.5 bg-[#0B0F14] border ${errors.password ? 'border-red-500' : 'border-white/10'} rounded-xl text-white placeholder-[#6B7280] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF] focus:border-transparent transition-all duration-200`}
                                            placeholder="••••••••"
                                        />
                                        <button
                                            type="button"
                                            onClick={() => setShowPassword(!showPassword)}
                                            className="absolute inset-y-0 right-0 pr-4 flex items-center text-[#9CA3AF] hover:text-white transition-colors"
                                        >
                                            {showPassword ? (
                                                <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                                </svg>
                                            ) : (
                                                <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            )}
                                        </button>
                                    </div>
                                    {/* Password Strength Indicator */}
                                    {data.password && (
                                        <div className="mt-2">
                                            <div className="flex gap-1 mb-1">
                                                {[0, 1, 2, 3].map((index) => (
                                                    <div
                                                        key={index}
                                                        className={`h-1 flex-1 rounded-full transition-all duration-300 ${
                                                            index < passwordStrength
                                                                ? ''
                                                                : 'bg-white/10'
                                                        }`}
                                                        style={{
                                                            backgroundColor: index < passwordStrength ? strengthColors[passwordStrength - 1] : undefined
                                                        }}
                                                    ></div>
                                                ))}
                                            </div>
                                            <p className="text-xs" style={{ color: strengthColors[passwordStrength - 1] || '#6B7280' }}>
                                                {passwordStrength > 0 ? strengthLabels[passwordStrength - 1] : 'Enter a password'}
                                            </p>
                                        </div>
                                    )}
                                    {errors.password && (
                                        <p className="mt-2 text-sm text-red-400 flex items-center gap-1">
                                            <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                            </svg>
                                            {errors.password}
                                        </p>
                                    )}
                                </div>

                                {/* Confirm Password Field */}
                                <div>
                                    <label htmlFor="password_confirmation" className="block text-sm font-medium text-[#E5E7EB] mb-2">
                                        Confirm Password
                                    </label>
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <svg className="h-5 w-5 text-[#9CA3AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                            </svg>
                                        </div>
                                        <input
                                            id="password_confirmation"
                                            name="password_confirmation"
                                            type={showConfirmPassword ? 'text' : 'password'}
                                            autoComplete="new-password"
                                            required
                                            value={data.password_confirmation}
                                            onChange={(e) => setData('password_confirmation', e.target.value)}
                                            className={`block w-full pl-12 pr-12 py-3.5 bg-[#0B0F14] border ${errors.password_confirmation ? 'border-red-500' : data.password_confirmation && data.password === data.password_confirmation ? 'border-[#12B76A]' : 'border-white/10'} rounded-xl text-white placeholder-[#6B7280] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF] focus:border-transparent transition-all duration-200`}
                                            placeholder="••••••••"
                                        />
                                        <button
                                            type="button"
                                            onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                                            className="absolute inset-y-0 right-0 pr-4 flex items-center text-[#9CA3AF] hover:text-white transition-colors"
                                        >
                                            {showConfirmPassword ? (
                                                <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                                </svg>
                                            ) : (
                                                <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            )}
                                        </button>
                                    </div>
                                    {data.password_confirmation && data.password === data.password_confirmation && (
                                        <p className="mt-2 text-sm text-[#12B76A] flex items-center gap-1">
                                            <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                            </svg>
                                            Passwords match
                                        </p>
                                    )}
                                    {errors.password_confirmation && (
                                        <p className="mt-2 text-sm text-red-400 flex items-center gap-1">
                                            <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                            </svg>
                                            {errors.password_confirmation}
                                        </p>
                                    )}
                                </div>

                                {/* Submit Button */}
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="relative w-full py-4 px-6 bg-gradient-to-r from-[#B6F400] to-[#8BC400] text-[#0B0F14] font-semibold rounded-xl hover:from-[#A3DB00] hover:to-[#7AAD00] focus:outline-none focus:ring-2 focus:ring-[#B6F400] focus:ring-offset-2 focus:ring-offset-[#111827] disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 group overflow-hidden"
                                >
                                    <span className="relative z-10 flex items-center justify-center gap-2">
                                        {processing ? (
                                            <>
                                                <svg className="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Creating Account...
                                            </>
                                        ) : (
                                            <>
                                                Create Account
                                                <svg className="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                                </svg>
                                            </>
                                        )}
                                    </span>
                                    <div className="absolute inset-0 bg-gradient-to-r from-white/0 via-white/20 to-white/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></div>
                                </button>

                                {/* Terms */}
                                <p className="text-center text-xs text-[#6B7280]">
                                    By creating an account, you agree to our{' '}
                                    <Link href="/terms" className="text-[#2F6BFF] hover:underline">Terms of Service</Link>
                                    {' '}and{' '}
                                    <Link href="/privacy-policy" className="text-[#2F6BFF] hover:underline">Privacy Policy</Link>
                                </p>
                            </div>
                        </div>
                    </form>

                    {/* Sign In Link */}
                    <p className="text-center text-[#9CA3AF]">
                        Already have an account?{' '}
                        <Link 
                            href="/login" 
                            className="text-[#2F6BFF] hover:text-[#5B8AFF] font-medium transition-colors"
                        >
                            Sign in
                        </Link>
                    </p>

                    {/* Features List */}
                    <div className="grid grid-cols-3 gap-4 pt-4">
                        <div className="text-center">
                            <div className="w-10 h-10 mx-auto bg-[#111827] rounded-lg flex items-center justify-center mb-2">
                                <svg className="w-5 h-5 text-[#2F6BFF]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <p className="text-xs text-[#6B7280]">AI-Powered</p>
                        </div>
                        <div className="text-center">
                            <div className="w-10 h-10 mx-auto bg-[#111827] rounded-lg flex items-center justify-center mb-2">
                                <svg className="w-5 h-5 text-[#B6F400]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <p className="text-xs text-[#6B7280]">Safe & Secure</p>
                        </div>
                        <div className="text-center">
                            <div className="w-10 h-10 mx-auto bg-[#111827] rounded-lg flex items-center justify-center mb-2">
                                <svg className="w-5 h-5 text-[#12B76A]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            </div>
                            <p className="text-xs text-[#6B7280]">Real Results</p>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
