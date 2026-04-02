import { usePage } from '@inertiajs/react';

const providers = [
    {
        key: 'google',
        label: 'Google',
        accentClass: 'group-hover:border-[#ff875c]/55 group-hover:shadow-[#ff875c]/15',
        icon: (
            <svg viewBox="0 0 24 24" className="h-5 w-5" aria-hidden="true">
                <path fill="#EA4335" d="M12 10.2v3.9h5.5c-.2 1.2-.9 2.2-1.9 2.9l3.1 2.4c1.8-1.7 2.9-4.1 2.9-7 0-.7-.1-1.4-.2-2.1H12z" />
                <path fill="#34A853" d="M12 22c2.6 0 4.8-.9 6.4-2.5l-3.1-2.4c-.9.6-2 1-3.3 1-2.5 0-4.7-1.7-5.5-4H3.3v2.5C4.9 19.8 8.1 22 12 22z" />
                <path fill="#4A90E2" d="M6.5 14.1c-.2-.6-.3-1.3-.3-2.1s.1-1.4.3-2.1V7.4H3.3C2.5 8.9 2 10.4 2 12s.5 3.1 1.3 4.6l3.2-2.5z" />
                <path fill="#FBBC05" d="M12 5.9c1.4 0 2.7.5 3.7 1.5l2.8-2.8C16.8 3 14.6 2 12 2 8.1 2 4.9 4.2 3.3 7.4l3.2 2.5c.8-2.3 3-4 5.5-4z" />
            </svg>
        ),
    },
    {
        key: 'microsoft',
        label: 'Microsoft',
        accentClass: 'group-hover:border-[#ff875c]/55 group-hover:shadow-[#ff875c]/15',
        icon: (
            <svg viewBox="0 0 24 24" className="h-5 w-5" aria-hidden="true">
                <rect x="3" y="3" width="8" height="8" fill="#F25022" />
                <rect x="13" y="3" width="8" height="8" fill="#7FBA00" />
                <rect x="3" y="13" width="8" height="8" fill="#00A4EF" />
                <rect x="13" y="13" width="8" height="8" fill="#FFB900" />
            </svg>
        ),
    },
    {
        key: 'github',
        label: 'GitHub',
        accentClass: 'group-hover:border-[#ff875c]/50 group-hover:shadow-[#ff875c]/15',
        icon: (
            <svg viewBox="0 0 24 24" className="h-5 w-5" aria-hidden="true" fill="currentColor">
                <path d="M12 2C6.48 2 2 6.58 2 12.22c0 4.5 2.87 8.31 6.84 9.66.5.1.68-.22.68-.49 0-.24-.01-1.04-.01-1.89-2.78.62-3.37-1.21-3.37-1.21-.45-1.18-1.1-1.49-1.1-1.49-.9-.63.07-.62.07-.62 1 .07 1.52 1.04 1.52 1.04.89 1.56 2.33 1.11 2.9.85.09-.66.35-1.11.63-1.37-2.22-.26-4.56-1.14-4.56-5.08 0-1.12.39-2.04 1.03-2.76-.1-.26-.45-1.3.1-2.7 0 0 .84-.28 2.75 1.05A9.3 9.3 0 0112 6.7c.85 0 1.71.12 2.51.35 1.91-1.33 2.75-1.05 2.75-1.05.55 1.4.2 2.44.1 2.7.64.72 1.03 1.64 1.03 2.76 0 3.95-2.34 4.81-4.57 5.07.36.32.67.93.67 1.88 0 1.35-.01 2.43-.01 2.76 0 .27.18.59.69.49A10.23 10.23 0 0022 12.22C22 6.58 17.52 2 12 2z" />
            </svg>
        ),
    },
    {
        key: 'apple',
        label: 'Apple',
        accentClass: 'group-hover:border-[#ff875c]/50 group-hover:shadow-[#ff875c]/15',
        icon: (
            <svg viewBox="0 0 24 24" className="h-5 w-5" aria-hidden="true" fill="currentColor">
                <path d="M16.37 12.37c.02 2.29 2 3.05 2.02 3.06-.02.05-.31 1.1-1.01 2.18-.61.93-1.24 1.86-2.24 1.88-.98.02-1.3-.6-2.42-.6-1.13 0-1.49.58-2.4.62-.96.04-1.7-.98-2.31-1.91C6.73 16.3 5.73 14 5.75 11.8c.01-2.03 1.31-3.93 3.25-3.97.9-.02 1.75.63 2.42.63.66 0 1.9-.77 3.2-.66.55.02 2.1.23 3.1 1.72-.08.05-1.85 1.1-1.83 2.85zM13.98 6.58c.5-.63.84-1.5.75-2.38-.72.03-1.6.5-2.11 1.12-.46.56-.86 1.46-.75 2.31.8.06 1.61-.42 2.11-1.05z" />
            </svg>
        ),
    },
    {
        key: 'facebook',
        label: 'Facebook',
        accentClass: 'group-hover:border-[#ff875c]/55 group-hover:shadow-[#ff875c]/15',
        icon: (
            <svg viewBox="0 0 24 24" className="h-5 w-5" aria-hidden="true" fill="#1877F2">
                <path d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07c0 6.03 4.39 11.02 10.12 11.93v-8.44H7.08v-3.49h3.04V9.41c0-3.02 1.79-4.69 4.53-4.69 1.31 0 2.68.24 2.68.24v2.97h-1.51c-1.49 0-1.95.93-1.95 1.88v2.26h3.33l-.53 3.49h-2.8V24C19.61 23.09 24 18.1 24 12.07z" />
            </svg>
        ),
    },
];

function buildAuthRedirectUrl(provider, currentUrl) {
    let url;

    try {
        url = new URL(currentUrl);
    } catch (error) {
        url = new URL(window.location.href);
    }

    const params = new URLSearchParams();

    ['intended', 'redirect', 'next'].forEach((key) => {
        const value = url.searchParams.get(key);
        if (value) {
            params.set(key, value);
        }
    });

    const query = params.toString();

    return `/auth/${provider}/redirect${query ? `?${query}` : ''}`;
}

export default function SocialLoginButtons({ className = '' }) {
    const { oauthProviders = {}, currentUrl = '' } = usePage().props;

    return (
        <div className={className}>
            <div className="flex flex-wrap items-center justify-center gap-3">
                {providers.map((provider) => {
                    const isConfigured = Boolean(oauthProviders[provider.key]);

                    return (
                        <a
                            key={provider.key}
                            href={buildAuthRedirectUrl(provider.key, currentUrl)}
                            aria-label={`Continue with ${provider.label}`}
                            title={provider.label}
                            className={`group inline-flex h-14 w-14 items-center justify-center rounded-full border text-white transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_32px_rgba(0,0,0,0.28)] ${provider.accentClass} ${
                                isConfigured
                                    ? 'border-[#ffffff14] bg-[linear-gradient(180deg,rgba(255,255,255,0.06),rgba(255,255,255,0.025))]'
                                    : 'border-[#ff946d]/16 bg-[linear-gradient(180deg,rgba(255,148,109,0.08),rgba(255,255,255,0.02))] opacity-85'
                            }`}
                        >
                            <span
                                className={`inline-flex h-10 w-10 items-center justify-center rounded-full border ${
                                    isConfigured
                                        ? 'border-[#ffffff12] bg-[rgba(0,0,0,0.22)]'
                                        : 'border-[#ff946d]/18 bg-[rgba(0,0,0,0.18)]'
                                }`}
                            >
                                {provider.icon}
                            </span>
                        </a>
                    );
                })}
            </div>
        </div>
    );
}
