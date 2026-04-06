import { Link } from '@inertiajs/react';

export default function Button({ 
    type = 'button', 
    className = '', 
    variant = 'primary',
    size = 'md',
    href,
    method = 'get',
    as = 'button',
    disabled = false,
    children,
    ...props 
}) {
    const baseClasses = 'inline-flex items-center justify-center font-semibold rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-[var(--admin-bg)] transition-all duration-200';
    
    const sizes = {
        xs: 'px-2.5 py-1 text-xs',
        sm: 'px-3 py-1.5 text-sm',
        md: 'px-5 py-2.5 text-sm',
        lg: 'px-6 py-3 text-base',
    };

    const variants = {
        primary: 'text-white bg-gradient-to-r from-[var(--admin-primary)] to-[var(--admin-primary-hover)] hover:from-[var(--admin-primary-hover)] hover:to-[var(--admin-primary-hover)] focus:ring-[var(--admin-primary)] shadow-lg hover:shadow-xl',
        secondary: 'text-[var(--admin-text)] bg-[var(--admin-surface-2)] hover:bg-[var(--admin-surface-3)] focus:ring-[var(--admin-primary)] border border-[var(--admin-border)]',
        danger: 'text-white bg-gradient-to-r from-[#F04438] to-[#DC2626] hover:from-[#DC2626] hover:to-[#B91C1C] focus:ring-[#F04438] shadow-lg shadow-[#F04438]/20',
        success: 'text-white bg-gradient-to-r from-[#12B76A] to-[#0D9458] hover:from-[#0D9458] hover:to-[#0A7A48] focus:ring-[#12B76A] shadow-lg shadow-[#12B76A]/20',
        warning: 'text-white bg-gradient-to-r from-[#F79009] to-[#D97706] hover:from-[#D97706] hover:to-[#B45309] focus:ring-[#F79009] shadow-lg shadow-[#F79009]/20',
        lime: 'text-[var(--admin-bg)] bg-gradient-to-r from-[#B6F400] to-[#A3DB00] hover:from-[#A3DB00] hover:to-[#8BC400] focus:ring-[#B6F400] shadow-lg shadow-[#B6F400]/20',
        outline: 'text-[var(--admin-text)] bg-transparent border border-[var(--admin-border)] hover:bg-[var(--admin-hover-bg)] hover:border-[var(--admin-hover-border)] focus:ring-[var(--admin-primary)]',
        ghost: 'text-[var(--admin-text-muted)] bg-transparent hover:bg-[var(--admin-hover-bg)] hover:text-[var(--admin-text)] focus:ring-[var(--admin-primary)]',
        white: 'text-[#0F172A] bg-white hover:bg-gray-100 focus:ring-white shadow-lg',
    };

    const classes = `${baseClasses} ${sizes[size] || sizes.md} ${variants[variant]} ${disabled ? 'opacity-50 cursor-not-allowed' : ''} ${className}`;

    if (href) {
        return (
            <Link href={href} method={method} className={classes} {...props}>
                {children}
            </Link>
        );
    }

    if (as === 'a') {
        return (
            <a href={href} className={classes} {...props}>
                {children}
            </a>
        );
    }

    return (
        <button type={type} className={classes} disabled={disabled} {...props}>
            {children}
        </button>
    );
}
