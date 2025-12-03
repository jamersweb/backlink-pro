import { Link } from '@inertiajs/react';

export default function Button({ 
    type = 'button', 
    className = '', 
    variant = 'primary',
    href,
    method = 'get',
    as = 'button',
    disabled = false,
    children,
    ...props 
}) {
    const baseClasses = 'inline-flex items-center px-6 py-3 border border-transparent text-sm font-semibold rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all duration-200 transform hover:scale-105';
    
    const variants = {
        primary: 'text-white bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 focus:ring-red-500 shadow-lg hover:shadow-xl',
        secondary: 'text-red-700 bg-red-50 hover:bg-red-100 focus:ring-red-500 border-2 border-red-200',
        danger: 'text-white bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 focus:ring-red-500 shadow-lg hover:shadow-xl',
        success: 'text-white bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 focus:ring-green-500 shadow-lg hover:shadow-xl',
        outline: 'text-gray-700 bg-white border-2 border-gray-300 hover:bg-gray-50 hover:border-red-500 focus:ring-red-500',
        white: 'text-gray-900 bg-white hover:bg-gray-100 focus:ring-gray-500 shadow-lg hover:shadow-xl',
    };

    const classes = `${baseClasses} ${variants[variant]} ${disabled ? 'opacity-50 cursor-not-allowed' : ''} ${className}`;

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

