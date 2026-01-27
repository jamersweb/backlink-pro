export default function Input({ 
    type = 'text',
    name,
    id,
    value,
    label,
    error,
    helpText,
    required = false,
    className = '',
    icon,
    variant = 'dark', // 'dark' or 'light'
    ...props 
}) {
    const inputId = id || name;
    
    const variants = {
        dark: {
            wrapper: '',
            label: 'text-[#E5E7EB]',
            input: `bg-[#0B0F14] border-white/10 text-white placeholder-[#6B7280] focus:border-[#2F6BFF] focus:ring-[#2F6BFF]/20`,
            error: 'text-[#F04438]',
            help: 'text-[#6B7280]',
        },
        light: {
            wrapper: '',
            label: 'text-gray-700',
            input: 'bg-white border-gray-300 text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-blue-200',
            error: 'text-red-600',
            help: 'text-gray-500',
        },
    };

    const style = variants[variant] || variants.dark;
    
    return (
        <div className={`mb-5 ${style.wrapper}`}>
            {label && (
                <label htmlFor={inputId} className={`block text-sm font-medium mb-2 ${style.label}`}>
                    {label}
                    {required && <span className="text-[#F04438] ml-1">*</span>}
                </label>
            )}
            <div className="relative">
                {icon && (
                    <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        {icon}
                    </div>
                )}
                <input
                    type={type}
                    name={name}
                    id={inputId}
                    value={value || ''}
                    className={`block w-full ${icon ? 'pl-12' : 'px-4'} py-3 rounded-xl border transition-all duration-200 focus:outline-none focus:ring-2 text-base ${
                        error 
                            ? 'border-[#F04438] focus:border-[#F04438] focus:ring-[#F04438]/20' 
                            : style.input
                    } ${className}`}
                    {...props}
                />
            </div>
            {error && (
                <p className={`mt-2 text-sm flex items-center gap-1.5 ${style.error}`}>
                    <svg className="h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                    </svg>
                    {error}
                </p>
            )}
            {helpText && !error && (
                <p className={`mt-2 text-sm ${style.help}`}>
                    {helpText}
                </p>
            )}
        </div>
    );
}
