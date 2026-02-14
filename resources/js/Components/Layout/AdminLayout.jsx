import { Link, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import ThemeMenu from './ThemeMenu';

export default function AdminLayout({ children, header }) {
    const { auth } = usePage().props;
    const [sidebarOpen, setSidebarOpen] = useState(true);
    const currentUrl = window.location.pathname;
    const isLeadsActive = currentUrl.startsWith('/admin/leads');
    const isSystemActive = currentUrl.startsWith('/admin/proxies') ||
                          currentUrl.startsWith('/admin/captcha') ||
                          currentUrl.startsWith('/admin/system-health') ||
                          currentUrl.startsWith('/admin/system-config') ||
                          currentUrl.startsWith('/admin/blocked-sites') ||
                          currentUrl.startsWith('/admin/ml-training');
    const isContentActive = currentUrl.startsWith('/admin/page-metas') ||
                           currentUrl.startsWith('/admin/marketing-leads');
    const isAccessActive = currentUrl.startsWith('/admin/roles-permissions');
    
    // Auto-expand dropdowns if on relevant pages
    const [leadsDropdownOpen, setLeadsDropdownOpen] = useState(isLeadsActive);
    const [systemDropdownOpen, setSystemDropdownOpen] = useState(isSystemActive);
    const [contentDropdownOpen, setContentDropdownOpen] = useState(isContentActive);
    const [accessDropdownOpen, setAccessDropdownOpen] = useState(isAccessActive);
    const [profileDropdownOpen, setProfileDropdownOpen] = useState(false);

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
            name: 'Audit Report',
            href: '/admin/audit-report',
            icon: 'bi-search',
            active: currentUrl.startsWith('/admin/audit-report'),
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
                    currentUrl.startsWith('/admin/blocked-sites') ||
                    currentUrl.startsWith('/admin/ml-training'),
            children: [
                { name: 'Proxies', href: '/admin/proxies', icon: 'bi-router' },
                { name: 'Captcha Logs', href: '/admin/captcha-logs', icon: 'bi-shield-check' },
                { name: 'System Health', href: '/admin/system-health', icon: 'bi-heart-pulse' },
                { name: 'System Config', href: '/admin/system-config', icon: 'bi-gear-wide-connected' },
                { name: 'Blocked Sites', href: '/admin/blocked-sites', icon: 'bi-ban' },
                { name: 'ML Training', href: '/admin/ml-training', icon: 'bi-cpu' },
            ],
        },
        {
            name: 'Content',
            icon: 'bi-file-earmark-text',
            active: isContentActive,
            children: [
                { name: 'Page SEO', href: '/admin/page-metas', icon: 'bi-search' },
                { name: 'Marketing Leads', href: '/admin/marketing-leads', icon: 'bi-envelope' },
            ],
        },
        {
            name: 'Access Control',
            icon: 'bi-shield-lock',
            active: isAccessActive,
            children: [
                { name: 'Roles & Permissions', href: '/admin/roles-permissions', icon: 'bi-people-fill' },
                { name: 'User Permissions', href: '/admin/roles-permissions/users', icon: 'bi-person-gear' },
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
        <div className="min-h-screen bg-[var(--admin-bg)] flex relative">
            {/* Mobile Sidebar Overlay */}
            {sidebarOpen && (
                <div
                    className="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 lg:hidden"
                    onClick={() => setSidebarOpen(false)}
                ></div>
            )}

            {/* Sidebar */}
            <aside className={`fixed lg:static inset-y-0 left-0 bg-[var(--admin-surface)] border-r border-[var(--admin-border)] transition-all duration-300 ease-in-out z-50 ${
                sidebarOpen ? 'w-64 translate-x-0' : '-translate-x-full lg:translate-x-0 lg:w-20'
            } flex flex-col`}>
                {/* Sidebar Header */}
                <div className={`h-16 flex items-center border-b border-[var(--admin-border)] ${
                    sidebarOpen ? 'justify-between px-4' : 'justify-center px-2'
                }`}>
                    {sidebarOpen ? (
                        <>
                            <Link href="/admin/dashboard" className="flex items-center space-x-3 group">
                                        <div className="w-9 h-9 rounded-lg bg-gradient-to-br from-[#2F6BFF] to-[#B6F400] p-[2px] group-hover:shadow-lg group-hover:shadow-[#2F6BFF]/25 transition-all duration-300">
                                            <div className="w-full h-full rounded-[6px] bg-[var(--admin-surface)] flex items-center justify-center">
                                        <svg className="w-5 h-5 text-[#2F6BFF]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                        </svg>
                                    </div>
                                </div>
                                <span className="text-lg font-bold text-[var(--admin-text)] group-hover:text-[var(--admin-text)] transition-colors">Admin</span>
                            </Link>
                            <button
                                onClick={() => setSidebarOpen(!sidebarOpen)}
                                className="p-2 rounded-lg hover:bg-[var(--admin-hover-bg)] transition-colors text-[var(--admin-text-muted)] hover:text-[var(--admin-text)]"
                            >
                                <i className="bi bi-chevron-left"></i>
                            </button>
                        </>
                    ) : (
                        <button
                            onClick={() => setSidebarOpen(!sidebarOpen)}
                            className="p-2 rounded-lg hover:bg-[var(--admin-hover-bg)] transition-colors w-full flex justify-center"
                        >
                            <div className="w-9 h-9 rounded-lg bg-gradient-to-br from-[#2F6BFF] to-[#B6F400] p-[2px]">
                                <div className="w-full h-full rounded-[6px] bg-[var(--admin-surface)] flex items-center justify-center">
                                    <svg className="w-5 h-5 text-[#2F6BFF]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                    </svg>
                                </div>
                            </div>
                        </button>
                    )}
                </div>

                {/* Sidebar Navigation */}
                <nav className="flex-1 overflow-y-auto py-4 admin-scrollbar">
                    <ul className="space-y-1 px-3">
                        {menuItems.map((item, index) => (
                            <li key={index}>
                                {item.children ? (
                                    <div>
                                        <button
                                            onClick={() => {
                                                if (item.name === 'Leads') setLeadsDropdownOpen(!leadsDropdownOpen);
                                                if (item.name === 'System') setSystemDropdownOpen(!systemDropdownOpen);
                                                if (item.name === 'Content') setContentDropdownOpen(!contentDropdownOpen);
                                                if (item.name === 'Access Control') setAccessDropdownOpen(!accessDropdownOpen);
                                            }}
                                            className={`w-full flex items-center px-3 py-2.5 rounded-lg transition-all duration-200 ${
                                                item.active
                                                    ? 'bg-gradient-to-r from-[#2F6BFF] to-[#2457D6] text-white shadow-lg shadow-[#2F6BFF]/20'
                                                    : 'text-[var(--admin-text-muted)] hover:bg-[var(--admin-hover-bg)] hover:text-[var(--admin-text)]'
                                            }`}
                                        >
                                            <i className={`bi ${item.icon} text-lg ${sidebarOpen ? 'mr-3' : 'mx-auto'}`}></i>
                                            {sidebarOpen && (
                                                <>
                                                    <span className="flex-1 text-left font-medium text-sm">{item.name}</span>
                                                    <i className={`bi ${
                                                        (item.name === 'Leads' && leadsDropdownOpen) || 
                                                        (item.name === 'System' && systemDropdownOpen) ||
                                                        (item.name === 'Content' && contentDropdownOpen) ||
                                                        (item.name === 'Access Control' && accessDropdownOpen)
                                                            ? 'bi-chevron-up' : 'bi-chevron-down'
                                                    } text-xs opacity-60`}></i>
                                                </>
                                            )}
                                        </button>
                                        {sidebarOpen && (
                                            (item.name === 'Leads' && leadsDropdownOpen) || 
                                            (item.name === 'System' && systemDropdownOpen) ||
                                            (item.name === 'Content' && contentDropdownOpen) ||
                                            (item.name === 'Access Control' && accessDropdownOpen)
                                        ) && (
                                            <ul className="mt-1 ml-3 space-y-1 border-l border-[var(--admin-border)] pl-3">
                                                {item.children.map((child, childIndex) => (
                                                    <li key={childIndex}>
                                                        <Link
                                                            href={child.href}
                                                            className={`flex items-center px-3 py-2 rounded-lg transition-all duration-200 ${
                                                                currentUrl === child.href || currentUrl.startsWith(child.href + '/')
                                                                    ? 'bg-[#2F6BFF]/20 text-[#5B8AFF] font-medium'
                                                                    : 'text-[var(--admin-text-dim)] hover:bg-[var(--admin-hover-bg)] hover:text-[var(--admin-text)]'
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
                                        className={`flex items-center px-3 py-2.5 rounded-lg transition-all duration-200 ${
                                            item.active
                                                ? 'bg-gradient-to-r from-[#2F6BFF] to-[#2457D6] text-white shadow-lg shadow-[#2F6BFF]/20'
                                                : 'text-[var(--admin-text-muted)] hover:bg-[var(--admin-hover-bg)] hover:text-[var(--admin-text)]'
                                        }`}
                                        title={!sidebarOpen ? item.name : ''}
                                    >
                                        <i className={`bi ${item.icon} text-lg ${sidebarOpen ? 'mr-3' : 'mx-auto'}`}></i>
                                        {sidebarOpen && <span className="font-medium text-sm">{item.name}</span>}
                                    </Link>
                                )}
                            </li>
                        ))}
                    </ul>
                </nav>

                {/* Sidebar Footer */}
                <div className="border-t border-[var(--admin-border)] p-4">
                    <div className="relative">
                        <button
                            onClick={() => setProfileDropdownOpen(!profileDropdownOpen)}
                            className={`w-full flex items-center px-3 py-2.5 rounded-lg hover:bg-[var(--admin-hover-bg)] transition-all duration-200 ${
                                sidebarOpen ? 'justify-start' : 'justify-center'
                            }`}
                        >
                            <div className="h-9 w-9 rounded-lg bg-gradient-to-br from-[#2F6BFF] to-[#B6F400] p-[2px]">
                                <div className="w-full h-full rounded-[6px] bg-[var(--admin-surface)] flex items-center justify-center text-white font-bold text-sm">
                                    {auth?.user?.name?.charAt(0).toUpperCase() || 'A'}
                                </div>
                            </div>
                            {sidebarOpen && (
                                <div className="ml-3 flex-1 text-left">
                                    <p className="text-sm font-medium text-[var(--admin-text)]">{auth?.user?.name}</p>
                                    <p className="text-xs text-[var(--admin-text-dim)] truncate">{auth?.user?.email}</p>
                                </div>
                            )}
                        </button>
                        {profileDropdownOpen && (
                            <div className="absolute bottom-full left-0 mb-2 w-full bg-[var(--admin-surface-2)] rounded-xl border border-[var(--admin-border)] py-2 z-50 shadow-xl shadow-[var(--admin-shadow-lg)] animate-fadeIn">
                                <Link
                                    href="/"
                                    className="flex items-center px-4 py-2.5 text-sm text-[var(--admin-text)] hover:bg-[var(--admin-hover-bg)] transition-colors"
                                    onClick={() => setProfileDropdownOpen(false)}
                                >
                                    <i className="bi bi-house mr-3 text-[var(--admin-text-muted)]"></i>View Site
                                </Link>
                                <Link
                                    href="/profile"
                                    className="flex items-center px-4 py-2.5 text-sm text-[var(--admin-text)] hover:bg-[var(--admin-hover-bg)] transition-colors"
                                    onClick={() => setProfileDropdownOpen(false)}
                                >
                                    <i className="bi bi-person mr-3 text-[var(--admin-text-muted)]"></i>Profile
                                </Link>
                                <div className="border-t border-[var(--admin-border)] my-2"></div>
                                <Link
                                    href="/logout"
                                    method="post"
                                    className="flex items-center px-4 py-2.5 text-sm text-[#F04438] hover:bg-[#F04438]/10 transition-colors"
                                    onClick={() => setProfileDropdownOpen(false)}
                                >
                                    <i className="bi bi-box-arrow-right mr-3"></i>Logout
                                </Link>
                            </div>
                        )}
                    </div>
                </div>
            </aside>

            {/* Main Content Area */}
            <div className="flex-1 flex flex-col overflow-hidden min-w-0">
                {/* Top Header */}
                <header className="h-16 bg-[var(--admin-surface)]/80 backdrop-blur-xl border-b border-[var(--admin-border)] flex items-center justify-between px-6 sticky top-0 z-30">
                    <div className="flex items-center space-x-4">
                        <button
                            onClick={() => setSidebarOpen(!sidebarOpen)}
                            className="lg:hidden p-2 rounded-lg hover:bg-[var(--admin-hover-bg)] transition-colors text-[var(--admin-text-muted)] hover:text-[var(--admin-text)]"
                        >
                            <i className="bi bi-list text-xl"></i>
                        </button>
                        {header && (
                            <h1 className="text-xl font-bold text-[var(--admin-text)]">{header}</h1>
                        )}
                    </div>
                    <div className="flex items-center space-x-4">
                        {/* Quick Actions */}
                        <button className="p-2 rounded-lg hover:bg-[var(--admin-hover-bg)] transition-colors text-[var(--admin-text-muted)] hover:text-[var(--admin-text)] relative">
                            <i className="bi bi-bell text-lg"></i>
                            <span className="absolute top-1 right-1 w-2 h-2 bg-[#F04438] rounded-full"></span>
                        </button>
                        <ThemeMenu />
                        <div className="h-9 w-9 rounded-lg bg-gradient-to-br from-[#2F6BFF] to-[#B6F400] p-[2px] lg:hidden">
                            <div className="w-full h-full rounded-[6px] bg-[var(--admin-surface)] flex items-center justify-center text-white font-bold text-sm">
                                {auth?.user?.name?.charAt(0).toUpperCase() || 'A'}
                            </div>
                        </div>
                    </div>
                </header>

                {/* Content Area */}
                <main className="flex-1 overflow-y-auto bg-[var(--admin-bg)] admin-scrollbar">
                    <div className="p-6">
                        {children}
                    </div>
                </main>
            </div>
        </div>
    );
}
