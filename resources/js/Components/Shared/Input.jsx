export default function Input({ 
    type = 'text',
    name,
    id,
    value,
    label,
    error,
    required = false,
    className = '',
    ...props 
}) {
    const inputId = id || name;
    
    return (
        <div className="mb-5">
            {label && (
                <label htmlFor={inputId} className="block text-sm font-semibold text-gray-700 mb-2">
                    {label}
                    {required && <span className="text-red-500 ml-1">*</span>}
                </label>
            )}
            <input
                type={type}
                name={name}
                id={inputId}
                value={value || ''}
                className={`block w-full h-12 px-4 py-3 rounded-lg border-2 transition-all duration-200 shadow-sm focus:shadow-md focus:outline-none text-base ${
                    error 
                        ? 'border-red-400 focus:border-red-500 focus:ring-2 focus:ring-red-200' 
                        : 'border-gray-300 focus:border-green-500 focus:ring-2 focus:ring-green-200'
                } ${className}`}
                {...props}
            />
            {error && (
                <p className="mt-2 text-sm text-red-600 flex items-center gap-1">
                    <svg className="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                    </svg>
                    {error}
                </p>
            )}
        </div>
    );
}

