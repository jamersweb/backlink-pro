import AppLayout from '../Components/Layout/AppLayout';
import React, { useState, useEffect } from 'react';
import { router, usePage } from '@inertiajs/react';

export default function AuditReport({
    googleConnected = false,
    googleEmail = null,
    recentAudits = [],
    canUseWhiteLabel = false,
}) {
    const { props } = usePage();
    const serverErrors = props.errors || {};

    const [formData, setFormData] = useState({
        url: '',
        email: '',
        include_white_label_data: false,
    });

    const [errors, setErrors] = useState({ url: '', email: '' });
    const [isLoading, setIsLoading] = useState(false);
    const [isConnected, setIsConnected] = useState(googleConnected);
    const [connectedEmail, setConnectedEmail] = useState(googleEmail);

    // Sync server validation errors into local state for display
    useEffect(() => {
        if (serverErrors.url || serverErrors.email) {
            setErrors({
                url: Array.isArray(serverErrors.url) ? serverErrors.url[0] : serverErrors.url || '',
                email: Array.isArray(serverErrors.email) ? serverErrors.email[0] : serverErrors.email || '',
            });
        }
    }, [serverErrors.url, serverErrors.email]);

    const validateUrl = (url) => {
        if (!url.trim()) return 'Website URL is required';
        const urlPattern = /^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/;
        if (!urlPattern.test(url)) return 'Please enter a valid URL (e.g., https://example.com)';
        return '';
    };

    const validateEmail = (email) => {
        if (email.trim()) {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(email)) return 'Please enter a valid email address';
        }
        return '';
    };

    const handleInputChange = (field, value) => {
        setFormData((prev) => ({ ...prev, [field]: value }));
        if (errors[field]) setErrors((prev) => ({ ...prev, [field]: '' }));
    };

    const validateForm = () => {
        const urlError = validateUrl(formData.url);
        const emailError = validateEmail(formData.email);
        setErrors({ url: urlError, email: emailError });
        return !urlError && !emailError;
    };

    const isFormValid = () => {
        const hasUrl = formData.url.trim() !== '';
        return hasUrl && !errors.url && !errors.email;
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (!validateForm()) return;
        setIsLoading(true);
        router.post('/audit-report', {
            url: formData.url.trim(),
            email: formData.email.trim() || null,
            send_to_email: Boolean(formData.email.trim()),
            include_white_label_data: formData.include_white_label_data,
        }, {
            preserveScroll: true,
            onFinish: () => setIsLoading(false),
            onError: (errs) => {
                setErrors({
                    url: Array.isArray(errs?.url) ? errs.url[0] : errs?.url || '',
                    email: Array.isArray(errs?.email) ? errs.email[0] : errs?.email || '',
                });
            },
        });
    };

    const handleGoogleConnect = () => {
        window.location.href = '/google-seo/connect';
    };

    const handleGoogleDisconnect = async () => {
        if (!confirm('Are you sure you want to disconnect Google Analytics and Search Console?')) return;
        try {
            await fetch('/google-seo/disconnect', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });
            setIsConnected(false);
            setConnectedEmail(null);
        } catch (err) {
            console.error('Error disconnecting Google:', err);
        }
    };

    return (
        <AppLayout header="Audit Report">
            <div className="bp-audit-report-page space-y-6 max-w-7xl mx-auto">
                <div className="bp-audit-header-card bp-card relative overflow-hidden">
                    <div className="relative flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                        <div>
                            <h2 className="mb-2">Audit Report</h2>
                            <p className="bp-text-muted">
                                Enter website details to generate an SEO audit report. You will be taken to the report page when ready.
                            </p>
                        </div>
                        <button
                            type="button"
                            onClick={handleSubmit}
                            disabled={!isFormValid() || isLoading}
                            className={`px-6 py-3 rounded-xl font-medium transition-all duration-200 flex items-center justify-center gap-2 h-11 min-h-[44px] ${
                                isFormValid() && !isLoading
                                    ? 'bp-btn-purple'
                                    : 'bg-[var(--admin-surface-3)] text-[var(--admin-text-dim)] cursor-not-allowed'
                            }`}
                        >
                            {isLoading ? (
                                <>
                                    <i className="bi bi-arrow-repeat animate-spin"></i>
                                    Running audit…
                                </>
                            ) : (
                                <>
                                    <i className="bi bi-play-circle"></i>
                                    Run Audit
                                </>
                            )}
                        </button>
                    </div>
                </div>

                {isLoading && (
                    <div className="bp-card rounded-2xl p-5 border border-[rgba(242,140,56,0.30)] bg-[rgba(242,140,56,0.06)]">
                        <div className="flex items-center gap-3">
                            <i className="bi bi-arrow-repeat animate-spin text-[var(--admin-primary-light)] text-lg"></i>
                            <span className="font-medium text-[var(--admin-primary-light)]">
                                Running audit… This may take up to a minute. Do not close this page.
                            </span>
                        </div>
                    </div>
                )}

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div className="lg:col-span-2">
                        <div className="bp-form-card overflow-hidden rounded-2xl p-6">
                            <form onSubmit={handleSubmit} className="space-y-6">
                                <div>
                                    <label className="bp-form-label block text-sm mb-2">
                                        Website URL <span className="text-[#F04438]">*</span>
                                    </label>
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                                            <i className="bi bi-globe text-[var(--admin-text-dim)]"></i>
                                        </div>
                                        <input
                                            type="text"
                                            value={formData.url}
                                            onChange={(e) => handleInputChange('url', e.target.value)}
                                            onBlur={() => setErrors((prev) => ({ ...prev, url: validateUrl(formData.url) }))}
                                            placeholder="https://example.com"
                                            className={`bp-input w-full pl-11 pr-4 py-3 ${
                                                errors.url ? 'border-[#F04438]' : ''
                                            } rounded-xl text-[var(--admin-text)] placeholder-[var(--admin-text-dim)] transition-colors`}
                                        />
                                    </div>
                                    {errors.url && (
                                        <p className="mt-2 text-sm text-[#F04438] flex items-center gap-1">
                                            <i className="bi bi-exclamation-circle"></i>
                                            {errors.url}
                                        </p>
                                    )}
                                    <p className="bp-form-helper mt-2 flex items-center gap-1">
                                        <i className="bi bi-info-circle"></i>
                                        We'll analyze your page and create an audit report.
                                    </p>
                                </div>

                                <div>
                                    <label className="bp-form-label block text-sm mb-2">
                                        Email
                                    </label>
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                                            <i className="bi bi-envelope text-[var(--admin-text-dim)]"></i>
                                        </div>
                                        <input
                                            type="email"
                                            value={formData.email}
                                            onChange={(e) => handleInputChange('email', e.target.value)}
                                            onBlur={() => setErrors((prev) => ({ ...prev, email: validateEmail(formData.email) }))}
                                            placeholder="name@email.com"
                                            className={`bp-input w-full pl-11 pr-4 py-3 ${
                                                errors.email ? 'border-[#F04438]' : ''
                                            } rounded-xl text-[var(--admin-text)] placeholder-[var(--admin-text-dim)] transition-colors`}
                                        />
                                    </div>
                                    {errors.email && (
                                        <p className="mt-2 text-sm text-[#F04438] flex items-center gap-1">
                                            <i className="bi bi-exclamation-circle"></i>
                                            {errors.email}
                                        </p>
                                    )}
                                    <p className="bp-form-helper mt-2 flex items-center gap-1">
                                        <i className="bi bi-info-circle"></i>
                                        Add an email if you want the completed audit sent to your inbox automatically.
                                    </p>
                                </div>

                                <div className="bp-checkbox-info-card flex items-start gap-3">
                                    <input
                                        type="checkbox"
                                        id="include-white-label-data"
                                        checked={formData.include_white_label_data}
                                        onChange={(e) => handleInputChange('include_white_label_data', e.target.checked)}
                                        disabled={!canUseWhiteLabel}
                                        className="mt-0.5 w-4 h-4 rounded border-[var(--admin-border)] bg-[var(--admin-bg)] focus:ring-[var(--admin-primary)] focus:ring-offset-0 cursor-pointer disabled:cursor-not-allowed disabled:opacity-50"
                                    />
                                    <label htmlFor="include-white-label-data" className={`flex-1 text-sm ${canUseWhiteLabel ? 'text-[var(--admin-text)] cursor-pointer' : 'text-[var(--admin-text-dim)] cursor-not-allowed'}`}>
                                        <span className="font-medium">Do you want this report with white-label data?</span>
                                        <p className="bp-form-helper mt-1">
                                            {canUseWhiteLabel
                                                ? 'Use your saved Label branding and white-label report details where available.'
                                                : 'White-label data becomes available once Label settings are configured on a supported plan.'}
                                        </p>
                                    </label>
                                </div>

                                <div className="space-y-3">
                                    <label className="bp-form-label block text-sm">
                                        Google Integrations <span className="text-xs text-[var(--admin-text-dim)] font-normal ml-1">(Optional)</span>
                                    </label>
                                    {isConnected ? (
                                        <div className="p-4 bg-[#12B76A]/10 border border-[#12B76A]/30 rounded-lg">
                                            <div className="flex items-start justify-between gap-3">
                                                <div className="flex items-start gap-3 flex-1">
                                                    <div className="mt-0.5 w-8 h-8 rounded-full bg-[#12B76A]/20 flex items-center justify-center flex-shrink-0">
                                                        <i className="bi bi-check-circle-fill text-[#12B76A]"></i>
                                                    </div>
                                                    <div>
                                                        <p className="text-sm font-medium text-[#12B76A] mb-1">Google Connected</p>
                                                        <p className="text-xs text-[var(--admin-text-dim)]">
                                                            Analytics & Search Console: <span className="font-medium text-[var(--admin-text)]">{connectedEmail}</span>
                                                        </p>
                                                    </div>
                                                </div>
                                                <button
                                                    type="button"
                                                    onClick={handleGoogleDisconnect}
                                                    className="text-xs text-[#F04438] hover:text-[#D92D20] font-medium transition-colors"
                                                >
                                                    Disconnect
                                                </button>
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <button
                                                type="button"
                                                onClick={handleGoogleConnect}
                                                className="bp-integration-tile flex items-center gap-3 p-4 group"
                                            >
                                                <div className="bp-integration-icon bg-gradient-to-br from-[#3B82F6]/20 to-[#3B82F6]/08 flex items-center justify-center flex-shrink-0">
                                                    <i className="bi bi-graph-up text-[#60a5fa]"></i>
                                                </div>
                                                <div className="flex-1 text-left">
                                                    <p className="text-sm font-medium text-[var(--admin-text)] group-hover:text-[#60a5fa] transition-colors">Google Analytics</p>
                                                    <p className="text-xs text-[var(--admin-text-dim)]">Connect GA4</p>
                                                </div>
                                                <i className="bi bi-arrow-right text-[var(--admin-text-dim)] group-hover:text-[#60a5fa] transition-colors"></i>
                                            </button>
                                            <button
                                                type="button"
                                                onClick={handleGoogleConnect}
                                                className="bp-integration-tile flex items-center gap-3 p-4 group"
                                            >
                                                <div className="bp-integration-icon bg-gradient-to-br from-[#10B981]/20 to-[#10B981]/08 flex items-center justify-center flex-shrink-0">
                                                    <i className="bi bi-search text-[#34d399]"></i>
                                                </div>
                                                <div className="flex-1 text-left">
                                                    <p className="text-sm font-medium text-[var(--admin-text)] group-hover:text-[#34d399] transition-colors">Search Console</p>
                                                    <p className="text-xs text-[var(--admin-text-dim)]">Connect GSC</p>
                                                </div>
                                                <i className="bi bi-arrow-right text-[var(--admin-text-dim)] group-hover:text-[#34d399] transition-colors"></i>
                                            </button>
                                        </div>
                                    )}
                                    <p className="bp-form-helper flex items-center gap-1">
                                        <i className="bi bi-info-circle"></i>
                                        Connect to enrich your audit with traffic and search performance metrics.
                                    </p>
                                </div>

                                <div className="md:hidden">
                                    <button
                                        type="submit"
                                        disabled={!isFormValid() || isLoading}
                                        className={`w-full px-6 py-3 rounded-xl font-medium transition-all duration-200 flex items-center justify-center gap-2 h-11 min-h-[44px] ${
                                            isFormValid() && !isLoading ? 'bp-btn-purple' : 'bg-[var(--admin-surface-3)] text-[var(--admin-text-dim)] cursor-not-allowed'
                                        }`}
                                    >
                                        {isLoading ? (
                                            <><i className="bi bi-arrow-repeat animate-spin"></i> Running audit…</>
                                        ) : (
                                            <><i className="bi bi-play-circle"></i> Run Audit</>
                                        )}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div className="lg:col-span-1">
                        <div className="bp-what-you-get-card h-full overflow-hidden rounded-2xl p-6">
                            <div className="space-y-6">
                                <div className="flex items-center gap-3">
                                    <div className="w-10 h-10 rounded-lg bg-[rgba(242,140,56,0.15)] flex items-center justify-center">
                                        <i className="bi bi-lightbulb text-xl text-[var(--admin-primary-light)]"></i>
                                    </div>
                                    <h3 className="text-lg font-semibold text-[var(--admin-text)]">What you'll get</h3>
                                </div>
                                <div className="space-y-3">
                                    <div className="bp-what-you-get-item flex items-start gap-3 group">
                                        <div className="mt-1 w-6 h-6 rounded-full bg-[rgba(242,140,56,0.20)] flex items-center justify-center flex-shrink-0 group-hover:bg-[rgba(242,140,56,0.30)] transition-colors">
                                            <i className="bi bi-check2 text-[var(--admin-primary-light)] text-xs"></i>
                                        </div>
                                        <div>
                                            <p className="bp-item-title text-sm">On-page checks</p>
                                            <p className="bp-item-desc mt-0.5">Title tags, meta descriptions, headings, content</p>
                                        </div>
                                    </div>
                                    <div className="bp-what-you-get-item flex items-start gap-3 group">
                                        <div className="mt-1 w-6 h-6 rounded-full bg-[rgba(242,140,56,0.20)] flex items-center justify-center flex-shrink-0 group-hover:bg-[rgba(242,140,56,0.30)] transition-colors">
                                            <i className="bi bi-check2 text-[var(--admin-primary-light)] text-xs"></i>
                                        </div>
                                        <div>
                                            <p className="bp-item-title text-sm">Technical SEO</p>
                                            <p className="bp-item-desc mt-0.5">Speed, mobile, SSL, crawlability</p>
                                        </div>
                                    </div>
                                    <div className="bp-what-you-get-item flex items-start gap-3 group">
                                        <div className="mt-1 w-6 h-6 rounded-full bg-[rgba(242,140,56,0.20)] flex items-center justify-center flex-shrink-0 group-hover:bg-[rgba(242,140,56,0.30)] transition-colors">
                                            <i className="bi bi-check2 text-[var(--admin-primary-light)] text-xs"></i>
                                        </div>
                                        <div>
                                            <p className="bp-item-title text-sm">Performance summary</p>
                                            <p className="bp-item-desc mt-0.5">Overall score and recommendations</p>
                                        </div>
                                    </div>
                                </div>
                                <div className="border-t border-[var(--admin-border)]"></div>
                                <div>
                                    <p className="text-xs font-semibold text-[var(--admin-text-muted)] uppercase tracking-wider mb-3">Optional</p>
                                    <div className="flex flex-wrap gap-2">
                                        <span className="bp-optional-chip px-3 py-1.5 bg-[#3B82F6]/10 border border-[#3B82F6]/30 text-[#60a5fa] flex items-center gap-1.5">
                                            <i className="bi bi-graph-up"></i> GA4
                                        </span>
                                        <span className="bp-optional-chip px-3 py-1.5 bg-[#10B981]/10 border border-[#10B981]/30 text-[#34d399] flex items-center gap-1.5">
                                            <i className="bi bi-search"></i> GSC
                                        </span>
                                        <span className="bp-optional-chip px-3 py-1.5 bg-[#F79009]/10 border border-[#F79009]/30 text-[#fbbf24] flex items-center gap-1.5">
                                            <i className="bi bi-speedometer2"></i> PSI
                                        </span>
                                    </div>
                                </div>
                                {recentAudits && recentAudits.length > 0 && (
                                    <>
                                        <div className="border-t border-[var(--admin-border)]"></div>
                                        <div>
                                            <p className="text-xs font-semibold text-[var(--admin-text-muted)] uppercase tracking-wider mb-2">Recent audits</p>
                                            <ul className="space-y-1.5">
                                                {recentAudits.slice(0, 5).map((a) => (
                                                    <li key={a.id}>
                                                        <a
                                                            href={`/audit-report/${a.id}`}
                                                            className="text-sm text-[var(--admin-primary)] hover:underline truncate block"
                                                            title={a.url}
                                                        >
                                                            {a.url}
                                                        </a>
                                                    </li>
                                                ))}
                                            </ul>
                                        </div>
                                    </>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
