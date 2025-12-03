import { Link, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';

export default function AdminLayout({ children, header }) {
    const { auth } = usePage().props;
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [leadsDropdownOpen, setLeadsDropdownOpen] = useState(false);
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
        <div className="min-h-screen bg-gradient-to-br from-gray-50 via-white to-gray-50">
            {/* Navigation */}
            <nav className="bg-gradient-to-r from-red-600 to-red-700 shadow-lg border-b-4 border-green-500">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex justify-between h-20">
                        <div className="flex">
                            <div className="flex-shrink-0 flex items-center">
                                <Link href="/admin/dashboard" className="text-2xl font-bold text-white hover:text-green-300 transition-colors duration-200">
                                    <span className="bg-green-500 px-3 py-1 rounded-lg mr-2">⚡</span>
                                    Admin Panel
                                </Link>
                            </div>
                            <div className="hidden sm:ml-8 sm:flex sm:space-x-6 items-center">
                                <Link
                                    href="/admin/dashboard"
                                    className={`border-b-2 ${currentUrl === '/admin/dashboard' ? 'border-green-400' : 'border-transparent'} text-white hover:text-green-300 hover:border-green-300 inline-flex items-center px-3 py-2 text-sm font-semibold transition-all duration-200`}
                                >
                                    Dashboard
                                </Link>
                                
                                {/* Leads Dropdown */}
                                <div className="relative" onMouseEnter={() => setLeadsDropdownOpen(true)} onMouseLeave={() => setLeadsDropdownOpen(false)}>
                                    <button
                                        className={`border-b-2 ${isLeadsActive ? 'border-green-400' : 'border-transparent'} text-white hover:text-green-300 hover:border-green-300 inline-flex items-center px-3 py-2 text-sm font-semibold transition-all duration-200`}
                                    >
                                        Leads
                                        <svg className="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                    {leadsDropdownOpen && (
                                        <div className="absolute top-full left-0 mt-2 w-56 bg-white rounded-lg shadow-xl border-2 border-green-200 py-2 z-50">
                                            <Link
                                                href="/admin/leads/verified"
                                                className="block px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-red-50 hover:to-green-50 hover:text-red-600 transition-colors"
                                            >
                                                ✓ Verified Users
                                            </Link>
                                            <Link
                                                href="/admin/leads/non-verified"
                                                className="block px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-red-50 hover:to-green-50 hover:text-red-600 transition-colors"
                                            >
                                                ✗ Non-Verified Users
                                            </Link>
                                            <Link
                                                href="/admin/leads/purchase"
                                                className="block px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-red-50 hover:to-green-50 hover:text-red-600 transition-colors"
                                            >
                                                💳 Purchase Users
                                            </Link>
                                        </div>
                                    )}
                                </div>
                                
                                <Link
                                    href="/admin/users"
                                    className={`border-b-2 ${currentUrl === '/admin/users' ? 'border-green-400' : 'border-transparent'} text-white hover:text-green-300 hover:border-green-300 inline-flex items-center px-3 py-2 text-sm font-semibold transition-all duration-200`}
                                >
                                    Users
                                </Link>
                                <Link
                                    href="/admin/plans"
                                    className={`border-b-2 ${currentUrl === '/admin/plans' ? 'border-green-400' : 'border-transparent'} text-white hover:text-green-300 hover:border-green-300 inline-flex items-center px-3 py-2 text-sm font-semibold transition-all duration-200`}
                                >
                                    Plans
                                </Link>
                            </div>
                        </div>
                        <div className="flex items-center space-x-4">
                            {/* Profile Dropdown */}
                            <div className="relative">
                                <button
                                    onClick={() => setProfileDropdownOpen(!profileDropdownOpen)}
                                    className="flex items-center space-x-3 focus:outline-none focus:ring-2 focus:ring-green-300 rounded-lg p-1"
                                >
                                    <div className="h-10 w-10 rounded-full bg-green-500 flex items-center justify-center text-white font-bold shadow-lg hover:bg-green-600 transition-colors">
                                        {auth?.user?.name?.charAt(0).toUpperCase() || 'A'}
                                    </div>
                                    <span className="text-sm font-medium text-white hidden md:block">{auth?.user?.name}</span>
                                    <svg className="h-5 w-5 text-white hidden md:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                {profileDropdownOpen && (
                                    <div className="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl border-2 border-green-200 py-2 z-50">
                                        <div className="px-4 py-3 border-b border-gray-200">
                                            <p className="text-sm font-semibold text-gray-900">{auth?.user?.name}</p>
                                            <p className="text-sm text-gray-500">{auth?.user?.email}</p>
                                        </div>
                                        <Link
                                            href="/profile"
                                            className="block px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-red-50 hover:to-green-50 hover:text-red-600 transition-colors"
                                            onClick={() => setProfileDropdownOpen(false)}
                                        >
                                            👤 View Profile
                                        </Link>
                                        <Link
                                            href="/logout"
                                            method="post"
                                            className="block px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-red-50 hover:to-green-50 hover:text-red-600 transition-colors"
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
                <header className="bg-gradient-to-r from-green-500 to-green-600 shadow-xl">
                    <div className="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
                        <h2 className="text-4xl font-bold text-white drop-shadow-lg">{header}</h2>
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

