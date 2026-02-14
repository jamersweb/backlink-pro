import AppLayout from '../Components/Layout/AppLayout';
import Card from '../Components/Shared/Card';
import { useState, useEffect } from 'react';

export default function AuditReport({ googleConnected = false, googleEmail = null, recentAudits = [] }) {
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
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.status === 'completed') {
                    clearInterval(pollInterval);
                    setAuditProgress(100);
                    setStatus('Audit completed successfully!');
                    
                    // Fetch full report data
                    fetchReportData(currentAuditId);
                } else if (data.status === 'failed') {
                    clearInterval(pollInterval);
                    setStatus(`Error: ${data.error || 'Audit failed'}`);
                    setAuditStatus('failed');
                    setIsLoading(false);
                } else if (data.status === 'running') {
                    setAuditProgress(data.progress_percent || 50);
                    setStatus('Analyzing website...');
                } else {
                    setAuditProgress(data.progress_percent || 10);
                    setStatus('Queued - waiting to start...');
                }
            } catch (error) {
                console.error('Error polling audit status:', error);
                setStatus('Error: Failed to check audit status');
            }
        }, 2500);

        return () => clearInterval(pollInterval);
    }, [currentAuditId, auditStatus]);

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
            setReportData(data.audit || data);
            setAuditStatus('completed');
            setIsLoading(false);
        } catch (error) {
            console.error('Error fetching report:', error);
            setStatus('Error: Failed to load report');
            setAuditStatus('failed');
            setIsLoading(false);
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
                
                // Check if already completed (sync queue)
                if (data.status === 'completed') {
                    setStatus('Audit completed! Loading report...');
                    setAuditProgress(100);
                    // Fetch report immediately
                    setTimeout(() => fetchReportData(data.audit_id), 500);
                } else {
                    // Will be completed via polling
                    setStatus('Audit processing...');
                    setAuditProgress(10);
                }
            } else if (data.audit_id) {
                setCurrentAuditId(data.audit_id);
                setStatus('Audit processing...');
                setAuditProgress(10);
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

    // Handle re-run audit
    const handleRerun = () => {
        setReportData(null);
        setAuditStatus('idle');
        setCurrentAuditId(null);
        setStatus('');
        setAuditProgress(0);
        setIsLoading(false);
        setFormData({ url: '', email: '', send_to_email: true });
    };

    // Handle PDF download
    const handleDownloadPDF = async () => {
        if (!currentAuditId) return;

        try {
            setStatus('Generating PDF...');
            
            // For MVP: Generate PDF client-side using html2pdf
            const element = document.getElementById('audit-report-content');
            
            if (window.html2pdf) {
                const opt = {
                    margin: 10,
                    filename: `audit-report-${reportData.url}-${new Date().toISOString().split('T')[0]}.pdf`,
                    image: { type: 'jpeg', quality: 0.98 },
                    html2canvas: { scale: 2 },
                    jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
                };
                
                await window.html2pdf().set(opt).from(element).save();
                setStatus('PDF downloaded successfully!');
                setTimeout(() => setStatus(''), 3000);
            } else {
                // Fallback: Server-side PDF generation
                const response = await fetch(`/audit-report/${currentAuditId}/pdf`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                });
                
                if (!response.ok) {
                    throw new Error('PDF generation failed');
                }
                
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `audit-report-${reportData.url}.pdf`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                
                setStatus('PDF downloaded successfully!');
                setTimeout(() => setStatus(''), 3000);
            }
        } catch (error) {
            console.error('Error downloading PDF:', error);
            setStatus('Error: Failed to generate PDF');
            setTimeout(() => setStatus(''), 3000);
        }
    };

    return (
        <AppLayout header="Audit Report">
            <div className="space-y-6 max-w-7xl mx-auto">
                {/* Header Card */}
                <div className="relative overflow-hidden rounded-2xl bg-[var(--admin-surface)] border border-[var(--admin-border)] p-8 shadow-[var(--admin-shadow-md)]">
                    {/* Background decoration - only visible in dark mode */}
                    <div className="absolute top-0 right-0 w-64 h-64 bg-[#2F6BFF]/10 rounded-full blur-3xl -mr-32 -mt-32 dark:opacity-100 opacity-0"></div>
                    <div className="absolute bottom-0 left-0 w-48 h-48 bg-[#B6F400]/10 rounded-full blur-3xl -ml-24 -mb-24 dark:opacity-100 opacity-0"></div>
                    
                    <div className="relative flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                        <div>
                            <h2 className="text-2xl font-bold text-[var(--admin-text)] mb-2">Audit Report</h2>
                            <p className="text-[var(--admin-text-muted)]">
                                {auditStatus === 'completed' 
                                    ? `Report for ${reportData?.url || 'your website'}`
                                    : 'Enter website details to generate an SEO audit report.'
                                }
                            </p>
                        </div>
                        
                        {auditStatus === 'completed' ? (
                            <div className="flex gap-3">
                                <button
                                    onClick={handleRerun}
                                    className="px-4 py-2.5 bg-[var(--admin-surface-2)] hover:bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] rounded-lg font-medium text-[var(--admin-text)] transition-colors flex items-center gap-2"
                                >
                                    <i className="bi bi-arrow-clockwise"></i>
                                    Re-run
                                </button>
                                <button
                                    onClick={handleDownloadPDF}
                                    className="px-6 py-2.5 rounded-lg font-medium transition-all duration-200 flex items-center gap-2 bg-gradient-to-r from-[#2F6BFF] to-[#2457D6] hover:from-[#2457D6] hover:to-[#1E4BBD] text-white shadow-lg shadow-[#2F6BFF]/20"
                                >
                                    <i className="bi bi-download"></i>
                                    Download PDF
                                </button>
                            </div>
                        ) : (
                            <button
                                onClick={handleSubmit}
                                disabled={!isFormValid() || isLoading}
                                className={`px-6 py-2.5 rounded-lg font-medium transition-all duration-200 flex items-center gap-2 ${
                                    isFormValid() && !isLoading
                                        ? 'bg-gradient-to-r from-[#2F6BFF] to-[#2457D6] hover:from-[#2457D6] hover:to-[#1E4BBD] text-white shadow-lg shadow-[#2F6BFF]/20'
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

                {/* Status Message with Progress */}
                {status && (
                    <div className={`p-4 rounded-xl border ${
                        status.includes('completed') || status.includes('Redirecting')
                            ? 'bg-[#12B76A]/10 border-[#12B76A]/30'
                            : status.includes('Error') || status.includes('failed')
                                ? 'bg-[#F04438]/10 border-[#F04438]/30'
                                : 'bg-[#2F6BFF]/10 border-[#2F6BFF]/30'
                    }`}>
                        <div className="flex items-center gap-3 mb-3">
                            <i className={`bi ${
                                status.includes('completed') || status.includes('Redirecting') ? 'bi-check-circle-fill text-[#12B76A]' :
                                status.includes('Error') || status.includes('failed') ? 'bi-x-circle-fill text-[#F04438]' :
                                'bi-arrow-repeat animate-spin text-[#5B8AFF]'
                            } text-lg`}></i>
                            <span className={`font-medium ${
                                status.includes('completed') || status.includes('Redirecting') ? 'text-[#12B76A]' :
                                status.includes('Error') || status.includes('failed') ? 'text-[#F04438]' :
                                'text-[#5B8AFF]'
                            }`}>{status}</span>
                        </div>
                        
                        {isLoading && auditProgress > 0 && (
                            <div className="space-y-2">
                                <div className="flex justify-between text-xs text-[var(--admin-text-dim)]">
                                    <span>Progress</span>
                                    <span>{auditProgress}%</span>
                                </div>
                                <div className="w-full h-2 bg-[var(--admin-surface-2)] rounded-full overflow-hidden">
                                    <div 
                                        className="h-full bg-gradient-to-r from-[#2F6BFF] to-[#5B8AFF] transition-all duration-500 ease-out"
                                        style={{ width: `${auditProgress}%` }}
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
                            <Card variant="elevated">
                                <form onSubmit={handleSubmit} className="space-y-6">
                                {/* Website URL Field */}
                                <div>
                                    <label className="block text-sm font-medium text-[var(--admin-text)] mb-2">
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
                                            className={`w-full pl-11 pr-4 py-3 bg-[var(--admin-surface-2)] border ${
                                                errors.url ? 'border-[#F04438]' : 'border-[var(--admin-border)]'
                                            } rounded-lg text-[var(--admin-text)] placeholder-[var(--admin-text-dim)] focus:outline-none focus:border-[#2F6BFF] focus:ring-1 focus:ring-[#2F6BFF] transition-colors`}
                                        />
                                    </div>
                                    {errors.url && (
                                        <p className="mt-2 text-sm text-[#F04438] flex items-center gap-1">
                                            <i className="bi bi-exclamation-circle"></i>
                                            {errors.url}
                                        </p>
                                    )}
                                    <p className="mt-2 text-xs text-[var(--admin-text-dim)] flex items-center gap-1">
                                        <i className="bi bi-info-circle"></i>
                                        We'll crawl your website and create an audit report.
                                    </p>
                                </div>

                                {/* Email Field */}
                                <div>
                                    <label className="block text-sm font-medium text-[var(--admin-text)] mb-2">
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
                                            className={`w-full pl-11 pr-4 py-3 bg-[var(--admin-surface-2)] border ${
                                                errors.email ? 'border-[#F04438]' : 'border-[var(--admin-border)]'
                                            } rounded-lg text-[var(--admin-text)] placeholder-[var(--admin-text-dim)] focus:outline-none focus:border-[#2F6BFF] focus:ring-1 focus:ring-[#2F6BFF] transition-colors`}
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
                                <div className="flex items-start gap-3 p-4 bg-[var(--admin-hover-bg)] rounded-lg border border-[var(--admin-border)]">
                                    <input
                                        type="checkbox"
                                        id="send-email"
                                        checked={formData.send_to_email}
                                        onChange={(e) => handleCheckboxChange(e.target.checked)}
                                        className="mt-0.5 w-4 h-4 rounded border-[var(--admin-border)] bg-[var(--admin-bg)] text-[#2F6BFF] focus:ring-[#2F6BFF] focus:ring-offset-0 cursor-pointer"
                                    />
                                    <label htmlFor="send-email" className="flex-1 text-sm text-[var(--admin-text)] cursor-pointer">
                                        <span className="font-medium">Send report to email</span>
                                        <p className="text-xs text-[var(--admin-text-dim)] mt-1">
                                            Receive the audit report in your email inbox once completed.
                                        </p>
                                    </label>
                                </div>

                                {/* Google Integrations - Moved under checkbox */}
                                <div className="space-y-3">
                                    <label className="block text-sm font-medium text-[var(--admin-text)]">
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
                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <button
                                                type="button"
                                                onClick={handleGoogleConnect}
                                                className="flex items-center gap-3 p-3 bg-[var(--admin-surface-2)] hover:bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] hover:border-[var(--admin-hover-border)] rounded-lg transition-all duration-150 group"
                                            >
                                                <div className="w-8 h-8 rounded-lg bg-[#2F6BFF]/15 flex items-center justify-center flex-shrink-0">
                                                    <i className="bi bi-graph-up text-[#5B8AFF]"></i>
                                                </div>
                                                <div className="flex-1 text-left">
                                                    <p className="text-sm font-medium text-[var(--admin-text)] group-hover:text-[#5B8AFF] transition-colors">Google Analytics</p>
                                                    <p className="text-xs text-[var(--admin-text-dim)]">Connect GA4</p>
                                                </div>
                                                <i className="bi bi-arrow-right text-[var(--admin-text-dim)] group-hover:text-[#5B8AFF] transition-colors"></i>
                                            </button>
                                            
                                            <button
                                                type="button"
                                                onClick={handleGoogleConnect}
                                                className="flex items-center gap-3 p-3 bg-[var(--admin-surface-2)] hover:bg-[var(--admin-hover-bg)] border border-[var(--admin-border)] hover:border-[var(--admin-hover-border)] rounded-lg transition-all duration-150 group"
                                            >
                                                <div className="w-8 h-8 rounded-lg bg-[#12B76A]/15 flex items-center justify-center flex-shrink-0">
                                                    <i className="bi bi-search text-[#12B76A]"></i>
                                                </div>
                                                <div className="flex-1 text-left">
                                                    <p className="text-sm font-medium text-[var(--admin-text)] group-hover:text-[#12B76A] transition-colors">Search Console</p>
                                                    <p className="text-xs text-[var(--admin-text-dim)]">Connect GSC</p>
                                                </div>
                                                <i className="bi bi-arrow-right text-[var(--admin-text-dim)] group-hover:text-[#12B76A] transition-colors"></i>
                                            </button>
                                        </div>
                                    )}
                                    <p className="text-xs text-[var(--admin-text-dim)] flex items-center gap-1">
                                        <i className="bi bi-info-circle"></i>
                                        Connect to enrich your audit with traffic data and search performance metrics
                                    </p>
                                </div>

                                {/* Submit Button (Mobile) */}
                                <div className="md:hidden">
                                    <button
                                        type="submit"
                                        disabled={!isFormValid() || isLoading}
                                        className={`w-full px-6 py-3 rounded-lg font-medium transition-all duration-200 flex items-center justify-center gap-2 ${
                                            isFormValid() && !isLoading
                                                ? 'bg-gradient-to-r from-[#2F6BFF] to-[#2457D6] hover:from-[#2457D6] hover:to-[#1E4BBD] text-white shadow-lg shadow-[#2F6BFF]/20'
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
                            </Card>
                        </div>

                        {/* Info Side Card - Takes 1 column on desktop */}
                        <div className="lg:col-span-1">
                            <Card variant="elevated" className="h-full">
                            <div className="space-y-6">
                                {/* Title */}
                                <div className="flex items-center gap-3">
                                    <div className="w-10 h-10 rounded-lg bg-[#B6F400]/15 flex items-center justify-center">
                                        <i className="bi bi-lightbulb text-xl text-[#B6F400]"></i>
                                    </div>
                                    <h3 className="text-lg font-semibold text-[var(--admin-text)]">What you'll get</h3>
                                </div>

                                {/* Feature List */}
                                <div className="space-y-4">
                                    <div className="flex items-start gap-3 group">
                                        <div className="mt-1 w-5 h-5 rounded-full bg-[#2F6BFF]/20 flex items-center justify-center flex-shrink-0 group-hover:bg-[#2F6BFF]/30 transition-colors">
                                            <i className="bi bi-check2 text-[#5B8AFF] text-xs"></i>
                                        </div>
                                        <div>
                                            <p className="text-sm font-medium text-[var(--admin-text)]">On-page checks</p>
                                            <p className="text-xs text-[var(--admin-text-dim)] mt-0.5">Title tags, meta descriptions, headings, and content analysis</p>
                                        </div>
                                    </div>

                                    <div className="flex items-start gap-3 group">
                                        <div className="mt-1 w-5 h-5 rounded-full bg-[#2F6BFF]/20 flex items-center justify-center flex-shrink-0 group-hover:bg-[#2F6BFF]/30 transition-colors">
                                            <i className="bi bi-check2 text-[#5B8AFF] text-xs"></i>
                                        </div>
                                        <div>
                                            <p className="text-sm font-medium text-[var(--admin-text)]">Off-page signals</p>
                                            <p className="text-xs text-[var(--admin-text-dim)] mt-0.5">Backlink profile, domain authority, and external factors</p>
                                        </div>
                                    </div>

                                    <div className="flex items-start gap-3 group">
                                        <div className="mt-1 w-5 h-5 rounded-full bg-[#2F6BFF]/20 flex items-center justify-center flex-shrink-0 group-hover:bg-[#2F6BFF]/30 transition-colors">
                                            <i className="bi bi-check2 text-[#5B8AFF] text-xs"></i>
                                        </div>
                                        <div>
                                            <p className="text-sm font-medium text-[var(--admin-text)]">Technical SEO</p>
                                            <p className="text-xs text-[var(--admin-text-dim)] mt-0.5">Site speed, mobile-friendliness, SSL, and crawlability</p>
                                        </div>
                                    </div>

                                    <div className="flex items-start gap-3 group">
                                        <div className="mt-1 w-5 h-5 rounded-full bg-[#2F6BFF]/20 flex items-center justify-center flex-shrink-0 group-hover:bg-[#2F6BFF]/30 transition-colors">
                                            <i className="bi bi-check2 text-[#5B8AFF] text-xs"></i>
                                        </div>
                                        <div>
                                            <p className="text-sm font-medium text-[var(--admin-text)]">Performance summary</p>
                                            <p className="text-xs text-[var(--admin-text-dim)] mt-0.5">Overall score and actionable recommendations</p>
                                        </div>
                                    </div>
                                </div>

                                {/* Divider */}
                                <div className="border-t border-[var(--admin-border)]"></div>

                                {/* Integrations */}
                                <div>
                                    <p className="text-xs font-semibold text-[var(--admin-text-muted)] uppercase tracking-wider mb-3">Optional Integrations</p>
                                    <div className="flex flex-wrap gap-2">
                                        <span className="px-3 py-1.5 bg-[#2F6BFF]/10 border border-[#2F6BFF]/30 rounded-full text-xs font-medium text-[#5B8AFF] flex items-center gap-1.5">
                                            <i className="bi bi-graph-up"></i>
                                            GA4
                                        </span>
                                        <span className="px-3 py-1.5 bg-[#12B76A]/10 border border-[#12B76A]/30 rounded-full text-xs font-medium text-[#12B76A] flex items-center gap-1.5">
                                            <i className="bi bi-search"></i>
                                            GSC
                                        </span>
                                        <span className="px-3 py-1.5 bg-[#F79009]/10 border border-[#F79009]/30 rounded-full text-xs font-medium text-[#F79009] flex items-center gap-1.5">
                                            <i className="bi bi-speedometer2"></i>
                                            PSI
                                        </span>
                                    </div>
                                </div>
                            </div>
                            </Card>
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
        const performanceData = reportData.performance_summary || {};

        const getScoreColor = (score) => {
            if (score >= 90) return { bg: 'bg-[#12B76A]/10', border: 'border-[#12B76A]/30', text: 'text-[#12B76A]' };
            if (score >= 70) return { bg: 'bg-[#F79009]/10', border: 'border-[#F79009]/30', text: 'text-[#F79009]' };
            return { bg: 'bg-[#F04438]/10', border: 'border-[#F04438]/30', text: 'text-[#F04438]' };
        };

        const getGrade = (score) => {
            if (score >= 90) return 'A';
            if (score >= 80) return 'B';
            if (score >= 70) return 'C';
            if (score >= 60) return 'D';
            return 'F';
        };

        const scoreColors = getScoreColor(overallScore);
        const grade = getGrade(overallScore);

        const criticalIssues = issues.filter(i => i.severity === 'critical');
        const warningIssues = issues.filter(i => i.severity === 'warning');
        const infoIssues = issues.filter(i => i.severity === 'info');

        return (
            <>
                {/* Report Header with Score */}
                <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <div className="lg:col-span-3">
                        <Card variant="elevated">
                            <div className="flex items-start gap-4">
                                <div className="w-16 h-16 rounded-2xl bg-gradient-to-br from-[#2F6BFF] to-[#2457D6] flex items-center justify-center flex-shrink-0 shadow-lg shadow-[#2F6BFF]/30">
                                    <i className="bi bi-globe text-2xl text-white"></i>
                                </div>
                                <div className="flex-1">
                                    <h3 className="text-xl font-bold text-[var(--admin-text)] mb-2">{reportData.url}</h3>
                                    <div className="flex flex-wrap items-center gap-4 text-sm text-[var(--admin-text-muted)]">
                                        <span className="flex items-center gap-1.5">
                                            <i className="bi bi-calendar3"></i>
                                            {new Date(reportData.created_at).toLocaleDateString('en-US', { 
                                                year: 'numeric', 
                                                month: 'long', 
                                                day: 'numeric' 
                                            })}
                                        </span>
                                        {reportData.finished_at && (
                                            <span className="flex items-center gap-1.5">
                                                <i className="bi bi-clock"></i>
                                                Completed in {Math.round((new Date(reportData.finished_at) - new Date(reportData.started_at)) / 1000)}s
                                            </span>
                                        )}
                                        <span className="px-3 py-1 rounded-full text-xs font-medium bg-[#12B76A]/10 border border-[#12B76A]/30 text-[#12B76A]">
                                            <i className="bi bi-check-circle-fill mr-1"></i>
                                            Completed
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </Card>
                    </div>

                    <div className="lg:col-span-1">
                        <Card variant="elevated" className="h-full">
                            <div className="flex flex-col items-center justify-center h-full">
                                <div className={`relative w-32 h-32 rounded-full ${scoreColors.bg} border-4 ${scoreColors.border} flex items-center justify-center`}>
                                    <div className="text-center">
                                        <div className={`text-3xl font-bold ${scoreColors.text}`}>{overallScore}</div>
                                        <div className="text-xs text-[var(--admin-text-dim)] mt-1">Overall</div>
                                        <div className={`text-xl font-bold ${scoreColors.text} mt-1`}>{grade}</div>
                                    </div>
                                </div>
                            </div>
                        </Card>
                    </div>
                </div>

                {/* KPI Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    {[
                        { label: 'Performance', score: categoryScores.performance || 0, icon: 'bi-lightning-charge', color: '#F79009' },
                        { label: 'SEO Health', score: categoryScores.onpage || 0, icon: 'bi-heart-pulse', color: '#12B76A' },
                        { label: 'Technical', score: categoryScores.technical || 0, icon: 'bi-gear', color: '#2F6BFF' },
                        { label: 'Issues Found', score: issues.length, icon: 'bi-exclamation-triangle', color: '#F04438', isCount: true },
                    ].map((kpi, index) => {
                        const colors = kpi.isCount ? { bg: 'bg-[#F04438]/10', text: 'text-[#F04438]' } : getScoreColor(kpi.score);
                        return (
                            <Card key={index} variant="elevated" className="hover:shadow-[var(--admin-shadow-lg)] transition-shadow duration-200">
                                <div className="flex items-start justify-between">
                                    <div>
                                        <p className="text-sm text-[var(--admin-text-muted)] mb-2">{kpi.label}</p>
                                        <p className={`text-3xl font-bold ${colors.text}`}>
                                            {kpi.isCount ? kpi.score : kpi.score}
                                            {!kpi.isCount && <span className="text-lg">/100</span>}
                                        </p>
                                    </div>
                                    <div className={`w-12 h-12 rounded-xl ${colors.bg} flex items-center justify-center`}>
                                        <i className={`bi ${kpi.icon} text-xl`} style={{ color: kpi.color }}></i>
                                    </div>
                                </div>
                            </Card>
                        );
                    })}
                </div>

                {/* Issues Summary */}
                <Card variant="elevated">
                    <h3 className="text-lg font-semibold text-[var(--admin-text)] mb-4">Issues Summary</h3>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div className="p-4 bg-[#F04438]/10 border border-[#F04438]/30 rounded-xl">
                            <div className="flex items-center gap-3">
                                <i className="bi bi-x-circle-fill text-2xl text-[#F04438]"></i>
                                <div>
                                    <p className="text-2xl font-bold text-[#F04438]">{criticalIssues.length}</p>
                                    <p className="text-sm text-[var(--admin-text-dim)]">Critical Issues</p>
                                </div>
                            </div>
                        </div>
                        <div className="p-4 bg-[#F79009]/10 border border-[#F79009]/30 rounded-xl">
                            <div className="flex items-center gap-3">
                                <i className="bi bi-exclamation-triangle-fill text-2xl text-[#F79009]"></i>
                                <div>
                                    <p className="text-2xl font-bold text-[#F79009]">{warningIssues.length}</p>
                                    <p className="text-sm text-[var(--admin-text-dim)]">Warnings</p>
                                </div>
                            </div>
                        </div>
                        <div className="p-4 bg-[#2F6BFF]/10 border border-[#2F6BFF]/30 rounded-xl">
                            <div className="flex items-center gap-3">
                                <i className="bi bi-info-circle-fill text-2xl text-[#5B8AFF]"></i>
                                <div>
                                    <p className="text-2xl font-bold text-[#5B8AFF]">{infoIssues.length}</p>
                                    <p className="text-sm text-[var(--admin-text-dim)]">Opportunities</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {issues.length > 0 ? (
                        <div className="space-y-3">
                            <h4 className="font-semibold text-[var(--admin-text)]">Top Issues</h4>
                            {issues.slice(0, 10).map((issue, index) => (
                                <div key={index} className="p-4 bg-[var(--admin-surface-2)] border border-[var(--admin-border)] rounded-xl hover:border-[var(--admin-hover-border)] transition-colors">
                                    <div className="flex items-start gap-3">
                                        <span className={`mt-0.5 px-2 py-1 rounded-md text-xs font-medium ${
                                            issue.severity === 'critical' 
                                                ? 'bg-[#F04438]/10 text-[#F04438]'
                                                : issue.severity === 'warning'
                                                    ? 'bg-[#F79009]/10 text-[#F79009]'
                                                    : 'bg-[#2F6BFF]/10 text-[#5B8AFF]'
                                        }`}>
                                            {issue.severity?.toUpperCase() || 'INFO'}
                                        </span>
                                        <div className="flex-1">
                                            <h5 className="font-semibold text-[var(--admin-text)] mb-1">{issue.title || 'Issue'}</h5>
                                            <p className="text-sm text-[var(--admin-text-dim)]">{issue.description || 'No description available'}</p>
                                            {issue.affected_count && (
                                                <p className="text-xs text-[var(--admin-text-muted)] mt-2">
                                                    Affects {issue.affected_count} {issue.affected_count === 1 ? 'page' : 'pages'}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="text-center py-8">
                            <i className="bi bi-check-circle text-4xl text-[#12B76A] mb-3"></i>
                            <p className="text-[var(--admin-text-dim)]">No issues found! Your site looks great.</p>
                        </div>
                    )}
                </Card>
            </>
        );
    }
}
