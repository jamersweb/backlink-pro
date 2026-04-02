import { Link, useForm, Head } from '@inertiajs/react';
import { useState } from 'react';
import SocialLoginButtons from '@/Components/Auth/SocialLoginButtons';

export default function Register() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });
    const [showPassword, setShowPassword] = useState(false);
    const [showConfirmPassword, setShowConfirmPassword] = useState(false);
    const quickStats = [
        { label: 'Teams onboarded', value: '3.2k' },
        { label: 'Placement workflows', value: '24/7' },
        { label: 'Reporting setup', value: 'Fast' },
    ];

    const submit = (e) => {
        e.preventDefault();
        post('/register');
    };

    return (
        <>
            <Head title="Register - BacklinkPro" />

            <div className="relative flex min-h-screen items-center justify-center overflow-hidden bg-[#050505] px-4 py-6 sm:px-6 lg:px-8">
                <div className="absolute inset-0 overflow-hidden">
                    <div className="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(255,122,69,0.15),transparent_34%),radial-gradient(circle_at_top_right,rgba(255,122,69,0.11),transparent_26%)]"></div>
                    <div className="absolute -top-28 left-[10%] h-72 w-72 rounded-full bg-[#ff6e40] opacity-[0.16] blur-[120px]"></div>
                    <div className="absolute -top-24 right-[6%] h-72 w-72 rounded-full bg-[#ff7b45] opacity-[0.11] blur-[135px]"></div>
                    <div className="absolute inset-x-0 top-0 h-48 bg-gradient-to-b from-[#140d0a] to-transparent"></div>
                    <div className="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAwIDEwIEwgNDAgMTAgTSAxMCAwIEwgMTAgNDAgTSAwIDIwIEwgNDAgMjAgTSAyMCAwIEwgMjAgNDAgTSAwIDMwIEwgNDAgMzAgTSAzMCAwIEwgMzAgNDAiIGZpbGw9Im5vbmUiIHN0cm9rZT0icmdiYSgyNTUsMjU1LDI1NSwwLjAyNSkiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-45"></div>
                </div>

                <div className="relative z-10 w-full max-w-6xl">
                    <div className="grid items-center gap-8 lg:grid-cols-[minmax(0,1.05fr)_minmax(24rem,26.5rem)] lg:gap-10">
                        <section className="hidden lg:block">
                            <Link href="/" className="group inline-flex items-center gap-3">
                                <div className="flex h-11 w-11 items-center justify-center rounded-full border border-white/10 bg-[rgba(255,255,255,0.06)] backdrop-blur-md transition-all duration-300 group-hover:border-[#ff875c]/50 group-hover:bg-[rgba(255,255,255,0.09)]">
                                    <div className="relative h-5 w-5">
                                        <span className="absolute inset-0 rounded-full border-2 border-[#fff4ef]/90"></span>
                                        <span className="absolute left-[3px] top-[3px] h-3 w-3 rounded-full border-2 border-transparent border-l-[#ff8c63] border-t-[#ff8c63] opacity-90"></span>
                                    </div>
                                </div>
                                <span className="text-2xl font-bold text-[#f7f3f0] transition-colors group-hover:text-white">
                                    BacklinkPro
                                </span>
                            </Link>

                            <div className="mt-8 max-w-lg">
                                <div className="inline-flex rounded-full border border-[#ff946d]/20 bg-[#ff946d]/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-[#ffb08f]">
                                    Team Access Setup
                                </div>
                                <h1 className="mt-5 text-[clamp(2.7rem,4vw,4.4rem)] font-bold leading-[0.95] tracking-[-0.04em] text-white">
                                    Start building backlinks,
                                    <span className="block text-[#f0d6c7]/78">from one clean workspace.</span>
                                </h1>
                                <p className="mt-4 max-w-md text-[15px] leading-7 text-[#cab8ad]">
                                    Create your account to manage approvals, placements, evidence logs, and reporting in one focused SEO platform.
                                </p>
                            </div>

                            <div className="mt-8 grid max-w-lg grid-cols-3 gap-3">
                                {quickStats.map((item) => (
                                    <div
                                        key={item.label}
                                        className="rounded-[1.4rem] border border-[#ffffff10] bg-[linear-gradient(180deg,rgba(255,255,255,0.05),rgba(255,255,255,0.02))] px-4 py-4"
                                    >
                                        <div className="text-[11px] uppercase tracking-[0.18em] text-[#a99284]">
                                            {item.label}
                                        </div>
                                        <div className="mt-2 text-2xl font-bold text-white">{item.value}</div>
                                    </div>
                                ))}
                            </div>
                        </section>

                        <section className="w-full max-w-[26.5rem] justify-self-center lg:justify-self-end">
                            <div className="text-center lg:hidden">
                                <Link href="/" className="group inline-flex items-center gap-3">
                                    <div className="flex h-11 w-11 items-center justify-center rounded-full border border-white/10 bg-[rgba(255,255,255,0.06)] backdrop-blur-md transition-all duration-300 group-hover:border-[#ff875c]/50 group-hover:bg-[rgba(255,255,255,0.09)]">
                                        <div className="relative h-5 w-5">
                                            <span className="absolute inset-0 rounded-full border-2 border-[#fff4ef]/90"></span>
                                            <span className="absolute left-[3px] top-[3px] h-3 w-3 rounded-full border-2 border-transparent border-l-[#ff8c63] border-t-[#ff8c63] opacity-90"></span>
                                        </div>
                                    </div>
                                    <span className="text-2xl font-bold text-[#f7f3f0] transition-colors group-hover:text-white">
                                        BacklinkPro
                                    </span>
                                </Link>

                                <h1 className="mt-6 text-[2rem] font-bold tracking-tight text-white">
                                    Create your account
                                </h1>
                                <p className="mt-1.5 text-sm text-[#c9b9ae]">
                                    Start building quality backlinks in minutes
                                </p>
                            </div>

                            <form onSubmit={submit} noValidate className="mt-6 lg:mt-0">
                                <div className="rounded-[28px] border border-[#ffffff12] bg-[linear-gradient(180deg,rgba(26,18,16,0.92),rgba(12,10,10,0.96))] p-6 shadow-[0_30px_80px_rgba(0,0,0,0.46)] backdrop-blur-xl">
                                    <div className="mb-5 hidden lg:block">
                                        <h2 className="text-[2rem] font-bold tracking-tight text-white">Create your account</h2>
                                        <p className="mt-1.5 text-sm text-[#c9b9ae]">
                                            Start building quality backlinks in minutes
                                        </p>
                                    </div>

                                    <div className="space-y-4">
                                        <div>
                                            <label htmlFor="name" className="mb-1.5 block text-[11px] font-medium uppercase tracking-[0.08em] text-[#f1e9e4]">
                                                Full Name
                                            </label>
                                            <div className="relative">
                                                <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                                    <svg className="h-5 w-5 text-[#bfa89a]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                                    className={`block w-full rounded-2xl border ${errors.name ? 'border-red-500' : 'border-[#ffffff14]'} bg-[rgba(255,255,255,0.04)] py-3 pl-12 pr-4 text-sm text-white placeholder-[#8f7f75] transition-all duration-200 focus:border-[#ff875c]/70 focus:outline-none focus:ring-2 focus:ring-[#ff875c]/20`}
                                                    placeholder="John Doe"
                                                />
                                            </div>
                                            {errors.name ? <p className="mt-2 text-sm text-red-400">{errors.name}</p> : null}
                                        </div>

                                        <div>
                                            <label htmlFor="email" className="mb-1.5 block text-[11px] font-medium uppercase tracking-[0.08em] text-[#f1e9e4]">
                                                Email Address
                                            </label>
                                            <div className="relative">
                                                <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                                    <svg className="h-5 w-5 text-[#bfa89a]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                                    className={`block w-full rounded-2xl border ${errors.email ? 'border-red-500' : 'border-[#ffffff14]'} bg-[rgba(255,255,255,0.04)] py-3 pl-12 pr-4 text-sm text-white placeholder-[#8f7f75] transition-all duration-200 focus:border-[#ff875c]/70 focus:outline-none focus:ring-2 focus:ring-[#ff875c]/20`}
                                                    placeholder="you@example.com"
                                                />
                                            </div>
                                            {errors.email ? <p className="mt-2 text-sm text-red-400">{errors.email}</p> : null}
                                        </div>

                                        <div>
                                            <label htmlFor="password" className="mb-1.5 block text-[11px] font-medium uppercase tracking-[0.08em] text-[#f1e9e4]">
                                                Password
                                            </label>
                                            <div className="relative">
                                                <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                                    <svg className="h-5 w-5 text-[#bfa89a]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                                    className={`block w-full rounded-2xl border ${errors.password ? 'border-red-500' : 'border-[#ffffff14]'} bg-[rgba(255,255,255,0.04)] py-3 pl-12 pr-12 text-sm text-white placeholder-[#8f7f75] transition-all duration-200 focus:border-[#ff875c]/70 focus:outline-none focus:ring-2 focus:ring-[#ff875c]/20`}
                                                    placeholder="........"
                                                />
                                                <button
                                                    type="button"
                                                    onClick={() => setShowPassword(!showPassword)}
                                                    className="absolute inset-y-0 right-0 flex items-center pr-4 text-[#bfa89a] transition-colors hover:text-white"
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
                                            {errors.password ? <p className="mt-2 text-sm text-red-400">{errors.password}</p> : null}
                                        </div>

                                        <div>
                                            <label htmlFor="password_confirmation" className="mb-1.5 block text-[11px] font-medium uppercase tracking-[0.08em] text-[#f1e9e4]">
                                                Confirm Password
                                            </label>
                                            <div className="relative">
                                                <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                                    <svg className="h-5 w-5 text-[#bfa89a]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                                    className={`block w-full rounded-2xl border ${errors.password_confirmation ? 'border-red-500' : 'border-[#ffffff14]'} bg-[rgba(255,255,255,0.04)] py-3 pl-12 pr-12 text-sm text-white placeholder-[#8f7f75] transition-all duration-200 focus:border-[#ff875c]/70 focus:outline-none focus:ring-2 focus:ring-[#ff875c]/20`}
                                                    placeholder="........"
                                                />
                                                <button
                                                    type="button"
                                                    onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                                                    className="absolute inset-y-0 right-0 flex items-center pr-4 text-[#bfa89a] transition-colors hover:text-white"
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
                                            {errors.password_confirmation ? <p className="mt-2 text-sm text-red-400">{errors.password_confirmation}</p> : null}
                                        </div>

                                        <button
                                            type="submit"
                                            disabled={processing}
                                            className="group relative w-full overflow-hidden rounded-full border border-[#ffe0d0] bg-[linear-gradient(180deg,#fff7f2,#ffe7db)] px-6 py-3.5 text-sm font-semibold text-[#16100d] shadow-[0_18px_40px_rgba(255,110,64,0.18)] transition-all duration-200 hover:-translate-y-0.5 hover:bg-[linear-gradient(180deg,#fff1ea,#ffdccc)] focus:outline-none focus:ring-2 focus:ring-[#ff875c]/30 disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            <span className="relative z-10 flex items-center justify-center gap-2">
                                                {processing ? (
                                                    <>
                                                        <svg className="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        Creating account...
                                                    </>
                                                ) : (
                                                    <>
                                                        Create Account
                                                        <svg className="h-5 w-5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                                        </svg>
                                                    </>
                                                )}
                                            </span>
                                            <div className="absolute inset-0 translate-x-[-100%] bg-gradient-to-r from-[#ffffff00] via-[#ffffff7a] to-[#ffffff00] transition-transform duration-700 group-hover:translate-x-[100%]"></div>
                                        </button>

                                        <div className="relative pt-1">
                                            <div className="absolute inset-0 flex items-center">
                                                <div className="w-full border-t border-white/10"></div>
                                            </div>
                                            <div className="relative flex justify-center">
                                                <span className="bg-[#171110] px-3 text-xs uppercase tracking-[0.2em] text-[#8f7f75]">
                                                    continue with
                                                </span>
                                            </div>
                                        </div>

                                        <SocialLoginButtons />

                                        <p className="text-center text-xs leading-5 text-[#9f8c80]">
                                            By creating an account, you agree to our{' '}
                                            <Link href="/terms" className="text-[#ff946d] transition-colors hover:text-[#ffb08f]">
                                                Terms
                                            </Link>{' '}
                                            and{' '}
                                            <Link href="/privacy-policy" className="text-[#ff946d] transition-colors hover:text-[#ffb08f]">
                                                Privacy Policy
                                            </Link>
                                            .
                                        </p>
                                    </div>
                                </div>
                            </form>

                            <p className="mt-5 text-center text-sm text-[#b9a79b]">
                                Already have an account?{' '}
                                <Link href="/login" className="font-medium text-[#ff946d] transition-colors hover:text-[#ffb08f]">
                                    Sign in
                                </Link>
                            </p>
                        </section>
                    </div>
                </div>
            </div>
        </>
    );
}
