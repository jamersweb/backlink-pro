import { Head, useForm, Link } from '@inertiajs/react';

export default function VerifyEmail({ status }) {
    const { post, processing } = useForm({});

    const quickStats = [
        { label: 'Verification flow', value: 'Fast' },
        { label: 'Workspace access', value: 'Secure' },
        { label: 'Team onboarding', value: 'Ready' },
    ];

    const submit = (e) => {
        e.preventDefault();
        post('/email/verification-notification', {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Verify Email - BacklinkPro" />

            <div className="relative flex min-h-screen items-center justify-center overflow-hidden bg-[#050505] px-4 py-6 sm:px-6 lg:px-8">
                <div className="absolute inset-0 overflow-hidden">
                    <div className="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(255,122,69,0.15),transparent_34%),radial-gradient(circle_at_top_right,rgba(255,122,69,0.11),transparent_26%)]"></div>
                    <div className="absolute -top-28 left-[10%] h-72 w-72 rounded-full bg-[#ff6e40] opacity-[0.16] blur-[120px]"></div>
                    <div className="absolute -top-24 right-[6%] h-72 w-72 rounded-full bg-[#ff7b45] opacity-[0.11] blur-[135px]"></div>
                    <div className="absolute inset-x-0 top-0 h-48 bg-gradient-to-b from-[#140d0a] to-transparent"></div>
                    <div className="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPBlVzZSI+PHBhdGggZD0iTSAwIDEwIEwgNDAgMTAgTSAxMCAwIEwgMTAgNDAgTSAwIDIwIEwgNDAgMjAgTSAyMCAwIEwgMjAgNDAgTSAwIDMwIEwgNDAgMzAgTSAzMCAwIEwgMzAgNDAiIGZpbGw9Im5vbmUiIHN0cm9rZT0icmdiYSgyNTUsMjU1LDI1NSwwLjAyNSkiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-45"></div>
                </div>

                <div className="relative z-10 w-full max-w-6xl">
                    <div className="grid items-center gap-8 lg:grid-cols-[minmax(0,1.05fr)_minmax(25rem,28rem)] lg:gap-10">
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
                                    Email access check
                                </div>
                                <h1 className="mt-5 text-[clamp(2.7rem,4vw,4.4rem)] font-bold leading-[0.95] tracking-[-0.04em] text-white">
                                    Verify your email,
                                    <span className="block text-[#f0d6c7]/78">unlock your workspace.</span>
                                </h1>
                                <p className="mt-4 max-w-md text-[15px] leading-7 text-[#cab8ad]">
                                    One quick verification keeps your BacklinkPro workspace secure and lets you continue into audits, reporting, and campaign management.
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

                        <section className="w-full max-w-[28rem] justify-self-center lg:justify-self-end">
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
                            </div>

                            <div className="mt-6 rounded-[30px] border border-[#ffffff12] bg-[linear-gradient(180deg,rgba(26,18,16,0.92),rgba(12,10,10,0.96))] p-6 shadow-[0_30px_80px_rgba(0,0,0,0.46)] backdrop-blur-xl sm:p-7">
                                <div className="text-center">
                                    <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full border border-[#ff946d]/30 bg-[radial-gradient(circle_at_top,#ff9a73,#ff6e40_56%,#47241a)] shadow-[0_20px_45px_rgba(255,110,64,0.22)]">
                                        <svg className="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.8} d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <h2 className="mt-5 text-[2rem] font-bold tracking-tight text-white">
                                        Verify your email
                                    </h2>
                                    <p className="mt-2 text-sm leading-6 text-[#c9b9ae]">
                                        We emailed you a verification link. Confirm your address to unlock the rest of your BacklinkPro workspace.
                                    </p>
                                </div>

                                <div className="mt-6 space-y-4">
                                    {status === 'verification-link-sent' ? (
                                        <div className="rounded-2xl border border-[#12B76A]/30 bg-[#12B76A]/10 p-4">
                                            <p className="text-sm font-medium text-[#6ee7a8]">
                                                A fresh verification link has been sent to your email address.
                                            </p>
                                        </div>
                                    ) : null}

                                    {status === 'email-verified' ? (
                                        <div className="rounded-2xl border border-[#12B76A]/30 bg-[#12B76A]/10 p-4">
                                            <p className="text-sm font-medium text-[#6ee7a8]">
                                                Your email is verified. You can now use the full workspace.
                                            </p>
                                        </div>
                                    ) : null}

                                    <div className="rounded-[24px] border border-[#ff875c]/18 bg-[linear-gradient(180deg,rgba(255,122,69,0.1),rgba(255,122,69,0.04))] p-5">
                                        <div className="flex items-start gap-3">
                                            <div className="mt-0.5 flex h-8 w-8 items-center justify-center rounded-full bg-[#ff7b45]/15 text-[#ffb08f]">
                                                <svg className="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.981-1.742 2.981H4.42c-1.53 0-2.492-1.647-1.743-2.98l5.58-9.921zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-6a1 1 0 00-1 1v3a1 1 0 102 0V8a1 1 0 00-1-1z" clipRule="evenodd" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p className="text-sm font-semibold uppercase tracking-[0.12em] text-[#ffb08f]">
                                                    Verification required
                                                </p>
                                                <p className="mt-2 text-sm leading-6 text-[#e2d5cd]">
                                                    Please open your inbox and click the verification link before continuing into all account features.
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <p className="text-center text-sm text-[#b9a79b]">
                                        Didn&apos;t receive the email? We&apos;ll send another one now.
                                    </p>

                                    <form onSubmit={submit}>
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
                                                        Sending...
                                                    </>
                                                ) : (
                                                    <>
                                                        Resend Verification Email
                                                        <svg className="h-5 w-5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                                        </svg>
                                                    </>
                                                )}
                                            </span>
                                            <div className="absolute inset-0 translate-x-[-100%] bg-gradient-to-r from-[#ffffff00] via-[#ffffff7a] to-[#ffffff00] transition-transform duration-700 group-hover:translate-x-[100%]"></div>
                                        </button>
                                    </form>

                                    <div className="text-center">
                                        <Link
                                            href="/logout"
                                            method="post"
                                            className="text-sm font-medium text-[#b9a79b] transition-colors hover:text-[#ffb08f]"
                                        >
                                            Sign out
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </>
    );
}
