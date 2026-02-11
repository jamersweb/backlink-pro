import { useState, useEffect } from 'react';
import { Link, usePage } from '@inertiajs/react';

export default function AppLayout({ children, header, flush = false, bodyClass = '' }) {
    const { auth } = usePage().props;
    const { url } = usePage();
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [profileDropdownOpen, setProfileDropdownOpen] = useState(false);
    
    // Close dropdowns when clicking outside
    useEffect(() => {
        const handleClickOutside = (event) => {
            if (profileDropdownOpen && !event.target.closest('.profile-dropdown')) {
                setProfileDropdownOpen(false);
            }
        };
        if (profileDropdownOpen) {
            document.addEventListener('click', handleClickOutside);
        }
        return () => document.removeEventListener('click', handleClickOutside);
    }, [profileDropdownOpen]);

    const isActive = (path) => url === path || url.startsWith(`${path}/`);

    useEffect(() => {
        if (!bodyClass) return undefined;
        document.body.classList.add(bodyClass);
        return () => {
            document.body.classList.remove(bodyClass);
        };
    }, [bodyClass]);

    const navLinkClass = (active) =>
        [
            'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
            active
                ? 'border-indigo-500 text-gray-900'
                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
        ].join(' ');

    const pageShellClass = flush ? 'min-h-screen bg-white' : 'min-h-screen bg-gray-100';
    const headerContainerClass = flush
        ? 'w-full px-4 sm:px-6 lg:px-8'
        : 'max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8';
    const mainContainerClass = flush
        ? 'w-full'
        : 'max-w-7xl mx-auto py-6 sm:px-6 lg:px-8';

    return (
        <div className={pageShellClass}>
            {/* Navigation */}
            <nav className="bg-white border-b border-gray-200">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex justify-between h-16">
                        <div className="flex">
                            <div className="flex-shrink-0 flex items-center">
                                <Link href="/dashboard" className="text-xl font-bold text-gray-900">
                                    Backlink Pro
                                </Link>
                            </div>
                            <div className="hidden sm:ml-6 sm:flex sm:space-x-8">
                                <Link
                                    href="/dashboard"
                                    className={navLinkClass(isActive('/dashboard'))}
                                >
                                    Dashboard
                                </Link>
                                <Link
                                    href="/campaign"
                                    className={navLinkClass(isActive('/campaign'))}
                                >
                                    Campaigns
                                </Link>
                                <Link
                                    href="/domains"
                                    className={navLinkClass(isActive('/domains'))}
                                >
                                    Domains
                                </Link>
                                <Link
                                    href="/site-accounts"
                                    className={navLinkClass(isActive('/site-accounts'))}
                                >
                                    Site Accounts
                                </Link>
                                <Link
                                    href="/gmail"
                                    className={navLinkClass(isActive('/gmail'))}
                                >
                                    Gmail
                                </Link>
                                <Link
                                    href="/activity"
                                    className={navLinkClass(isActive('/activity'))}
                                >
                                    Activity
                                </Link>
                                <Link
                                    href="/reports"
                                    className={navLinkClass(isActive('/reports'))}
                                >
                                    Reports
                                </Link>
                                <Link
                                    href="/Backlink/auditreport"
                                    className={navLinkClass(isActive('/Backlink/auditreport'))}
                                >
                                    Audit Report
                                </Link>
                                <Link
                                    href="/settings"
                                    className={navLinkClass(isActive('/settings'))}
                                >
                                    Settings
                                </Link>
                            </div>
                        </div>
                        <div className="flex items-center space-x-4">
                            {/* Profile Dropdown */}
                            <div className="relative profile-dropdown">
                                <button
                                    onClick={() => setProfileDropdownOpen(!profileDropdownOpen)}
                                    className="flex items-center space-x-3 focus:outline-none focus:ring-2 focus:ring-indigo-300 rounded-lg p-1"
                                >
                                    <div className="h-10 w-10 rounded-full bg-indigo-600 flex items-center justify-center text-white font-bold shadow-lg hover:bg-indigo-700 transition-colors">
                                        {auth?.user?.name?.charAt(0).toUpperCase() || 'A'}
                                    </div>
                                    <span className="text-sm font-medium text-gray-700 hidden md:block">{auth?.user?.name}</span>
                                    <svg className="h-5 w-5 text-gray-700 hidden md:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                {profileDropdownOpen && (
                                    <div className="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl border-2 border-indigo-200 py-2 z-50">
                                        <div className="px-4 py-3 border-b border-gray-200">
                                            <p className="text-sm font-semibold text-gray-900">{auth?.user?.name}</p>
                                            <p className="text-sm text-gray-500">{auth?.user?.email}</p>
                                        </div>
                                        <Link
                                            href="/profile"
                                            className="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors"
                                            onClick={() => setProfileDropdownOpen(false)}
                                        >
                                            👤 View Profile
                                        </Link>
                                        <Link
                                            href="/logout"
                                            method="post"
                                            className="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors"
                                            onClick={() => setProfileDropdownOpen(false)}
                                        >
                                            🚪 Logout
                                        </Link>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            {/* Page Header */}
            {header && (
                <header className="bg-white shadow">
                    <div className={headerContainerClass}>
                        <h2 className="text-3xl font-bold text-gray-900">{header}</h2>
                    </div>
                </header>
            )}

            {/* Page Content */}
            <main className={mainContainerClass}>
                {children}
            </main>
        </div>
    );
}
