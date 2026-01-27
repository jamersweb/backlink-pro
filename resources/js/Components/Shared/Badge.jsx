/**
 * Badge Component
 * A versatile badge/tag component for status indicators, labels, and counts
 */
export default function Badge({
    children,
    variant = 'default',
    size = 'md',
    rounded = 'md',
    dot = false,
    removable = false,
    onRemove,
    className = '',
}) {
    const variants = {
        default: 'bg-gray-100 text-gray-800',
        primary: 'bg-gray-900 text-white',
        secondary: 'bg-gray-200 text-gray-700',
        success: 'bg-green-100 text-green-800',
        warning: 'bg-yellow-100 text-yellow-800',
        danger: 'bg-red-100 text-red-800',
        info: 'bg-blue-100 text-blue-800',
        purple: 'bg-purple-100 text-purple-800',
        pink: 'bg-pink-100 text-pink-800',
        indigo: 'bg-indigo-100 text-indigo-800',
        // Outline variants
        'outline-default': 'bg-transparent border border-gray-300 text-gray-700',
        'outline-primary': 'bg-transparent border border-gray-900 text-gray-900',
        'outline-success': 'bg-transparent border border-green-500 text-green-700',
        'outline-warning': 'bg-transparent border border-yellow-500 text-yellow-700',
        'outline-danger': 'bg-transparent border border-red-500 text-red-700',
        'outline-info': 'bg-transparent border border-blue-500 text-blue-700',
    };

    const sizes = {
        xs: 'px-1.5 py-0.5 text-xs',
        sm: 'px-2 py-0.5 text-xs',
        md: 'px-2.5 py-1 text-sm',
        lg: 'px-3 py-1.5 text-sm',
    };

    const roundedStyles = {
        none: 'rounded-none',
        sm: 'rounded-sm',
        md: 'rounded-md',
        lg: 'rounded-lg',
        full: 'rounded-full',
    };

    const dotColors = {
        default: 'bg-gray-500',
        primary: 'bg-gray-900',
        secondary: 'bg-gray-500',
        success: 'bg-green-500',
        warning: 'bg-yellow-500',
        danger: 'bg-red-500',
        info: 'bg-blue-500',
        purple: 'bg-purple-500',
        pink: 'bg-pink-500',
        indigo: 'bg-indigo-500',
    };

    return (
        <span
            className={`
                inline-flex items-center font-medium
                ${variants[variant] || variants.default}
                ${sizes[size]}
                ${roundedStyles[rounded]}
                ${className}
            `}
        >
            {dot && (
                <span className={`w-1.5 h-1.5 rounded-full mr-1.5 ${dotColors[variant.replace('outline-', '')] || dotColors.default}`} />
            )}
            {children}
            {removable && (
                <button
                    type="button"
                    onClick={onRemove}
                    className="ml-1.5 -mr-0.5 h-4 w-4 rounded-full inline-flex items-center justify-center hover:bg-black/10 focus:outline-none"
                >
                    <svg className="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" />
                    </svg>
                </button>
            )}
        </span>
    );
}

/**
 * Status Badge - Pre-configured badge for common status values
 */
export function StatusBadge({ status, size = 'sm' }) {
    const statusConfig = {
        // Active/Inactive
        active: { variant: 'success', label: 'Active', dot: true },
        inactive: { variant: 'default', label: 'Inactive', dot: true },
        
        // Task/Job statuses
        pending: { variant: 'warning', label: 'Pending', dot: true },
        processing: { variant: 'info', label: 'Processing', dot: true },
        completed: { variant: 'success', label: 'Completed', dot: true },
        failed: { variant: 'danger', label: 'Failed', dot: true },
        cancelled: { variant: 'default', label: 'Cancelled', dot: true },
        
        // Subscription statuses
        trialing: { variant: 'info', label: 'Trial', dot: true },
        subscribed: { variant: 'success', label: 'Subscribed', dot: true },
        expired: { variant: 'danger', label: 'Expired', dot: true },
        
        // Verification
        verified: { variant: 'success', label: 'Verified' },
        unverified: { variant: 'warning', label: 'Unverified' },
        
        // Boolean
        yes: { variant: 'success', label: 'Yes' },
        no: { variant: 'danger', label: 'No' },
        
        // Priority
        high: { variant: 'danger', label: 'High' },
        medium: { variant: 'warning', label: 'Medium' },
        low: { variant: 'info', label: 'Low' },
        
        // Default
        default: { variant: 'default', label: status },
    };

    const config = statusConfig[status?.toLowerCase()] || statusConfig.default;

    return (
        <Badge variant={config.variant} size={size} dot={config.dot}>
            {config.label}
        </Badge>
    );
}

/**
 * Count Badge - For showing counts/numbers
 */
export function CountBadge({ count, max = 99, variant = 'primary', size = 'sm' }) {
    const displayCount = count > max ? `${max}+` : count;
    
    return (
        <Badge variant={variant} size={size} rounded="full">
            {displayCount}
        </Badge>
    );
}
