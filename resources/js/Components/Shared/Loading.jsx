/**
 * Loading Component
 * Various loading indicators and skeletons
 */

/**
 * Spinner - Circular loading indicator
 */
export default function Loading({
    size = 'md',
    color = 'gray',
    className = '',
}) {
    const sizes = {
        xs: 'h-3 w-3',
        sm: 'h-4 w-4',
        md: 'h-6 w-6',
        lg: 'h-8 w-8',
        xl: 'h-12 w-12',
    };

    const colors = {
        gray: 'text-gray-600',
        primary: 'text-gray-900',
        white: 'text-white',
        blue: 'text-blue-600',
        green: 'text-green-600',
        red: 'text-red-600',
    };

    return (
        <svg
            className={`animate-spin ${sizes[size]} ${colors[color]} ${className}`}
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
        >
            <circle
                className="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                strokeWidth="4"
            />
            <path
                className="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            />
        </svg>
    );
}

/**
 * LoadingOverlay - Full-page or container loading overlay
 */
export function LoadingOverlay({ message = 'Loading...', transparent = false }) {
    return (
        <div className={`absolute inset-0 flex items-center justify-center z-50 ${transparent ? 'bg-white/50' : 'bg-white/80'}`}>
            <div className="text-center">
                <Loading size="xl" />
                {message && (
                    <p className="mt-4 text-sm text-gray-600">{message}</p>
                )}
            </div>
        </div>
    );
}

/**
 * LoadingButton - Button with loading state
 */
export function LoadingButton({
    loading = false,
    children,
    loadingText = 'Loading...',
    className = '',
    disabled = false,
    ...props
}) {
    return (
        <button
            className={`inline-flex items-center justify-center ${className}`}
            disabled={loading || disabled}
            {...props}
        >
            {loading ? (
                <>
                    <Loading size="sm" className="mr-2" />
                    {loadingText}
                </>
            ) : (
                children
            )}
        </button>
    );
}

/**
 * Skeleton - Loading placeholder for content
 */
export function Skeleton({
    variant = 'text',
    width,
    height,
    className = '',
    lines = 1,
}) {
    const variants = {
        text: 'h-4 rounded',
        title: 'h-6 rounded',
        avatar: 'h-10 w-10 rounded-full',
        thumbnail: 'h-20 w-20 rounded-lg',
        card: 'h-48 rounded-lg',
        button: 'h-10 w-24 rounded-lg',
    };

    const baseClass = `bg-gray-200 animate-pulse ${variants[variant] || variants.text}`;

    if (variant === 'text' && lines > 1) {
        return (
            <div className={`space-y-2 ${className}`}>
                {[...Array(lines)].map((_, i) => (
                    <div
                        key={i}
                        className={baseClass}
                        style={{
                            width: i === lines - 1 ? '75%' : '100%',
                        }}
                    />
                ))}
            </div>
        );
    }

    return (
        <div
            className={`${baseClass} ${className}`}
            style={{
                width: width,
                height: height,
            }}
        />
    );
}

/**
 * TableSkeleton - Loading placeholder for tables
 */
export function TableSkeleton({ rows = 5, columns = 4 }) {
    return (
        <div className="bg-white border border-gray-200 rounded-lg overflow-hidden">
            <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            {[...Array(columns)].map((_, i) => (
                                <th key={i} className="px-6 py-3">
                                    <Skeleton variant="text" width="80%" />
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {[...Array(rows)].map((_, rowIndex) => (
                            <tr key={rowIndex}>
                                {[...Array(columns)].map((_, colIndex) => (
                                    <td key={colIndex} className="px-6 py-4">
                                        <Skeleton variant="text" />
                                    </td>
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

/**
 * CardSkeleton - Loading placeholder for cards
 */
export function CardSkeleton({ withImage = false, lines = 3 }) {
    return (
        <div className="bg-white border border-gray-200 rounded-lg p-6 space-y-4">
            {withImage && <Skeleton variant="thumbnail" className="w-full h-32" />}
            <Skeleton variant="title" width="60%" />
            <Skeleton variant="text" lines={lines} />
        </div>
    );
}

/**
 * PageSkeleton - Full page loading skeleton
 */
export function PageSkeleton() {
    return (
        <div className="space-y-6">
            <div className="flex justify-between items-center">
                <Skeleton variant="title" width="200px" />
                <Skeleton variant="button" />
            </div>
            <CardSkeleton lines={2} />
            <TableSkeleton rows={5} columns={4} />
        </div>
    );
}
