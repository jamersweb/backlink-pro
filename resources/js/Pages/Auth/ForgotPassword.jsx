import { Link, useForm, Head } from '@inertiajs/react';

export default function ForgotPassword() {
    const { data, setData, post, processing, errors, wasSuccessful } = useForm({
        email: '',
    });

    const trustNotes = [
        { label: 'Reset delivery', value: 'Instant' },
        { label: 'Account safety', value: 'Secure' },
        { label: 'Recovery flow', value: 'Guided' },
    ];

    const submit = (e) => {
        e.preventDefault();
        post('/forgot-password');
    };

    return (
        <>
            <Head title="Forgot Password - BacklinkPro" />

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
                            <Link href="/" className="inline-flex items-center gap-3 group">
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
                                    Secure Account Recovery
                                </div>
                                <h1 className="mt-5 text-[clamp(2.7rem,4vw,4.5rem)] font-bold leading-[0.95] tracking-[-0.04em] text-white">
                                    Get back into your workspace,
                                    <span className="block text-[#f0d6c7]/78">without losing momentum.</span>
                                </h1>
                                <p className="mt-4 max-w-md text-[15px] leading-7 text-[#cab8ad]">
                                    We&apos;ll send a secure reset link so you can continue managing approvals, evidence logs, and backlink operations from one place.
                                </p>
                            </div>

                            <div className="mt-8 grid max-w-lg grid-cols-3 gap-3">
                                {trustNotes.map((item) => (
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

                        <section className="w-full max-w-[25rem] justify-self-center lg:justify-self-end">
                            <div className="text-center lg:hidden">
                                <Link href="/" className="inline-flex items-center gap-3 group">
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
                                    Reset your password
                                </h1>
                                <p className="mt-1.5 text-sm text-[#c9b9ae]">
                                    We&apos;ll email you a secure recovery link
                                </p>
                            </div>

                            <div className="mt-6 lg:mt-0 rounded-[28px] border border-[#ffffff12] bg-[linear-gradient(180deg,rgba(26,18,16,0.92),rgba(12,10,10,0.96))] p-6 shadow-[0_30px_80px_rgba(0,0,0,0.46)] backdrop-blur-xl">
                                <div className="mb-5 hidden lg:block">
                                    <h2 className="text-[2rem] font-bold tracking-tight text-white">Forgot password?</h2>
                                    <p className="mt-1.5 text-sm text-[#c9b9ae]">
                                        Enter your email and we&apos;ll send a secure reset link.
                                    </p>
                                </div>

                                {wasSuccessful ? (
                                    <div className="space-y-5 text-center">
                                        <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-full border border-[#ff946d]/25 bg-[#ff946d]/10 text-[#ffb08f]">
                                            <svg className="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.8} d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 className="text-xl font-semibold text-white">Check your email</h3>
                                            <p className="mt-2 text-sm leading-6 text-[#c9b9ae]">
                                                We&apos;ve sent a password reset link to <span className="font-medium text-[#fff2eb]">{data.email}</span>.
                                            </p>
                                            <p className="mt-2 text-xs text-[#9f8c80]">
                                                If you don&apos;t see it soon, check your spam folder and try again.
                                            </p>
                                        </div>

                                        <div className="space-y-3">
                                            <Link
                                                href="/login"
                                                className="group relative flex w-full items-center justify-center overflow-hidden rounded-full border border-[#ffe0d0] bg-[linear-gradient(180deg,#fff7f2,#ffe7db)] px-6 py-3.5 text-sm font-semibold text-[#16100d] shadow-[0_18px_40px_rgba(255,110,64,0.18)] transition-all duration-200 hover:-translate-y-0.5 hover:bg-[linear-gradient(180deg,#fff1ea,#ffdccc)]"
                                            >
                                                Back to Sign In
                                            </Link>
                                            <button
                                                type="button"
                                                onClick={submit}
                                                disabled={processing}
                                                className="w-full rounded-full border border-[#ff946d]/25 bg-[#ff946d]/10 px-6 py-3 text-sm font-semibold text-[#fff2eb] transition-colors hover:bg-[#ff946d]/16 disabled:opacity-50"
                                            >
                                                Send again
                                            </button>
                                        </div>
                                    </div>
                                ) : (
                                    <form onSubmit={submit} noValidate className="space-y-4">
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
                                                    autoFocus
                                                    value={data.email}
                                                    onChange={(e) => setData('email', e.target.value)}
                                                    className={`block w-full rounded-2xl border ${errors.email ? 'border-red-500' : 'border-[#ffffff14]'} bg-[rgba(255,255,255,0.04)] py-3 pl-12 pr-4 text-sm text-white placeholder-[#8f7f75] transition-all duration-200 focus:border-[#ff875c]/70 focus:outline-none focus:ring-2 focus:ring-[#ff875c]/20`}
                                                    placeholder="you@example.com"
                                                />
                                            </div>
                                            {errors.email ? <p className="mt-2 text-sm text-red-400">{errors.email}</p> : null}
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
                                                        Sending reset link...
                                                    </>
                                                ) : (
                                                    <>
                                                        Send Reset Link
                                                        <svg className="h-5 w-5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                                        </svg>
                                                    </>
                                                )}
                                            </span>
                                            <div className="absolute inset-0 translate-x-[-100%] bg-gradient-to-r from-[#ffffff00] via-[#ffffff7a] to-[#ffffff00] transition-transform duration-700 group-hover:translate-x-[100%]"></div>
                                        </button>

                                        <div className="rounded-2xl border border-[#ff946d]/14 bg-[rgba(255,148,109,0.06)] px-4 py-3 text-sm text-[#cab8ad]">
                                            Use the same email linked to your BacklinkPro account for the fastest recovery.
                                        </div>

                                        <div className="text-center">
                                            <Link href="/login" className="text-sm font-medium text-[#ff946d] transition-colors hover:text-[#ffb08f]">
                                                Back to Sign In
                                            </Link>
                                        </div>
                                    </form>
                                )}
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </>
    );
}
