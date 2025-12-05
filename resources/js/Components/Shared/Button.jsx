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
        primary: 'text-white bg-gray-900 hover:bg-gray-800 focus:ring-gray-900 shadow-lg hover:shadow-xl',
        secondary: 'text-gray-900 bg-gray-100 hover:bg-gray-200 focus:ring-gray-900 border-2 border-gray-300',
        danger: 'text-white bg-red-600 hover:bg-red-700 focus:ring-red-500 shadow-lg hover:shadow-xl',
        success: 'text-white bg-green-600 hover:bg-green-700 focus:ring-green-500 shadow-lg hover:shadow-xl',
        outline: 'text-gray-900 bg-white border-2 border-gray-900 hover:bg-gray-50 hover:border-gray-800 focus:ring-gray-900',
        white: 'text-gray-900 bg-white hover:bg-gray-100 focus:ring-gray-900 shadow-lg hover:shadow-xl',
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

