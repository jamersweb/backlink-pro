import { Link } from '@inertiajs/react';

/**
 * Pagination Component
 * A flexible pagination component for Laravel paginated data
 */
export default function Pagination({
    links = [],
    from = 0,
    to = 0,
    total = 0,
    showInfo = true,
    className = '',
    size = 'md',
}) {
    // Don't render if there's only one page or no links
    if (!links || links.length <= 3) {
        return null;
    }

    const sizes = {
        sm: 'px-2 py-1 text-xs',
        md: 'px-3 py-2 text-sm',
        lg: 'px-4 py-2.5 text-base',
    };

    return (
        <div className={`flex flex-col sm:flex-row items-center justify-between gap-4 ${className}`}>
            {showInfo && (
                <div className="text-sm text-gray-700">
                    Showing <span className="font-medium">{from}</span> to{' '}
                    <span className="font-medium">{to}</span> of{' '}
                    <span className="font-medium">{total}</span> results
                </div>
            )}
            <nav className="flex flex-wrap gap-1" aria-label="Pagination">
                {links.map((link, index) => {
                    // Skip the first (Previous) and last (Next) for rendering separately
                    if (index === 0) {
                        return (
                            <PaginationLink
                                key="prev"
                                href={link.url}
                                disabled={!link.url}
                                size={size}
                            >
                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                                </svg>
                                <span className="sr-only">Previous</span>
                            </PaginationLink>
                        );
                    }

                    if (index === links.length - 1) {
                        return (
                            <PaginationLink
                                key="next"
                                href={link.url}
                                disabled={!link.url}
                                size={size}
                            >
                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                                </svg>
                                <span className="sr-only">Next</span>
                            </PaginationLink>
                        );
                    }

                    // Handle ellipsis
                    if (link.label === '...') {
                        return (
                            <span
                                key={`ellipsis-${index}`}
                                className={`${sizes[size]} text-gray-500 flex items-center`}
                            >
                                ...
                            </span>
                        );
                    }

                    return (
                        <PaginationLink
                            key={index}
                            href={link.url}
                            active={link.active}
                            size={size}
                        >
                            {link.label}
                        </PaginationLink>
                    );
                })}
            </nav>
        </div>
    );
}

/**
 * Individual pagination link
 */
function PaginationLink({
    href,
    active = false,
    disabled = false,
    size = 'md',
    children,
}) {
    const sizes = {
        sm: 'px-2 py-1 text-xs min-w-[28px]',
        md: 'px-3 py-2 text-sm min-w-[36px]',
        lg: 'px-4 py-2.5 text-base min-w-[44px]',
    };

    const baseClasses = `${sizes[size]} font-medium rounded-md transition-colors flex items-center justify-center`;

    if (disabled) {
        return (
            <span className={`${baseClasses} text-gray-300 cursor-not-allowed`}>
                {children}
            </span>
        );
    }

    if (active) {
        return (
            <span className={`${baseClasses} bg-gray-900 text-white`}>
                {children}
            </span>
        );
    }

    return (
        <Link
            href={href}
            className={`${baseClasses} bg-white text-gray-700 hover:bg-gray-100 border border-gray-300`}
            preserveScroll
        >
            {children}
        </Link>
    );
}

/**
 * Simple Pagination - Just Previous/Next buttons
 */
export function SimplePagination({
    prevUrl,
    nextUrl,
    currentPage,
    totalPages,
    className = '',
}) {
    return (
        <div className={`flex items-center justify-between ${className}`}>
            <Link
                href={prevUrl || '#'}
                className={`inline-flex items-center px-4 py-2 text-sm font-medium rounded-md ${
                    prevUrl
                        ? 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50'
                        : 'text-gray-300 bg-gray-100 cursor-not-allowed'
                }`}
                preserveScroll
            >
                <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                </svg>
                Previous
            </Link>
            <span className="text-sm text-gray-700">
                Page <span className="font-medium">{currentPage}</span> of{' '}
                <span className="font-medium">{totalPages}</span>
            </span>
            <Link
                href={nextUrl || '#'}
                className={`inline-flex items-center px-4 py-2 text-sm font-medium rounded-md ${
                    nextUrl
                        ? 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50'
                        : 'text-gray-300 bg-gray-100 cursor-not-allowed'
                }`}
                preserveScroll
            >
                Next
                <svg className="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                </svg>
            </Link>
        </div>
    );
}

/**
 * Load More Button - Alternative to pagination
 */
export function LoadMore({
    onClick,
    loading = false,
    hasMore = true,
    loadedCount,
    totalCount,
    className = '',
}) {
    if (!hasMore) {
        return (
            <p className={`text-center text-sm text-gray-500 ${className}`}>
                All {totalCount} items loaded
            </p>
        );
    }

    return (
        <div className={`text-center ${className}`}>
            <button
                onClick={onClick}
                disabled={loading}
                className="inline-flex items-center px-6 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                {loading ? (
                    <>
                        <svg className="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                        </svg>
                        Loading...
                    </>
                ) : (
                    <>
                        Load More
                        {totalCount && (
                            <span className="ml-2 text-gray-400">
                                ({loadedCount}/{totalCount})
                            </span>
                        )}
                    </>
                )}
            </button>
        </div>
    );
}
