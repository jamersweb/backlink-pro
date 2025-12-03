import { Link, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';

export default function AdminLayout({ children, header }) {
    const { auth } = usePage().props;
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [leadsDropdownOpen, setLeadsDropdownOpen] = useState(false);
    const [systemDropdownOpen, setSystemDropdownOpen] = useState(false);
    const [profileDropdownOpen, setProfileDropdownOpen] = useState(false);

    const currentUrl = window.location.pathname;
    const isLeadsActive = currentUrl.startsWith('/admin/leads');

    // Close dropdowns when clicking outside
    useEffect(() => {
        const handleClickOutside = (event) => {
            if (profileDropdownOpen && !event.target.closest('.relative')) {
                setProfileDropdownOpen(false);
            }
        };
        if (profileDropdownOpen) {
            document.addEventListener('click', handleClickOutside);
        }
        return () => document.removeEventListener('click', handleClickOutside);
    }, [profileDropdownOpen]);

    return (
        <div className="min-h-screen bg-gray-50">
            {/* Navigation */}
            <nav className="bg-white shadow-md border-b border-gray-200">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex justify-between h-16">
                        <div className="flex">
                            <div className="flex-shrink-0 flex items-center">
                                <Link href="/admin/dashboard" className="text-xl font-bold text-gray-900 hover:text-gray-700 transition-colors duration-200">
                                    <span className="text-2xl mr-2">⚡</span>
                                    Admin Panel
                                </Link>
                            </div>
                            <div className="hidden sm:ml-8 sm:flex sm:space-x-4 items-center">
                                <Link
                                    href="/admin/dashboard"
                                    className={`px-3 py-2 text-sm font-medium rounded-md transition-colors ${
                                        currentUrl === '/admin/dashboard'
                                            ? 'bg-gray-100 text-gray-900'
                                            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                                    }`}
                                >
                                    Dashboard
                                </Link>

                                {/* Leads Dropdown */}
                                <div className="relative" onMouseEnter={() => setLeadsDropdownOpen(true)} onMouseLeave={() => setLeadsDropdownOpen(false)}>
                                    <button
                                        className={`px-3 py-2 text-sm font-medium rounded-md transition-colors flex items-center ${
                                            isLeadsActive
                                                ? 'bg-gray-100 text-gray-900'
                                                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                                        }`}
                                    >
                                        Leads
                                        <svg className="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                    {leadsDropdownOpen && (
                                        <div className="absolute top-full left-0 mt-1 w-56 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                                            <Link
                                                href="/admin/leads/verified"
                                                className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                                            >
                                                ✓ Verified Users
                                            </Link>
                                            <Link
                                                href="/admin/leads/non-verified"
                                                className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                                            >
                                                ✗ Non-Verified Users
                                            </Link>
                                            <Link
                                                href="/admin/leads/purchase"
                                                className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                                            >
                                                💳 Purchase Users
                                            </Link>
                                        </div>
                                    )}
                                </div>

                                <Link
                                    href="/admin/users"
                                    className={`px-3 py-2 text-sm font-medium rounded-md transition-colors ${
                                        currentUrl.startsWith('/admin/users')
                                            ? 'bg-gray-100 text-gray-900'
                                            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                                    }`}
                                >
                                    Users
                                </Link>
                                <Link
                                    href="/admin/plans"
                                    className={`px-3 py-2 text-sm font-medium rounded-md transition-colors ${
                                        currentUrl.startsWith('/admin/plans')
                                            ? 'bg-gray-100 text-gray-900'
                                            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                                    }`}
                                >
                                    Plans
                                </Link>
                                <Link
                                    href="/admin/campaigns"
                                    className={`px-3 py-2 text-sm font-medium rounded-md transition-colors ${
                                        currentUrl.startsWith('/admin/campaigns')
                                            ? 'bg-gray-100 text-gray-900'
                                            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                                    }`}
                                >
                                    Campaigns
                                </Link>
                                <Link
                                    href="/admin/backlinks"
                                    className={`px-3 py-2 text-sm font-medium rounded-md transition-colors ${
                                        currentUrl.startsWith('/admin/backlinks')
                                            ? 'bg-gray-100 text-gray-900'
                                            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                                    }`}
                                >
                                    Backlinks
                                </Link>
                                <Link
                                    href="/admin/automation-tasks"
                                    className={`px-3 py-2 text-sm font-medium rounded-md transition-colors ${
                                        currentUrl.startsWith('/admin/automation-tasks')
                                            ? 'bg-gray-100 text-gray-900'
                                            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                                    }`}
                                >
                                    Tasks
                                </Link>

                                {/* System Dropdown */}
                                <div className="relative" onMouseEnter={() => setSystemDropdownOpen(true)} onMouseLeave={() => setSystemDropdownOpen(false)}>
                                    <button
                                        className={`px-3 py-2 text-sm font-medium rounded-md transition-colors flex items-center ${
                                            currentUrl.startsWith('/admin/proxies') ||
                                            currentUrl.startsWith('/admin/captcha') ||
                                            currentUrl.startsWith('/admin/system-health') ||
                                            currentUrl.startsWith('/admin/blocked-sites')
                                                ? 'bg-gray-100 text-gray-900'
                                                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                                        }`}
                                    >
                                        System
                                        <svg className="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                    {(systemDropdownOpen || currentUrl.startsWith('/admin/proxies') ||
                                      currentUrl.startsWith('/admin/captcha') ||
                                      currentUrl.startsWith('/admin/system-health') ||
                                      currentUrl.startsWith('/admin/blocked-sites')) && (
                                        <div className="absolute top-full left-0 mt-1 w-56 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                                            <Link
                                                href="/admin/proxies"
                                                className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                                            >
                                                🔌 Proxies
                                            </Link>
                                            <Link
                                                href="/admin/captcha-logs"
                                                className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                                            >
                                                🧩 Captcha Logs
                                            </Link>
                                            <Link
                                                href="/admin/system-health"
                                                className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                                            >
                                                💚 System Health
                                            </Link>
                                            <Link
                                                href="/admin/blocked-sites"
                                                className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                                            >
                                                🚫 Blocked Sites
                                            </Link>
                                        </div>
                                    )}
                                </div>

                                <Link
                                    href="/admin/blocked-sites"
                                    className={`px-3 py-2 text-sm font-medium rounded-md transition-colors ${
                                        currentUrl.startsWith('/admin/blocked-sites')
                                            ? 'bg-gray-100 text-gray-900'
                                            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                                    }`}
                                >
                                    🚫 Blocked Sites
                                </Link>
                                <Link
                                    href="/admin/settings"
                                    className={`px-3 py-2 text-sm font-medium rounded-md transition-colors ${
                                        currentUrl.startsWith('/admin/settings')
                                            ? 'bg-gray-100 text-gray-900'
                                            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                                    }`}
                                >
                                    ⚙️ Settings
                                </Link>
                                <Link
                                    href="/admin/locations/create"
                                    className={`px-3 py-2 text-sm font-medium rounded-md transition-colors ${
                                        currentUrl.startsWith('/admin/locations')
                                            ? 'bg-gray-100 text-gray-900'
                                            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                                    }`}
                                >
                                    Locations
                                </Link>
                            </div>
                        </div>
                        <div className="flex items-center space-x-4">
                            {/* Profile Dropdown */}
                            <div className="relative">
                                <button
                                    onClick={() => setProfileDropdownOpen(!profileDropdownOpen)}
                                    className="flex items-center space-x-2 focus:outline-none focus:ring-2 focus:ring-gray-300 rounded-lg p-1"
                                >
                                    <div className="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center text-gray-700 font-bold text-sm hover:bg-gray-400 transition-colors">
                                        {auth?.user?.name?.charAt(0).toUpperCase() || 'A'}
                                    </div>
                                    <span className="text-sm font-medium text-gray-700 hidden md:block">{auth?.user?.name}</span>
                                    <svg className="h-4 w-4 text-gray-600 hidden md:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                {profileDropdownOpen && (
                                    <div className="absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-50">
                                        <div className="px-4 py-3 border-b border-gray-200">
                                            <p className="text-sm font-semibold text-gray-900">{auth?.user?.name}</p>
                                            <p className="text-sm text-gray-500">{auth?.user?.email}</p>
                                        </div>
                                        <Link
                                            href="/profile"
                                            className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                                            onClick={() => setProfileDropdownOpen(false)}
                                        >
                                            👤 View Profile
                                        </Link>
                                        <Link
                                            href="/logout"
                                            method="post"
                                            className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
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
                <header className="bg-white border-b border-gray-200 shadow-sm">
                    <div className="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        <h2 className="text-3xl font-bold text-gray-900">{header}</h2>
                    </div>
                </header>
            )}

            {/* Page Content */}
            <main className="max-w-7xl mx-auto py-8 sm:px-6 lg:px-8">
                {children}
            </main>
        </div>
    );
}

