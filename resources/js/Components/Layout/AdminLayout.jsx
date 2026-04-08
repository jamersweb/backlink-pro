import { Link, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import ThemeMenu from './ThemeMenu';

export default function AdminLayout({ children, header }) {
    const { auth } = usePage().props;
    const [sidebarOpen, setSidebarOpen] = useState(true);
    const [notificationsOpen, setNotificationsOpen] = useState(false);
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

    // Mock notifications data (replace with API later)
    const notifications = [
        {
            id: 1,
            type: 'success',
            title: 'Campaign Created',
            message: 'New campaign "SEO 2026" was created successfully',
            time: '2 minutes ago',
            read: false,
        },
        {
            id: 2,
            type: 'info',
            title: 'New User Registration',
            message: 'John Doe has registered an account',
            time: '1 hour ago',
            read: false,
        },
        {
            id: 3,
            type: 'warning',
            title: 'System Update',
            message: 'Scheduled maintenance tonight at 10 PM',
            time: '3 hours ago',
            read: true,
        },
    ];

    const unreadCount = notifications.filter(n => !n.read).length;

    // Close dropdowns when clicking outside or pressing Esc
    useEffect(() => {
        const handleClickOutside = (event) => {
            if (profileDropdownOpen && !event.target.closest('.relative')) {
                setProfileDropdownOpen(false);
            }
            if (notificationsOpen && !event.target.closest('.notifications-container')) {
                setNotificationsOpen(false);
            }
        };
        
        const handleEscape = (event) => {
            if (event.key === 'Escape') {
                setNotificationsOpen(false);
                setProfileDropdownOpen(false);
            }
        };

        if (profileDropdownOpen || notificationsOpen) {
            document.addEventListener('click', handleClickOutside);
            document.addEventListener('keydown', handleEscape);
        }
        return () => {
            document.removeEventListener('click', handleClickOutside);
            document.removeEventListener('keydown', handleEscape);
        };
    }, [profileDropdownOpen, notificationsOpen]);

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
                    currentUrl.startsWith('/admin/feature-flags') ||
                    currentUrl.startsWith('/admin/blocked-sites') ||
                    currentUrl.startsWith('/admin/ml-training'),
            children: [
                { name: 'Proxies', href: '/admin/proxies', icon: 'bi-router' },
                { name: 'Captcha Logs', href: '/admin/captcha-logs', icon: 'bi-shield-check' },
                { name: 'System Health', href: '/admin/system-health', icon: 'bi-heart-pulse' },
                { name: 'Feature Flags', href: '/admin/feature-flags', icon: 'bi-flag' },
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
                                            <ul className="mt-2 ml-2 space-y-0.5 border-l-2 border-[var(--admin-border)] pl-4">
                                                {item.children.map((child, childIndex) => (
                                                    <li key={childIndex}>
                                                        <Link
                                                            href={child.href}
                                                            className={`flex items-center px-3 py-2.5 rounded-lg transition-all duration-150 group ${
                                                                currentUrl === child.href || currentUrl.startsWith(child.href + '/')
                                                                    ? 'bg-gradient-to-r from-[#2F6BFF]/15 to-[#2F6BFF]/5 text-[#2F6BFF] dark:text-[#5B8AFF] font-medium border border-[#2F6BFF]/20'
                                                                    : 'text-[var(--admin-text-dim)] hover:bg-[var(--admin-hover-bg)] hover:text-[var(--admin-text)] hover:border-[var(--admin-border)] border border-transparent'
                                                            }`}
                                                        >
                                                            <i className={`bi ${child.icon} text-base mr-3 ${
                                                                currentUrl === child.href || currentUrl.startsWith(child.href + '/')
                                                                    ? 'text-[#2F6BFF]'
                                                                    : 'text-[var(--admin-text-muted)] group-hover:text-[var(--admin-text)]'
                                                            }`}></i>
                                                            <span className="text-sm font-medium">{child.name}</span>
                                                            {(currentUrl === child.href || currentUrl.startsWith(child.href + '/')) && (
                                                                <div className="ml-auto w-1.5 h-1.5 rounded-full bg-[#2F6BFF]"></div>
                                                            )}
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
                        {/* Notifications */}
                        <div className="relative notifications-container">
                            <button 
                                onClick={() => setNotificationsOpen(!notificationsOpen)}
                                className="p-2 rounded-lg hover:bg-[var(--admin-hover-bg)] transition-colors text-[var(--admin-text-muted)] hover:text-[var(--admin-text)] relative"
                            >
                                <i className="bi bi-bell text-lg"></i>
                                {unreadCount > 0 && (
                                    <span className="absolute top-1 right-1 flex items-center justify-center min-w-[18px] h-[18px] bg-[#F04438] text-white text-[10px] font-bold rounded-full px-1">
                                        {unreadCount > 9 ? '9+' : unreadCount}
                                    </span>
                                )}
                            </button>

                            {/* Notifications Dropdown */}
                            {notificationsOpen && (
                                <div className="absolute right-0 mt-2 w-96 max-w-[calc(100vw-2rem)] bg-[var(--admin-surface)] border border-[var(--admin-border)] rounded-xl shadow-2xl z-[100] overflow-hidden">
                                    {/* Header */}
                                    <div className="p-4 border-b border-[var(--admin-border)] bg-[var(--admin-surface-2)]">
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center gap-2">
                                                <h3 className="text-base font-semibold text-[var(--admin-text)]">Notifications</h3>
                                                {unreadCount > 0 && (
                                                    <span className="px-2 py-0.5 text-xs font-semibold rounded-full bg-[#F04438]/15 text-[#F04438]">
                                                        {unreadCount} new
                                                    </span>
                                                )}
                                            </div>
                                            <button
                                                onClick={() => setNotificationsOpen(false)}
                                                className="p-1 rounded-lg hover:bg-[var(--admin-hover-bg)] text-[var(--admin-text-muted)] hover:text-[var(--admin-text)] transition-colors"
                                            >
                                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    {/* Notifications List */}
                                    <div className="max-h-[400px] overflow-y-auto admin-scrollbar">
                                        {notifications.length > 0 ? (
                                            <div className="divide-y divide-[var(--admin-border)]">
                                                {notifications.map((notification) => (
                                                    <div
                                                        key={notification.id}
                                                        className={`p-4 hover:bg-[var(--admin-hover-bg)] transition-colors cursor-pointer ${
                                                            !notification.read ? 'bg-[#2F6BFF]/5' : ''
                                                        }`}
                                                    >
                                                        <div className="flex items-start gap-3">
                                                            <div className={`flex items-center justify-center w-10 h-10 rounded-xl flex-shrink-0 ${
                                                                notification.type === 'success' 
                                                                    ? 'bg-[#12B76A]/15'
                                                                    : notification.type === 'warning'
                                                                        ? 'bg-[#F79009]/15'
                                                                        : notification.type === 'error'
                                                                            ? 'bg-[#F04438]/15'
                                                                            : 'bg-[#2F6BFF]/15'
                                                            }`}>
                                                                <svg className={`h-5 w-5 ${
                                                                    notification.type === 'success' 
                                                                        ? 'text-[#12B76A]'
                                                                        : notification.type === 'warning'
                                                                            ? 'text-[#F79009]'
                                                                            : notification.type === 'error'
                                                                                ? 'text-[#F04438]'
                                                                                : 'text-[#5B8AFF]'
                                                                }`} fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    {notification.type === 'success' ? (
                                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                    ) : notification.type === 'warning' ? (
                                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                                    ) : notification.type === 'error' ? (
                                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                    ) : (
                                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                    )}
                                                                </svg>
                                                            </div>
                                                            <div className="flex-1 min-w-0">
                                                                <p className="text-sm font-semibold text-[var(--admin-text)]">{notification.title}</p>
                                                                <p className="text-sm text-[var(--admin-text-muted)] mt-1">{notification.message}</p>
                                                                <p className="text-xs text-[var(--admin-text-dim)] mt-2">{notification.time}</p>
                                                            </div>
                                                            {!notification.read && (
                                                                <div className="w-2 h-2 bg-[#2F6BFF] rounded-full flex-shrink-0 mt-1.5"></div>
                                                            )}
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        ) : (
                                            <div className="text-center py-12">
                                                <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-[var(--admin-surface-2)] mb-4">
                                                    <svg className="h-8 w-8 text-[var(--admin-text-dim)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                                    </svg>
                                                </div>
                                                <p className="text-[var(--admin-text-muted)] font-medium">No notifications</p>
                                                <p className="text-[var(--admin-text-dim)] text-sm mt-1">You're all caught up!</p>
                                            </div>
                                        )}
                                    </div>

                                    {/* Footer */}
                                    {notifications.length > 0 && (
                                        <div className="p-3 border-t border-[var(--admin-border)] bg-[var(--admin-surface-2)]">
                                            <button className="w-full text-sm font-medium text-[#2F6BFF] hover:text-[#5B8AFF] transition-colors py-1">
                                                View all notifications
                                            </button>
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>
                        
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
