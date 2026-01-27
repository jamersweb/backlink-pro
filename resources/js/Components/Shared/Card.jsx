export default function Card({ children, className = '', title, footer, variant = 'default' }) {
    const baseClasses = 'overflow-hidden rounded-xl transition-all duration-300';
    
    const variants = {
        default: 'bg-[#111827] border border-white/10 hover:border-white/20',
        elevated: 'bg-gradient-to-br from-[#111827] to-[#1F2937] border border-white/10 hover:shadow-xl hover:shadow-black/30',
        bordered: 'bg-[#111827]/50 border-2 border-white/10',
        ghost: 'bg-transparent',
    };

    return (
        <div className={`${baseClasses} ${variants[variant] || variants.default} ${className}`}>
            {title && (
                <div className="px-6 py-4 border-b border-white/10 bg-white/[0.02]">
                    <h3 className="text-lg font-semibold text-[#E5E7EB]">{title}</h3>
                </div>
            )}
            <div className="px-6 py-5">
                {children}
            </div>
            {footer && (
                <div className="px-6 py-4 border-t border-white/10 bg-white/[0.02]">
                    {footer}
                </div>
            )}
        </div>
    );
}
