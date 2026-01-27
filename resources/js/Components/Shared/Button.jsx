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
    const baseClasses = 'inline-flex items-center justify-center font-semibold rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-[#0B0F14] transition-all duration-200';
    
    const sizes = {
        xs: 'px-2.5 py-1 text-xs',
        sm: 'px-3 py-1.5 text-sm',
        md: 'px-5 py-2.5 text-sm',
        lg: 'px-6 py-3 text-base',
    };

    const variants = {
        primary: 'text-white bg-gradient-to-r from-[#2F6BFF] to-[#2457D6] hover:from-[#2457D6] hover:to-[#1E4BB8] focus:ring-[#2F6BFF] shadow-lg shadow-[#2F6BFF]/20 hover:shadow-xl hover:shadow-[#2F6BFF]/30',
        secondary: 'text-[#E5E7EB] bg-[#1F2937] hover:bg-[#374151] focus:ring-[#2F6BFF] border border-white/10',
        danger: 'text-white bg-gradient-to-r from-[#F04438] to-[#DC2626] hover:from-[#DC2626] hover:to-[#B91C1C] focus:ring-[#F04438] shadow-lg shadow-[#F04438]/20',
        success: 'text-white bg-gradient-to-r from-[#12B76A] to-[#0D9458] hover:from-[#0D9458] hover:to-[#0A7A48] focus:ring-[#12B76A] shadow-lg shadow-[#12B76A]/20',
        warning: 'text-white bg-gradient-to-r from-[#F79009] to-[#D97706] hover:from-[#D97706] hover:to-[#B45309] focus:ring-[#F79009] shadow-lg shadow-[#F79009]/20',
        lime: 'text-[#0B0F14] bg-gradient-to-r from-[#B6F400] to-[#A3DB00] hover:from-[#A3DB00] hover:to-[#8BC400] focus:ring-[#B6F400] shadow-lg shadow-[#B6F400]/20',
        outline: 'text-[#E5E7EB] bg-transparent border border-white/20 hover:bg-white/5 hover:border-white/30 focus:ring-[#2F6BFF]',
        ghost: 'text-[#9CA3AF] bg-transparent hover:bg-white/5 hover:text-white focus:ring-[#2F6BFF]',
        white: 'text-[#0B0F14] bg-white hover:bg-gray-100 focus:ring-white shadow-lg',
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
