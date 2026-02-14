import AdminLayout from '../../Components/Layout/AdminLayout';
import Card from '../../Components/Shared/Card';
import { useState } from 'react';
import { router } from '@inertiajs/react';

export default function AuditReport() {
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

    // Handle form submission
    const handleSubmit = async (e) => {
        e.preventDefault();
        
        if (!validateForm()) {
            return;
        }
        
        setIsLoading(true);
        setStatus('Starting audit...');
        
        try {
            // Make API call
            const response = await fetch('/admin/audit-report/run', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify(formData),
            });
            
            const data = await response.json();
            
            if (data.success) {
                console.log('Audit queued:', data);
                setStatus('Audit queued successfully! ✓');
                
                // Reset form after 2 seconds
                setTimeout(() => {
                    setFormData({ url: '', email: '', send_to_email: true });
                    setStatus('');
                    setIsLoading(false);
                }, 2000);
            } else {
                setStatus('Error: Failed to queue audit');
                setIsLoading(false);
            }
        } catch (error) {
            console.error('Error submitting audit:', error);
            setStatus('Error: Failed to queue audit');
            setIsLoading(false);
        }
    };

    return (
        <AdminLayout header="Audit Report">
            <div className="space-y-6 max-w-7xl mx-auto">
                {/* Header Card */}
                <div className="relative overflow-hidden rounded-2xl bg-[var(--admin-surface)] border border-[var(--admin-border)] p-8 shadow-[var(--admin-shadow-md)]">
                    {/* Background decoration - only visible in dark mode */}
                    <div className="absolute top-0 right-0 w-64 h-64 bg-[#2F6BFF]/10 rounded-full blur-3xl -mr-32 -mt-32 dark:opacity-100 opacity-0"></div>
                    <div className="absolute bottom-0 left-0 w-48 h-48 bg-[#B6F400]/10 rounded-full blur-3xl -ml-24 -mb-24 dark:opacity-100 opacity-0"></div>
                    
                    <div className="relative flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                        <div>
                            <h2 className="text-2xl font-bold text-[var(--admin-text)] mb-2">Audit Report</h2>
                            <p className="text-[var(--admin-text-muted)]">Enter website details to generate an SEO audit report.</p>
                        </div>
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
                    </div>
                </div>

                {/* Status Message */}
                {status && (
                    <div className={`p-4 rounded-xl border ${
                        status.includes('successfully') 
                            ? 'bg-[#12B76A]/10 border-[#12B76A]/30 text-[#12B76A]'
                            : status.includes('Error')
                                ? 'bg-[#F04438]/10 border-[#F04438]/30 text-[#F04438]'
                                : 'bg-[#2F6BFF]/10 border-[#2F6BFF]/30 text-[#5B8AFF]'
                    } flex items-center gap-3`}>
                        <i className={`bi ${
                            status.includes('successfully') ? 'bi-check-circle-fill' :
                            status.includes('Error') ? 'bi-x-circle-fill' :
                            'bi-info-circle-fill'
                        } text-lg`}></i>
                        <span className="font-medium">{status}</span>
                    </div>
                )}

                {/* Main Content Grid */}
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
            </div>
        </AdminLayout>
    );
}
