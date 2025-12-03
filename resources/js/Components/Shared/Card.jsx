export default function Card({ children, className = '', title, footer }) {
    return (
        <div className={`bg-white overflow-hidden shadow-lg rounded-xl border border-gray-100 hover:shadow-xl transition-all duration-300 ${className}`}>
            {title && (
                <div className="px-6 py-4 bg-gradient-to-r from-gray-50 to-white border-b-2 border-gray-200">
                    <h3 className="text-xl font-bold text-gray-900">{title}</h3>
                </div>
            )}
            <div className="px-6 py-6">
                {children}
            </div>
            {footer && (
                <div className="px-6 py-4 border-t-2 border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                    {footer}
                </div>
            )}
        </div>
    );
}

