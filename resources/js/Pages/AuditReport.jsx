import AppLayout from '../Components/Layout/AppLayout';
import Card from '../Components/Shared/Card';
import React, { useState, useEffect, useRef } from 'react';

const STORAGE_KEY = 'backlinkpro_last_audit_id';

export default function AuditReport({ googleConnected = false, googleEmail = null, recentAudits = [], lastCompletedAuditId = null }) {
    const [formData, setFormData] = useState({
        url: '',
        email: '',
        send_to_email: true,
    });
    
    const [errors, setErrors] = useState({
        url: '',
        email: '',
    });
    
    const [isLoading, setIsLoading] = useState(false);
    const [status, setStatus] = useState('');
    const [currentAuditId, setCurrentAuditId] = useState(null);
    const [auditProgress, setAuditProgress] = useState(0);
    const [isConnected, setIsConnected] = useState(googleConnected);
    const [connectedEmail, setConnectedEmail] = useState(googleEmail);
    const [reportData, setReportData] = useState(null);
    const [auditStatus, setAuditStatus] = useState('idle'); // idle|running|completed|failed

    // Validate URL format
    const validateUrl = (url) => {
        if (!url.trim()) {
            return 'Website URL is required';
        }
        
        const urlPattern = /^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/;
        if (!urlPattern.test(url)) {
            return 'Please enter a valid URL (e.g., https://example.com)';
        }
        
        return '';
    };

    // Validate email format
    const validateEmail = (email) => {
        // Email is only required if send_to_email is checked
        if (formData.send_to_email && !email.trim()) {
            return 'Email is required when "Send report to email" is checked';
        }
        
        if (email.trim()) {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(email)) {
                return 'Please enter a valid email address';
            }
        }
        
        return '';
    };

    // Handle input change
    const handleInputChange = (field, value) => {
        setFormData(prev => ({ ...prev, [field]: value }));
        
        // Clear error when user starts typing
        if (errors[field]) {
            setErrors(prev => ({ ...prev, [field]: '' }));
        }
    };

    // Handle checkbox change
    const handleCheckboxChange = (checked) => {
        setFormData(prev => ({ ...prev, send_to_email: checked }));
        
        // Re-validate email if checkbox state changes
        if (!checked) {
            setErrors(prev => ({ ...prev, email: '' }));
        }
    };

    // Validate all fields
    const validateForm = () => {
        const urlError = validateUrl(formData.url);
        const emailError = validateEmail(formData.email);
        
        setErrors({
            url: urlError,
            email: emailError,
        });
        
        return !urlError && !emailError;
    };

    // Check if form is valid (for button enable/disable)
    const isFormValid = () => {
        const hasUrl = formData.url.trim() !== '';
        const hasEmail = formData.send_to_email ? formData.email.trim() !== '' : true;
        const noErrors = !errors.url && !errors.email;
        
        return hasUrl && hasEmail && noErrors;
    };

    const stageLabels = {
        starting: 'Starting audit...',
        onpage: 'Analyzing on-page SEO...',
        psi: 'Running PageSpeed Insights...',
        ga4: 'Fetching Google Analytics data...',
        gsc: 'Fetching Search Console data...',
        compiling: 'Compiling report...',
        completed: 'Audit completed!',
    };

    // Track when audit was queued (for stuck detection)
    const [queuedAt, setQueuedAt] = useState(null);
    const [isExportingPdf, setIsExportingPdf] = useState(false);

    // On mount: load last completed audit only when idle (abort if user runs new audit)
    const initialLoadRef = useRef(null);
    useEffect(() => {
        if (auditStatus !== 'idle' || reportData) return;
        const stored = (typeof window !== 'undefined' && localStorage.getItem(STORAGE_KEY)) || lastCompletedAuditId;
        if (stored) {
            const ctrl = new AbortController();
            initialLoadRef.current = ctrl;
            setStatus('Loading report...');
            fetch(`/audit-report/${stored}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '' },
                signal: ctrl.signal,
            })
                .then(r => r.ok ? r.json() : Promise.reject(new Error('Failed')))
                .then(data => {
                    const audit = data.audit || data;
                    if (initialLoadRef.current === ctrl) {
                        setReportData(audit);
                        setCurrentAuditId(audit?.id ?? null);
                        setAuditStatus('completed');
                        setStatus('');
                        if (audit?.id) try { localStorage.setItem(STORAGE_KEY, String(audit.id)); } catch (_) {}
                    }
                })
                .catch(e => { if (e?.name !== 'AbortError' && initialLoadRef.current === ctrl) setStatus(''); });
            return () => { ctrl.abort(); initialLoadRef.current = null; };
        }
    }, []);

    // Poll audit status
    useEffect(() => {
        if (!currentAuditId || auditStatus === 'completed') return;

        const pollInterval = setInterval(async () => {
            try {
                const response = await fetch(`/audit-report/${currentAuditId}/status`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    cache: 'no-store',
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                
                const data = await response.json();
                const stage = data.progress_stage || '';
                const stageLabel = stageLabels[stage] || `Processing (${stage})...`;
                const queuedSeconds = queuedAt ? Math.floor((Date.now() - queuedAt) / 1000) : (data.created_at ? Math.floor((Date.now() - new Date(data.created_at).getTime()) / 1000) : 0);
                const isStuckQueued = data.status === 'queued' && queuedSeconds >= 120;
                
                if (data.status === 'completed') {
                    clearInterval(pollInterval);
                    setAuditProgress(100);
                    setStatus('Audit completed successfully!');
                    fetchReportData(currentAuditId);
                } else if (data.status === 'failed') {
                    clearInterval(pollInterval);
                    setStatus(`Error: ${data.error || 'Audit failed'}`);
                    setAuditStatus('failed');
                    setIsLoading(false);
                } else if (data.status === 'running') {
                    setAuditProgress(data.progress_percent || 50);
                    setStatus(stageLabel);
                } else {
                    setAuditProgress(data.progress_percent || 5);
                    setStatus(isStuckQueued
                        ? 'Starting… (worker may not be running — audits will auto-fail after 5 min)'
                        : 'Queued - waiting to start...');
                }
            } catch (error) {
                console.error('Error polling audit status:', error);
            }
        }, 2000);

        return () => clearInterval(pollInterval);
    }, [currentAuditId, auditStatus, queuedAt]);

    // Fetch full report data
    const fetchReportData = async (auditId) => {
        try {
            const response = await fetch(`/audit-report/${auditId}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const data = await response.json();
            const audit = data.audit || data;
            setReportData(audit);
            setCurrentAuditId(audit?.id ?? null);
            setAuditStatus('completed');
            setIsLoading(false);
            if (audit?.id) {
                try { localStorage.setItem(STORAGE_KEY, String(audit.id)); } catch (_) {}
            }
        } catch (error) {
            console.error('Error fetching report:', error);
            setStatus('Error: Failed to load report');
            setAuditStatus('failed');
            setIsLoading(false);
            setCurrentAuditId(null);
            try { localStorage.removeItem(STORAGE_KEY); } catch (_) {}
        }
    };

    // Handle Google OAuth
    const handleGoogleConnect = () => {
        window.location.href = '/google-seo/connect';
    };

    const handleGoogleDisconnect = async () => {
        if (!confirm('Are you sure you want to disconnect Google Analytics and Search Console?')) {
            return;
        }

        try {
            await fetch('/google-seo/disconnect', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });
            
            setIsConnected(false);
            setConnectedEmail(null);
        } catch (error) {
            console.error('Error disconnecting Google:', error);
        }
    };

    // Handle form submission
    const handleSubmit = async (e) => {
        e.preventDefault();
        
        if (!validateForm()) {
            return;
        }
        
        if (initialLoadRef.current) {
            initialLoadRef.current.abort();
            initialLoadRef.current = null;
        }
        setIsLoading(true);
        setAuditStatus('running');
        setStatus('Starting audit... This may take 30-60 seconds.');
        setAuditProgress(0);
        setErrors({ url: '', email: '' });
        
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            if (!csrfToken) {
                throw new Error('CSRF token not found');
            }

            console.log('Submitting audit:', formData);

            // Create abort controller for timeout handling
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 120000); // 120 second timeout

            const response = await fetch('/audit-report', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(formData),
                credentials: 'same-origin',
                signal: controller.signal,
            });
            
            clearTimeout(timeoutId);
            console.log('Response status:', response.status);
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                console.error('Response error:', errorData);
                
                // Check for validation errors
                if (response.status === 422 && errorData.errors) {
                    const validationErrors = {};
                    Object.keys(errorData.errors).forEach(key => {
                        validationErrors[key] = errorData.errors[key][0];
                    });
                    setErrors(validationErrors);
                    throw new Error('Please fix the validation errors');
                }
                
                throw new Error(errorData.message || `HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            console.log('Response data:', data);
            
            if (data.success && data.audit_id) {
                setCurrentAuditId(data.audit_id);
                setQueuedAt(Date.now());
                
                // Check if already completed (sync queue)
                if (data.status === 'completed') {
                    setStatus('Audit completed! Loading report...');
                    setAuditProgress(100);
                    // Fetch report immediately
                    setTimeout(() => fetchReportData(data.audit_id), 500);
                } else {
                    // Will be completed via polling
                    setStatus('Queued - waiting to start...');
                    setAuditProgress(5);
                }
            } else if (data.audit_id) {
                setCurrentAuditId(data.audit_id);
                setQueuedAt(Date.now());
                setStatus('Queued - waiting to start...');
                setAuditProgress(5);
            } else {
                throw new Error(data.message || 'Failed to create audit');
            }
        } catch (error) {
            console.error('Error submitting audit:', error);
            
            if (error.name === 'AbortError') {
                setStatus('Error: Request timeout - audit is taking too long. Please try again.');
            } else {
                setStatus(`Error: ${error.message || 'Failed to queue audit'}`);
            }
            
            setAuditStatus('failed');
            setIsLoading(false);
        }
    };

    // Handle Run New Audit - clear report and show form
    const handleRerun = () => {
        setReportData(null);
        setAuditStatus('idle');
        setCurrentAuditId(null);
        setQueuedAt(null);
        setStatus('');
        setAuditProgress(0);
        setIsLoading(false);
        setFormData({ url: '', email: '', send_to_email: true });
        try { localStorage.removeItem(STORAGE_KEY); } catch (_) {}
    };

    // Handle Export PDF - fetch from server and download
    const handleExportPDF = async () => {
        const auditId = currentAuditId || reportData?.id;
        if (!auditId) return;
        setIsExportingPdf(true);
        try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch(`/audit-report/${auditId}/export-pdf`, {
                method: 'POST',
                headers: { 'Accept': 'application/pdf', 'X-CSRF-TOKEN': csrf || '', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) throw new Error('Export failed');
            const blob = await res.blob();
            const isPdf = res.headers.get('Content-Type')?.includes('pdf');
            if (isPdf) {
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `audit-report-${auditId}-${new Date().toISOString().slice(0,10)}.pdf`;
                a.click();
                URL.revokeObjectURL(url);
            } else {
                const text = await blob.text();
                const w = window.open('', '_blank');
                w.document.write(text);
                w.document.close();
            }
        } catch (e) {
            console.error(e);
            window.print();
        } finally {
            setIsExportingPdf(false);
        }
    };

    return (
        <AppLayout header="Audit Report">
            <div className="bp-audit-report-page space-y-6 max-w-7xl mx-auto">
                {/* Header Card */}
                <div className="bp-audit-header-card bp-card relative overflow-hidden">
                    <div className="relative flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                        <div>
                            <h2 className="mb-2">Audit Report</h2>
                            <p className="bp-text-muted">
                                {auditStatus === 'completed' 
                                    ? `Report for ${reportData?.url || 'your website'}`
                                    : 'Enter website details to generate an SEO audit report.'
                                }
                            </p>
                        </div>
                        
                        {auditStatus === 'completed' ? (
                            <div className="flex gap-3 items-center">
                                <button
                                    onClick={handleRerun}
                                    className="text-sm text-[var(--admin-text-dim)] hover:text-[var(--admin-text)] transition-colors flex items-center gap-1.5"
                                >
                                    <i className="bi bi-arrow-clockwise"></i>
                                    Run New Audit
                                </button>
                                <button
                                    onClick={handleExportPDF}
                                    disabled={isExportingPdf}
                                    className="bp-btn-purple px-6 py-2.5 rounded-xl font-medium transition-all duration-200 flex items-center gap-2 h-11 min-h-[44px]"
                                >
                                    {isExportingPdf ? (
                                        <><i className="bi bi-arrow-repeat animate-spin"></i> Generating PDF…</>
                                    ) : (
                                        <><i className="bi bi-download"></i> Export PDF</>
                                    )}
                                </button>
                            </div>
                        ) : (
                            <button
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
                                        Running...
                                    </>
                                ) : (
                                    <>
                                        <i className="bi bi-play-circle"></i>
                                        Run Audit
                                    </>
                                )}
                            </button>
                        )}
                    </div>
                </div>

                {/* Status Message with Progress - hide when completed */}
                {status && auditStatus !== 'completed' && (
                    <div className={`bp-card rounded-2xl p-5 border ${
                        status.includes('completed') || status.includes('successfully')
                            ? 'border-[#12B76A]/30'
                            : status.includes('Error') || status.includes('failed')
                                ? 'border-[#F04438]/30'
                                : 'border-[#7C3AED]/30'
                    }`}>
                        <div className="flex items-center gap-3 mb-3">
                            <i className={`bi ${
                                status.includes('completed') || status.includes('successfully') ? 'bi-check-circle-fill text-[#12B76A]' :
                                status.includes('Error') || status.includes('failed') ? 'bi-x-circle-fill text-[#F04438]' :
                                'bi-arrow-repeat animate-spin text-[#A78BFA]'
                            } text-lg`}></i>
                            <span className={`font-medium ${
                                status.includes('completed') || status.includes('successfully') ? 'text-[#12B76A]' :
                                status.includes('Error') || status.includes('failed') ? 'text-[#F04438]' :
                                'text-[#A78BFA]'
                            }`}>{status}</span>
                        </div>
                        
                        {isLoading && auditProgress > 0 && (
                            <div className="space-y-2">
                                <div className="flex justify-between text-xs text-[var(--admin-text-dim)]">
                                    <span>Progress</span>
                                    <span>{auditProgress}%</span>
                                </div>
                                <div className="w-full h-2.5 bg-[var(--admin-surface-2)] rounded-full overflow-hidden">
                                    <div 
                                        className="h-full rounded-full transition-all duration-700 ease-out"
                                        style={{ width: `${auditProgress}%`, background: 'linear-gradient(90deg, #7C3AED, #A78BFA)' }}
                                    ></div>
                                </div>
                            </div>
                        )}
                    </div>
                )}

                {/* Main Content - Form or Report */}
                {auditStatus === 'completed' && reportData ? (
                    // REPORT VIEW (INLINE)
                    <div id="audit-report-content" className="space-y-6">
                        {renderReport()}
                    </div>
                ) : (
                    // FORM VIEW
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Form Card - Takes 2 columns on desktop */}
                        <div className="lg:col-span-2">
                            <div className="bp-form-card overflow-hidden rounded-2xl p-6">
                                <form onSubmit={handleSubmit} className="space-y-6">
                                {/* Website URL Field */}
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
                                            onBlur={() => setErrors(prev => ({ ...prev, url: validateUrl(formData.url) }))}
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
                                        We'll crawl your website and create an audit report.
                                    </p>
                                </div>

                                {/* Email Field */}
                                <div>
                                    <label className="bp-form-label block text-sm mb-2">
                                        Email {formData.send_to_email && <span className="text-[#F04438]">*</span>}
                                    </label>
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                                            <i className="bi bi-envelope text-[var(--admin-text-dim)]"></i>
                                        </div>
                                        <input
                                            type="email"
                                            value={formData.email}
                                            onChange={(e) => handleInputChange('email', e.target.value)}
                                            onBlur={() => setErrors(prev => ({ ...prev, email: validateEmail(formData.email) }))}
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
                                </div>

                                {/* Checkbox */}
                                <div className="bp-checkbox-info-card flex items-start gap-3">
                                    <input
                                        type="checkbox"
                                        id="send-email"
                                        checked={formData.send_to_email}
                                        onChange={(e) => handleCheckboxChange(e.target.checked)}
                                        className="mt-0.5 w-4 h-4 rounded border-[var(--admin-border)] bg-[var(--admin-bg)] focus:ring-[#7C3AED] focus:ring-offset-0 cursor-pointer"
                                    />
                                    <label htmlFor="send-email" className="flex-1 text-sm text-[var(--admin-text)] cursor-pointer">
                                        <span className="font-medium">Send report to email</span>
                                        <p className="bp-form-helper mt-1">
                                            Receive the audit report in your email inbox once completed.
                                        </p>
                                    </label>
                                </div>

                                {/* Google Integrations - Moved under checkbox */}
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
                                                            Analytics & Search Console connected: <span className="font-medium text-[var(--admin-text)]">{connectedEmail}</span>
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
                                        Connect to enrich your audit with traffic data and search performance metrics
                                    </p>
                                </div>

                                {/* Submit Button (Mobile) */}
                                <div className="md:hidden">
                                    <button
                                        type="submit"
                                        disabled={!isFormValid() || isLoading}
                                        className={`w-full px-6 py-3 rounded-xl font-medium transition-all duration-200 flex items-center justify-center gap-2 h-11 min-h-[44px] ${
                                            isFormValid() && !isLoading
                                                ? 'bp-btn-purple'
                                                : 'bg-[var(--admin-surface-3)] text-[var(--admin-text-dim)] cursor-not-allowed'
                                        }`}
                                    >
                                        {isLoading ? (
                                            <>
                                                <i className="bi bi-arrow-repeat animate-spin"></i>
                                                Running Audit...
                                            </>
                                        ) : (
                                            <>
                                                <i className="bi bi-play-circle"></i>
                                                Run Audit
                                            </>
                                        )}
                                    </button>
                                </div>
                                </form>
                            </div>
                        </div>

                        {/* Info Side Card - Takes 1 column on desktop */}
                        <div className="lg:col-span-1">
                            <div className="bp-what-you-get-card h-full overflow-hidden rounded-2xl p-6">
                            <div className="space-y-6">
                                {/* Title */}
                                <div className="flex items-center gap-3">
                                    <div className="w-10 h-10 rounded-lg bg-[#7C3AED]/15 flex items-center justify-center">
                                        <i className="bi bi-lightbulb text-xl text-[#A78BFA]"></i>
                                    </div>
                                    <h3 className="text-lg font-semibold text-[var(--admin-text)]">What you'll get</h3>
                                </div>

                                {/* Feature List */}
                                <div className="space-y-3">
                                    <div className="bp-what-you-get-item flex items-start gap-3 group">
                                        <div className="mt-1 w-6 h-6 rounded-full bg-[#7C3AED]/20 flex items-center justify-center flex-shrink-0 group-hover:bg-[#7C3AED]/30 transition-colors">
                                            <i className="bi bi-check2 text-[#A78BFA] text-xs"></i>
                                        </div>
                                        <div>
                                            <p className="bp-item-title text-sm">On-page checks</p>
                                            <p className="bp-item-desc mt-0.5">Title tags, meta descriptions, headings, and content analysis</p>
                                        </div>
                                    </div>

                                    <div className="bp-what-you-get-item flex items-start gap-3 group">
                                        <div className="mt-1 w-6 h-6 rounded-full bg-[#7C3AED]/20 flex items-center justify-center flex-shrink-0 group-hover:bg-[#7C3AED]/30 transition-colors">
                                            <i className="bi bi-check2 text-[#A78BFA] text-xs"></i>
                                        </div>
                                        <div>
                                            <p className="bp-item-title text-sm">Off-page signals</p>
                                            <p className="bp-item-desc mt-0.5">Backlink profile, domain authority, and external factors</p>
                                        </div>
                                    </div>

                                    <div className="bp-what-you-get-item flex items-start gap-3 group">
                                        <div className="mt-1 w-6 h-6 rounded-full bg-[#7C3AED]/20 flex items-center justify-center flex-shrink-0 group-hover:bg-[#7C3AED]/30 transition-colors">
                                            <i className="bi bi-check2 text-[#A78BFA] text-xs"></i>
                                        </div>
                                        <div>
                                            <p className="bp-item-title text-sm">Technical SEO</p>
                                            <p className="bp-item-desc mt-0.5">Site speed, mobile-friendliness, SSL, and crawlability</p>
                                        </div>
                                    </div>

                                    <div className="bp-what-you-get-item flex items-start gap-3 group">
                                        <div className="mt-1 w-6 h-6 rounded-full bg-[#7C3AED]/20 flex items-center justify-center flex-shrink-0 group-hover:bg-[#7C3AED]/30 transition-colors">
                                            <i className="bi bi-check2 text-[#A78BFA] text-xs"></i>
                                        </div>
                                        <div>
                                            <p className="bp-item-title text-sm">Performance summary</p>
                                            <p className="bp-item-desc mt-0.5">Overall score and actionable recommendations</p>
                                        </div>
                                    </div>
                                </div>

                                {/* Divider */}
                                <div className="border-t border-[var(--admin-border)]"></div>

                                {/* Integrations */}
                                <div>
                                    <p className="text-xs font-semibold text-[var(--admin-text-muted)] uppercase tracking-wider mb-3">Optional Integrations</p>
                                    <div className="flex flex-wrap gap-2">
                                        <span className="bp-optional-chip px-3 py-1.5 bg-[#3B82F6]/10 border border-[#3B82F6]/30 text-[#60a5fa] flex items-center gap-1.5">
                                            <i className="bi bi-graph-up"></i>
                                            GA4
                                        </span>
                                        <span className="bp-optional-chip px-3 py-1.5 bg-[#10B981]/10 border border-[#10B981]/30 text-[#34d399] flex items-center gap-1.5">
                                            <i className="bi bi-search"></i>
                                            GSC
                                        </span>
                                        <span className="bp-optional-chip px-3 py-1.5 bg-[#F79009]/10 border border-[#F79009]/30 text-[#fbbf24] flex items-center gap-1.5">
                                            <i className="bi bi-speedometer2"></i>
                                            PSI
                                        </span>
                                    </div>
                                </div>
                            </div>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );

    // RENDER REPORT FUNCTION
    function renderReport() {
        if (!reportData) return null;

        const overallScore = reportData.overall_score || 0;
        const categoryScores = reportData.category_scores || {};
        const issues = reportData.issues || [];
        const psi = reportData.psi || {};
        const ga4 = reportData.ga4 || {};
        const gsc = reportData.gsc || {};
        const pageData = reportData.page_data || {};

        const getScoreColor = (score) => {
            if (score >= 90) return '#12B76A';
            if (score >= 70) return '#F79009';
            return '#F04438';
        };

        const getScoreBg = (score) => {
            if (score >= 90) return 'bg-[#12B76A]/10 border-[#12B76A]/30';
            if (score >= 70) return 'bg-[#F79009]/10 border-[#F79009]/30';
            return 'bg-[#F04438]/10 border-[#F04438]/30';
        };

        const getGrade = (score) => {
            if (score >= 90) return 'A';
            if (score >= 80) return 'B';
            if (score >= 70) return 'C';
            if (score >= 60) return 'D';
            return 'F';
        };

        const getImpactColor = (impact) => {
            if (impact === 'high') return { bg: 'bg-[#F04438]/10', text: 'text-[#F04438]', border: 'border-l-[#F04438]' };
            if (impact === 'medium') return { bg: 'bg-[#F79009]/10', text: 'text-[#F79009]', border: 'border-l-[#F79009]' };
            return { bg: 'bg-[#12B76A]/10', text: 'text-[#12B76A]', border: 'border-l-[#12B76A]' };
        };

        const psiMobile = psi?.mobile?.kpis || {};
        const psiDesktop = psi?.desktop?.kpis || {};
        const labMetrics = psiMobile?.lab_metrics || psiDesktop?.lab_metrics || {};
        const psiCategories = psiMobile?.categories || psiDesktop?.categories || {};

        const highIssues = issues.filter(i => i.impact === 'high');
        const mediumIssues = issues.filter(i => i.impact === 'medium');
        const lowIssues = issues.filter(i => i.impact === 'low');

        const shareUrl = reportData.share_token ? `${window.location.origin}/audit-report/share/${reportData.share_token}` : null;

        const formatScore = (v) => (v == null || v === '' ? '—' : v);

        function ScoreCircle({ score, size = 120, label }) {
            const hasScore = score != null && score !== '';
            const numScore = hasScore ? Math.min(100, Math.max(0, Number(score))) : 0;
            const color = hasScore ? getScoreColor(numScore) : 'var(--admin-text-dim)';
            const radius = (size - 12) / 2;
            const circumference = 2 * Math.PI * radius;
            const progress = hasScore ? (numScore / 100) * circumference : 0;
            return (
                <div className="flex flex-col items-center relative">
                    <svg width={size} height={size} className="-rotate-90">
                        <circle cx={size/2} cy={size/2} r={radius} fill="none" stroke="rgba(255,255,255,0.06)" strokeWidth="8" />
                        <circle cx={size/2} cy={size/2} r={radius} fill="none" stroke={color} strokeWidth="8"
                            strokeDasharray={circumference} strokeDashoffset={circumference - progress}
                            strokeLinecap="round" className="transition-all duration-1000" />
                    </svg>
                    <div className="absolute flex flex-col items-center justify-center" style={{ width: size, height: size }}>
                        <span className="text-3xl font-bold" style={{ color }}>{formatScore(score)}</span>
                        <span className="text-xs text-[var(--admin-text-dim)]">{label}</span>
                    </div>
                </div>
            );
        }

        function MetricCard({ label, value, unit, status }) {
            const color = status === 'good' ? '#12B76A' : status === 'warning' ? '#F79009' : '#F04438';
            return (
                <div className="p-3 rounded-xl bg-[var(--admin-surface-2)] border border-[var(--admin-border)]">
                    <p className="text-xs text-[var(--admin-text-dim)] mb-1">{label}</p>
                    <p className="text-lg font-bold" style={{ color }}>{value}<span className="text-xs font-normal ml-0.5">{unit}</span></p>
                </div>
            );
        }

        function getMetricStatus(metric, value) {
            const thresholds = {
                fcp: [1800, 3000],
                lcp: [2500, 4000],
                cls: [0.1, 0.25],
                tbt: [200, 600],
                si: [3400, 5800],
            };
            const [good, poor] = thresholds[metric] || [0, 0];
            if (value <= good) return 'good';
            if (value <= poor) return 'warning';
            return 'poor';
        }

        return (
            <>
                {/* Executive Summary */}
                <div className="bp-card rounded-2xl p-6">
                    <div className="flex flex-col lg:flex-row gap-6">
                        <div className="flex-shrink-0 relative" style={{ width: 140, height: 140 }}>
                            <ScoreCircle score={overallScore} size={140} label="Overall" />
                        </div>
                        <div className="flex-1">
                            <div className="flex flex-wrap items-center gap-3 mb-3">
                                <h3 className="text-xl font-bold text-[var(--admin-text)]">{reportData.url}</h3>
                                <span className="px-3 py-1 rounded-full text-xs font-semibold bg-[#12B76A]/10 border border-[#12B76A]/30 text-[#12B76A]">
                                    <i className="bi bi-check-circle-fill mr-1"></i>Completed
                                </span>
                            </div>
                            <div className="flex flex-wrap gap-4 text-sm text-[var(--admin-text-muted)] mb-4">
                                <span className="flex items-center gap-1.5">
                                    <i className="bi bi-calendar3"></i>
                                    {reportData.created_at ? new Date(reportData.created_at).toLocaleDateString('en-US', { year:'numeric', month:'long', day:'numeric' }) : ''}
                                </span>
                                {reportData.finished_at && reportData.started_at && (
                                    <span className="flex items-center gap-1.5">
                                        <i className="bi bi-clock"></i>
                                        {Math.round((new Date(reportData.finished_at) - new Date(reportData.started_at)) / 1000)}s
                                    </span>
                                )}
                                <span className="flex items-center gap-1.5">
                                    <i className="bi bi-trophy"></i> Grade: <strong style={{ color: getScoreColor(overallScore) }}>{getGrade(overallScore)}</strong>
                                </span>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                {shareUrl && (
                                    <button onClick={() => { navigator.clipboard.writeText(shareUrl); }} className="px-3 py-1.5 rounded-lg text-xs font-medium bg-[var(--admin-surface-2)] border border-[var(--admin-border)] text-[var(--admin-text)] hover:border-[var(--admin-hover-border)] transition-colors flex items-center gap-1.5">
                                        <i className="bi bi-link-45deg"></i> Copy Share Link
                                    </button>
                                )}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Category Score Cards */}
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    {[
                        { label: 'SEO', score: psiCategories.seo_score ?? categoryScores.onpage ?? null, icon: 'bi-search', color: '#12B76A' },
                        { label: 'Performance', score: psiCategories.performance_score ?? categoryScores.performance ?? null, icon: 'bi-lightning-charge', color: '#F79009' },
                        { label: 'Accessibility', score: psiCategories.accessibility_score ?? null, icon: 'bi-universal-access', color: '#7C3AED' },
                        { label: 'Best Practices', score: psiCategories.best_practices_score ?? null, icon: 'bi-shield-check', color: '#3B82F6' },
                    ].map((cat, idx) => {
                        const s = cat.score;
                        const displayScore = s != null ? s : '—';
                        const scoreColor = s != null ? getScoreColor(s) : 'var(--admin-text-dim)';
                        return (
                            <div key={idx} className={`bp-card rounded-2xl p-5 border ${s != null ? getScoreBg(s) : 'border-[var(--admin-border)]'}`}>
                                <div className="flex items-center gap-2 mb-3">
                                    <i className={`bi ${cat.icon}`} style={{ color: cat.color }}></i>
                                    <span className="text-sm font-medium text-[var(--admin-text)]">{cat.label}</span>
                                </div>
                                <div className="text-3xl font-bold" style={{ color: scoreColor }}>
                                    {displayScore}{s != null && <span className="text-base font-normal text-[var(--admin-text-dim)]">/100</span>}
                                </div>
                            </div>
                        );
                    })}
                </div>

                {/* Lab Metrics (PSI) */}
                {labMetrics && Object.keys(labMetrics).length > 0 && (
                    <div className="bp-card rounded-2xl p-6">
                        <h3 className="text-lg font-bold text-[var(--admin-text)] mb-1">Core Web Vitals & Lab Metrics</h3>
                        <p className="text-sm text-[var(--admin-text-dim)] mb-4">Measured by Lighthouse (PageSpeed Insights)</p>
                        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                            {labMetrics.fcp_ms != null && <MetricCard label="First Contentful Paint" value={(labMetrics.fcp_ms / 1000).toFixed(1)} unit="s" status={getMetricStatus('fcp', labMetrics.fcp_ms)} />}
                            {labMetrics.lcp_ms != null && <MetricCard label="Largest Contentful Paint" value={(labMetrics.lcp_ms / 1000).toFixed(1)} unit="s" status={getMetricStatus('lcp', labMetrics.lcp_ms)} />}
                            {labMetrics.cls != null && <MetricCard label="Cumulative Layout Shift" value={labMetrics.cls.toFixed(3)} unit="" status={getMetricStatus('cls', labMetrics.cls)} />}
                            {labMetrics.tbt_ms != null && <MetricCard label="Total Blocking Time" value={Math.round(labMetrics.tbt_ms)} unit="ms" status={getMetricStatus('tbt', labMetrics.tbt_ms)} />}
                            {labMetrics.speed_index_ms != null && <MetricCard label="Speed Index" value={(labMetrics.speed_index_ms / 1000).toFixed(1)} unit="s" status={getMetricStatus('si', labMetrics.speed_index_ms)} />}
                            {labMetrics.tti_ms != null && <MetricCard label="Time to Interactive" value={(labMetrics.tti_ms / 1000).toFixed(1)} unit="s" status={getMetricStatus('fcp', labMetrics.tti_ms)} />}
                        </div>
                    </div>
                )}

                {/* On-Page Analysis */}
                {pageData && (
                    <div className="bp-card rounded-2xl p-6">
                        <h3 className="text-lg font-bold text-[var(--admin-text)] mb-1">On-Page SEO Analysis</h3>
                        <p className="text-sm text-[var(--admin-text-dim)] mb-4">Key elements found on the page</p>
                        <div className="space-y-2">
                            {[
                                { label: 'Title Tag', value: pageData.title || 'Missing', ok: !!pageData.title && pageData.title_len >= 30 && pageData.title_len <= 60, detail: pageData.title ? `${pageData.title_len} characters` : 'Not found' },
                                { label: 'Meta Description', value: pageData.meta_description ? (pageData.meta_description.substring(0, 80) + '...') : 'Missing', ok: !!pageData.meta_description && pageData.meta_len >= 70, detail: pageData.meta_description ? `${pageData.meta_len} characters` : 'Not found' },
                                { label: 'H1 Heading', value: `${pageData.h1_count || 0} found`, ok: pageData.h1_count === 1, detail: pageData.h1_count === 1 ? 'Perfect — exactly one H1' : pageData.h1_count === 0 ? 'Missing H1 tag' : `Multiple H1 tags (${pageData.h1_count})` },
                                { label: 'Word Count', value: `${pageData.word_count || 0} words`, ok: (pageData.word_count || 0) >= 300, detail: (pageData.word_count || 0) >= 300 ? 'Good content length' : 'Content may be too thin' },
                                { label: 'Images', value: `${pageData.images_total || 0} total, ${pageData.images_missing_alt || 0} missing alt`, ok: (pageData.images_missing_alt || 0) === 0, detail: (pageData.images_missing_alt || 0) === 0 ? 'All images have alt text' : `${pageData.images_missing_alt} images need alt text` },
                                { label: 'Open Graph', value: pageData.og_present ? 'Present' : 'Missing', ok: pageData.og_present, detail: pageData.og_present ? 'Social sharing tags found' : 'Add OG tags for better social sharing' },
                                { label: 'Schema.org', value: pageData.schema_types?.length ? pageData.schema_types.join(', ') : 'None found', ok: pageData.schema_types?.length > 0, detail: pageData.schema_types?.length > 0 ? 'Structured data detected' : 'Add structured data for rich results' },
                                { label: 'Links', value: `${pageData.internal_links_count || 0} internal, ${pageData.external_links_count || 0} external`, ok: (pageData.internal_links_count || 0) > 0, detail: 'Internal linking structure' },
                            ].map((item, idx) => (
                                <div key={idx} className={`flex items-center gap-3 p-3 rounded-lg border-l-4 ${item.ok ? 'border-l-[#12B76A] bg-[#12B76A]/5' : 'border-l-[#F79009] bg-[#F79009]/5'}`}>
                                    <i className={`bi ${item.ok ? 'bi-check-circle-fill text-[#12B76A]' : 'bi-exclamation-circle-fill text-[#F79009]'} text-lg`}></i>
                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-center gap-2">
                                            <span className="font-semibold text-sm text-[var(--admin-text)]">{item.label}</span>
                                            <span className="text-xs text-[var(--admin-text-dim)] truncate">{item.detail}</span>
                                        </div>
                                        <p className="text-xs text-[var(--admin-text-muted)] truncate mt-0.5">{item.value}</p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Issues & Recommendations */}
                <div className="bp-card rounded-2xl p-6">
                    <h3 className="text-lg font-bold text-[var(--admin-text)] mb-1">Issues & Recommendations</h3>
                    <p className="text-sm text-[var(--admin-text-dim)] mb-4">Actionable insights to improve your site</p>
                    
                    <div className="grid grid-cols-3 gap-3 mb-6">
                        <div className="p-3 rounded-xl bg-[#F04438]/10 border border-[#F04438]/20 text-center">
                            <p className="text-2xl font-bold text-[#F04438]">{highIssues.length}</p>
                            <p className="text-xs text-[var(--admin-text-dim)]">High Impact</p>
                        </div>
                        <div className="p-3 rounded-xl bg-[#F79009]/10 border border-[#F79009]/20 text-center">
                            <p className="text-2xl font-bold text-[#F79009]">{mediumIssues.length}</p>
                            <p className="text-xs text-[var(--admin-text-dim)]">Medium Impact</p>
                        </div>
                        <div className="p-3 rounded-xl bg-[#12B76A]/10 border border-[#12B76A]/20 text-center">
                            <p className="text-2xl font-bold text-[#12B76A]">{lowIssues.length}</p>
                            <p className="text-xs text-[var(--admin-text-dim)]">Low Impact</p>
                        </div>
                    </div>

                    {issues.length > 0 ? (
                        <div className="space-y-3">
                            {issues.map((issue, idx) => {
                                const ic = getImpactColor(issue.impact);
                                return (
                                    <div key={idx} className={`p-4 rounded-xl border-l-4 ${ic.border} bg-[var(--admin-surface-2)] border border-[var(--admin-border)]`}>
                                        <div className="flex items-start gap-3">
                                            <span className={`mt-0.5 px-2 py-0.5 rounded text-[10px] font-bold uppercase ${ic.bg} ${ic.text}`}>{issue.impact}</span>
                                            <div className="flex-1 min-w-0">
                                                <h5 className="font-semibold text-sm text-[var(--admin-text)] mb-1">{issue.title}</h5>
                                                <p className="text-xs text-[var(--admin-text-dim)] mb-2">{issue.description}</p>
                                                {issue.recommendation && (
                                                    <div className="flex items-start gap-1.5 text-xs text-[#7C3AED]">
                                                        <i className="bi bi-lightbulb mt-0.5"></i>
                                                        <span>{issue.recommendation}</span>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    ) : (
                        <div className="text-center py-8">
                            <i className="bi bi-check-circle text-4xl text-[#12B76A]"></i>
                            <p className="text-[var(--admin-text-dim)] mt-2">No issues found. Great job!</p>
                        </div>
                    )}
                </div>

                {/* PSI Opportunities */}
                {psiMobile?.opportunities?.length > 0 && (
                    <div className="bp-card rounded-2xl p-6">
                        <h3 className="text-lg font-bold text-[var(--admin-text)] mb-1">Performance Opportunities</h3>
                        <p className="text-sm text-[var(--admin-text-dim)] mb-4">From Lighthouse — potential savings</p>
                        <div className="space-y-2">
                            {psiMobile.opportunities.map((opp, idx) => (
                                <div key={idx} className="flex items-center gap-3 p-3 rounded-lg bg-[var(--admin-surface-2)] border border-[var(--admin-border)]">
                                    <i className="bi bi-arrow-down-circle text-[#F79009]"></i>
                                    <div className="flex-1 min-w-0">
                                        <p className="text-sm font-medium text-[var(--admin-text)]">{opp.title}</p>
                                    </div>
                                    {opp.savings_ms > 0 && (
                                        <span className="text-xs font-semibold text-[#F79009] bg-[#F79009]/10 px-2 py-1 rounded">
                                            Save {(opp.savings_ms / 1000).toFixed(1)}s
                                        </span>
                                    )}
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Google Search Console */}
                {gsc?.connected && gsc?.summary && (
                    <div className="bp-card rounded-2xl p-6">
                        <div className="flex items-center gap-2 mb-1">
                            <i className="bi bi-google text-[#12B76A]"></i>
                            <h3 className="text-lg font-bold text-[var(--admin-text)]">Search Console Data</h3>
                        </div>
                        <p className="text-sm text-[var(--admin-text-dim)] mb-4">Last 30 days — {gsc.site_url}</p>
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
                            <div className="p-3 rounded-xl bg-[var(--admin-surface-2)] border border-[var(--admin-border)] text-center">
                                <p className="text-2xl font-bold text-[#3B82F6]">{(gsc.summary.total_clicks || 0).toLocaleString()}</p>
                                <p className="text-xs text-[var(--admin-text-dim)]">Total Clicks</p>
                            </div>
                            <div className="p-3 rounded-xl bg-[var(--admin-surface-2)] border border-[var(--admin-border)] text-center">
                                <p className="text-2xl font-bold text-[#7C3AED]">{(gsc.summary.total_impressions || 0).toLocaleString()}</p>
                                <p className="text-xs text-[var(--admin-text-dim)]">Impressions</p>
                            </div>
                            <div className="p-3 rounded-xl bg-[var(--admin-surface-2)] border border-[var(--admin-border)] text-center">
                                <p className="text-2xl font-bold text-[#12B76A]">{gsc.summary.avg_ctr || 0}%</p>
                                <p className="text-xs text-[var(--admin-text-dim)]">Avg CTR</p>
                            </div>
                            <div className="p-3 rounded-xl bg-[var(--admin-surface-2)] border border-[var(--admin-border)] text-center">
                                <p className="text-2xl font-bold text-[#F79009]">{gsc.summary.avg_position || 0}</p>
                                <p className="text-xs text-[var(--admin-text-dim)]">Avg Position</p>
                            </div>
                        </div>
                        {gsc.top_queries?.length > 0 && (
                            <>
                                <h4 className="font-semibold text-sm text-[var(--admin-text)] mb-3">Top Keywords</h4>
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="text-xs text-[var(--admin-text-dim)] border-b border-[var(--admin-border)]">
                                                <th className="text-left py-2 pr-4">Query</th>
                                                <th className="text-right py-2 px-2">Clicks</th>
                                                <th className="text-right py-2 px-2">Impressions</th>
                                                <th className="text-right py-2 px-2">CTR</th>
                                                <th className="text-right py-2 pl-2">Position</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {gsc.top_queries.slice(0, 10).map((q, idx) => (
                                                <tr key={idx} className="border-b border-[var(--admin-border)] last:border-0">
                                                    <td className="py-2 pr-4 text-[var(--admin-text)] font-medium truncate max-w-[200px]">{q.query}</td>
                                                    <td className="py-2 px-2 text-right text-[var(--admin-text)]">{q.clicks}</td>
                                                    <td className="py-2 px-2 text-right text-[var(--admin-text-muted)]">{q.impressions}</td>
                                                    <td className="py-2 px-2 text-right text-[var(--admin-text-muted)]">{(q.ctr * 100).toFixed(1)}%</td>
                                                    <td className="py-2 pl-2 text-right text-[var(--admin-text-muted)]">{q.position.toFixed(1)}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </>
                        )}
                    </div>
                )}

                {/* Google Analytics */}
                {ga4?.connected && ga4?.summary && (
                    <div className="bp-card rounded-2xl p-6">
                        <div className="flex items-center gap-2 mb-1">
                            <i className="bi bi-graph-up text-[#3B82F6]"></i>
                            <h3 className="text-lg font-bold text-[var(--admin-text)]">Analytics Overview</h3>
                        </div>
                        <p className="text-sm text-[var(--admin-text-dim)] mb-4">Last 30 days — {ga4.property}</p>
                        <div className="grid grid-cols-3 gap-3 mb-6">
                            <div className="p-3 rounded-xl bg-[var(--admin-surface-2)] border border-[var(--admin-border)] text-center">
                                <p className="text-2xl font-bold text-[#3B82F6]">{(ga4.summary.total_sessions || 0).toLocaleString()}</p>
                                <p className="text-xs text-[var(--admin-text-dim)]">Sessions</p>
                            </div>
                            <div className="p-3 rounded-xl bg-[var(--admin-surface-2)] border border-[var(--admin-border)] text-center">
                                <p className="text-2xl font-bold text-[#7C3AED]">{(ga4.summary.total_users || 0).toLocaleString()}</p>
                                <p className="text-xs text-[var(--admin-text-dim)]">Users</p>
                            </div>
                            <div className="p-3 rounded-xl bg-[var(--admin-surface-2)] border border-[var(--admin-border)] text-center">
                                <p className="text-2xl font-bold text-[#12B76A]">{ga4.summary.avg_engagement_rate || 0}%</p>
                                <p className="text-xs text-[var(--admin-text-dim)]">Engagement Rate</p>
                            </div>
                        </div>
                        {ga4.top_pages?.length > 0 && (
                            <>
                                <h4 className="font-semibold text-sm text-[var(--admin-text)] mb-3">Top Landing Pages</h4>
                                <div className="space-y-2">
                                    {ga4.top_pages.slice(0, 8).map((pg, idx) => (
                                        <div key={idx} className="flex items-center justify-between p-2 rounded-lg bg-[var(--admin-surface-2)] border border-[var(--admin-border)]">
                                            <span className="text-sm text-[var(--admin-text)] truncate flex-1 mr-3">{pg.landing_page}</span>
                                            <span className="text-xs text-[var(--admin-text-muted)] whitespace-nowrap">{pg.sessions} sessions</span>
                                        </div>
                                    ))}
                                </div>
                            </>
                        )}
                    </div>
                )}

                {/* Not Connected CTAs */}
                {(!gsc?.connected || !ga4?.connected) && (
                    <div className="bp-card rounded-2xl p-6">
                        <h3 className="text-lg font-bold text-[var(--admin-text)] mb-2">Enhance Your Report</h3>
                        <p className="text-sm text-[var(--admin-text-dim)] mb-4">Connect Google services to unlock more insights in future audits.</p>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            {!ga4?.connected && (
                                <a href="/google-seo/connect" className="bp-integration-tile flex items-center gap-3 p-4 group">
                                    <div className="bp-integration-icon bg-gradient-to-br from-[#3B82F6]/20 to-[#3B82F6]/08 flex items-center justify-center flex-shrink-0">
                                        <i className="bi bi-graph-up text-[#60a5fa]"></i>
                                    </div>
                                    <div className="flex-1 text-left">
                                        <p className="text-sm font-medium text-[var(--admin-text)] group-hover:text-[#60a5fa]">Connect Google Analytics</p>
                                        <p className="text-xs text-[var(--admin-text-dim)]">Traffic, engagement & top pages</p>
                                    </div>
                                    <i className="bi bi-arrow-right text-[var(--admin-text-dim)] group-hover:text-[#60a5fa]"></i>
                                </a>
                            )}
                            {!gsc?.connected && (
                                <a href="/google-seo/connect" className="bp-integration-tile flex items-center gap-3 p-4 group">
                                    <div className="bp-integration-icon bg-gradient-to-br from-[#10B981]/20 to-[#10B981]/08 flex items-center justify-center flex-shrink-0">
                                        <i className="bi bi-search text-[#34d399]"></i>
                                    </div>
                                    <div className="flex-1 text-left">
                                        <p className="text-sm font-medium text-[var(--admin-text)] group-hover:text-[#34d399]">Connect Search Console</p>
                                        <p className="text-xs text-[var(--admin-text-dim)]">Keywords, clicks & impressions</p>
                                    </div>
                                    <i className="bi bi-arrow-right text-[var(--admin-text-dim)] group-hover:text-[#34d399]"></i>
                                </a>
                            )}
                        </div>
                    </div>
                )}
            </>
        );
    }
}
