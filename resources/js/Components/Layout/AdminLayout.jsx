import { Link, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';

export default function AdminLayout({ children, header }) {
    const { auth } = usePage().props;
    const [sidebarOpen, setSidebarOpen] = useState(true);
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

    // Close sidebar on mobile when clicking outside
    useEffect(() => {
        const handleResize = () => {
            if (window.innerWidth >= 1024) {
                setSidebarOpen(true);
            } else {
                setSidebarOpen(false);
            }
        };
        handleResize();
        window.addEventListener('resize', handleResize);
        return () => window.removeEventListener('resize', handleResize);
    }, []);

    const menuItems = [
        {
            name: 'Dashboard',
            href: '/admin/dashboard',
            icon: 'bi-speedometer2',
            active: currentUrl === '/admin/dashboard',
        },
        {
            name: 'Leads',
            icon: 'bi-people',
            active: isLeadsActive,
            children: [
                { name: 'Verified Users', href: '/admin/leads/verified', icon: 'bi-check-circle' },
                { name: 'Non-Verified', href: '/admin/leads/non-verified', icon: 'bi-x-circle' },
                { name: 'Purchase Users', href: '/admin/leads/purchase', icon: 'bi-cart-check' },
            ],
        },
        {
            name: 'Users',
            href: '/admin/users',
            icon: 'bi-person',
            active: currentUrl.startsWith('/admin/users'),
        },
        {
            name: 'Plans',
            href: '/admin/plans',
            icon: 'bi-box-seam',
            active: currentUrl.startsWith('/admin/plans'),
        },
        {
            name: 'Subscriptions',
            href: '/admin/subscriptions',
            icon: 'bi-credit-card',
            active: currentUrl.startsWith('/admin/subscriptions'),
        },
        {
            name: 'Campaigns',
            href: '/admin/campaigns',
            icon: 'bi-bullseye',
            active: currentUrl.startsWith('/admin/campaigns'),
        },
        {
            name: 'Categories',
            href: '/admin/categories',
            icon: 'bi-folder',
            active: currentUrl.startsWith('/admin/categories'),
        },
        {
            name: 'Opportunities',
            href: '/admin/backlink-opportunities',
            icon: 'bi-database',
            active: currentUrl.startsWith('/admin/backlink-opportunities'),
        },
        {
            name: 'Backlinks',
            href: '/admin/backlinks',
            icon: 'bi-link-45deg',
            active: currentUrl.startsWith('/admin/backlinks'),
        },
        {
            name: 'Tasks',
            href: '/admin/automation-tasks',
            icon: 'bi-gear',
            active: currentUrl.startsWith('/admin/automation-tasks'),
        },
        {
            name: 'System',
            icon: 'bi-server',
            active: currentUrl.startsWith('/admin/proxies') ||
                    currentUrl.startsWith('/admin/captcha') ||
                    currentUrl.startsWith('/admin/system-health') ||
                    currentUrl.startsWith('/admin/blocked-sites'),
            children: [
                { name: 'Proxies', href: '/admin/proxies', icon: 'bi-router' },
                { name: 'Captcha Logs', href: '/admin/captcha-logs', icon: 'bi-shield-check' },
                { name: 'System Health', href: '/admin/system-health', icon: 'bi-heart-pulse' },
                { name: 'Blocked Sites', href: '/admin/blocked-sites', icon: 'bi-ban' },
            ],
        },
        {
            name: 'Settings',
            href: '/admin/settings',
            icon: 'bi-sliders',
            active: currentUrl.startsWith('/admin/settings'),
        },
        {
            name: 'Locations',
            href: '/admin/locations/create',
            icon: 'bi-geo-alt',
            active: currentUrl.startsWith('/admin/locations'),
        },
    ];

    return (
        <div className="min-h-screen bg-gray-50 flex relative">
            {/* Mobile Sidebar Overlay */}
            {sidebarOpen && (
                <div
                    className="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden"
                    onClick={() => setSidebarOpen(false)}
                ></div>
            )}

            {/* Sidebar */}
            <aside className={`fixed lg:static inset-y-0 left-0 bg-white border-r border-gray-200 transition-all duration-300 ease-in-out z-50 ${
                sidebarOpen ? 'w-64 translate-x-0' : '-translate-x-full lg:translate-x-0 lg:w-20'
            } flex flex-col shadow-xl lg:shadow-none`}>
                {/* Sidebar Header */}
                <div className={`h-14 flex items-center border-b border-gray-200 ${
                    sidebarOpen ? 'justify-between px-4' : 'justify-center px-2'
                }`}>
                    {sidebarOpen ? (
                        <>
                            <Link href="/admin/dashboard" className="flex items-center space-x-2">
                                <span className="text-2xl">⚡</span>
                                <span className="text-lg font-bold text-gray-900">Admin</span>
                            </Link>
                            <button
                                onClick={() => setSidebarOpen(!sidebarOpen)}
                                className="p-1.5 rounded-lg hover:bg-gray-100 transition-colors"
                            >
                                <i className="bi bi-chevron-left text-gray-600"></i>
                            </button>
                        </>
                    ) : (
                        <button
                            onClick={() => setSidebarOpen(!sidebarOpen)}
                            className="p-1.5 rounded-lg hover:bg-gray-100 transition-colors w-full flex justify-center"
                        >
                            <span className="text-2xl">⚡</span>
                        </button>
                    )}
                </div>

                {/* Sidebar Navigation */}
                <nav className="flex-1 overflow-y-auto py-4">
                    <ul className="space-y-1 px-2">
                        {menuItems.map((item, index) => (
                            <li key={index}>
                                {item.children ? (
                                    <div>
                                        <button
                                            onClick={() => {
                                                if (item.name === 'Leads') setLeadsDropdownOpen(!leadsDropdownOpen);
                                                if (item.name === 'System') setSystemDropdownOpen(!systemDropdownOpen);
                                            }}
                                            className={`w-full flex items-center px-3 py-2.5 rounded-lg transition-colors ${
                                                item.active
                                                    ? 'bg-gray-900 text-white'
                                                    : 'text-gray-700 hover:bg-gray-100'
                                            }`}
                                        >
                                            <i className={`bi ${item.icon} text-lg ${sidebarOpen ? 'mr-3' : 'mx-auto'}`}></i>
                                            {sidebarOpen && (
                                                <>
                                                    <span className="flex-1 text-left font-medium">{item.name}</span>
                                                    <i className={`bi ${item.name === 'Leads' && leadsDropdownOpen || item.name === 'System' && systemDropdownOpen ? 'bi-chevron-up' : 'bi-chevron-down'} text-xs`}></i>
                                                </>
                                            )}
                                        </button>
                                        {sidebarOpen && ((item.name === 'Leads' && leadsDropdownOpen) || (item.name === 'System' && systemDropdownOpen)) && (
                                            <ul className="mt-1 ml-4 space-y-1">
                                                {item.children.map((child, childIndex) => (
                                                    <li key={childIndex}>
                                                        <Link
                                                            href={child.href}
                                                            className={`flex items-center px-3 py-2 rounded-lg transition-colors ${
                                                                currentUrl === child.href || currentUrl.startsWith(child.href + '/')
                                                                    ? 'bg-gray-900 text-white'
                                                                    : 'text-gray-600 hover:bg-gray-100'
                                                            }`}
                                                        >
                                                            <i className={`bi ${child.icon} text-sm mr-3`}></i>
                                                            <span className="text-sm">{child.name}</span>
                                                        </Link>
                                                    </li>
                                                ))}
                                            </ul>
                                        )}
                                    </div>
                                ) : (
                                    <Link
                                        href={item.href}
                                        className={`flex items-center px-3 py-2.5 rounded-lg transition-colors ${
                                            item.active
                                                ? 'bg-gray-900 text-white'
                                                : 'text-gray-700 hover:bg-gray-100'
                                        }`}
                                        title={!sidebarOpen ? item.name : ''}
                                    >
                                        <i className={`bi ${item.icon} text-lg ${sidebarOpen ? 'mr-3' : 'mx-auto'}`}></i>
                                        {sidebarOpen && <span className="font-medium">{item.name}</span>}
                                    </Link>
                                )}
                            </li>
                        ))}
                    </ul>
                </nav>

                {/* Sidebar Footer */}
                <div className="border-t border-gray-200 p-4">
                    <div className="relative">
                        <button
                            onClick={() => setProfileDropdownOpen(!profileDropdownOpen)}
                            className={`w-full flex items-center px-3 py-2 rounded-lg hover:bg-gray-50 transition-colors ${
                                sidebarOpen ? 'justify-start' : 'justify-center'
                            }`}
                        >
                            <div className="h-8 w-8 rounded-full bg-gray-900 flex items-center justify-center text-white font-bold text-sm">
                                {auth?.user?.name?.charAt(0).toUpperCase() || 'A'}
                            </div>
                            {sidebarOpen && (
                                <div className="ml-3 flex-1 text-left">
                                    <p className="text-sm font-medium text-gray-900">{auth?.user?.name}</p>
                                    <p className="text-xs text-gray-500 truncate">{auth?.user?.email}</p>
                                </div>
                            )}
                        </button>
                        {profileDropdownOpen && (
                            <div className="absolute bottom-full left-0 mb-2 w-full bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-50">
                                <Link
                                    href="/profile"
                                    className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                                    onClick={() => setProfileDropdownOpen(false)}
                                >
                                    <i className="bi bi-person mr-2"></i>View Profile
                                </Link>
                                <Link
                                    href="/logout"
                                    method="post"
                                    className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                                    onClick={() => setProfileDropdownOpen(false)}
                                >
                                    <i className="bi bi-box-arrow-right mr-2"></i>Logout
                                </Link>
                            </div>
                        )}
                    </div>
                </div>
            </aside>

            {/* Main Content Area */}
            <div className="flex-1 flex flex-col overflow-hidden min-w-0">
                {/* Top Header - Small */}
                <header className="h-14 bg-white border-b border-gray-200 flex items-center justify-between px-6 shadow-sm sticky top-0 z-30">
                    <div className="flex items-center space-x-4">
                        <button
                            onClick={() => setSidebarOpen(!sidebarOpen)}
                            className="lg:hidden p-2 rounded-lg hover:bg-gray-100 transition-colors"
                        >
                            <i className="bi bi-list text-xl text-gray-600"></i>
                        </button>
                        {header && (
                            <h1 className="text-lg font-semibold text-gray-900">{header}</h1>
                        )}
                    </div>
                    <div className="flex items-center space-x-4">
                        {/* Notifications or other header items can go here */}
                        <div className="h-8 w-8 rounded-full bg-gray-900 flex items-center justify-center text-white font-bold text-sm lg:hidden">
                            {auth?.user?.name?.charAt(0).toUpperCase() || 'A'}
                        </div>
                    </div>
                </header>

                {/* Content Area */}
                <main className="flex-1 overflow-y-auto bg-gray-50">
                    <div className="p-6">
                        {children}
                    </div>
                </main>
            </div>
        </div>
    );
}
