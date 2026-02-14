export default function Card({ children, className = '', title, footer, variant = 'default' }) {
    const baseClasses = 'overflow-hidden rounded-xl transition-all duration-300';
    
    const variants = {
        default: 'bg-[var(--admin-surface)] border border-[var(--admin-border)] hover:border-[var(--admin-hover-border)]',
        elevated: 'bg-gradient-to-br from-[var(--admin-surface)] to-[var(--admin-surface-2)] border border-[var(--admin-border)] hover:shadow-xl hover:shadow-[var(--admin-shadow-md)]',
        bordered: 'bg-[var(--admin-surface)] border-2 border-[var(--admin-border)]',
        ghost: 'bg-transparent',
    };

    return (
        <div className={`${baseClasses} ${variants[variant] || variants.default} ${className}`}>
            {title && (
                <div className="px-6 py-4 border-b border-[var(--admin-border)] bg-[var(--admin-hover-bg)]">
                    <h3 className="text-lg font-semibold text-[var(--admin-text)]">{title}</h3>
                </div>
            )}
            <div className="px-6 py-5">
                {children}
            </div>
            {footer && (
                <div className="px-6 py-4 border-t border-[var(--admin-border)] bg-[var(--admin-hover-bg)]">
                    {footer}
                </div>
            )}
        </div>
    );
}
