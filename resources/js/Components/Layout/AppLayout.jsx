import { useState, useEffect } from 'react';
import { Link, usePage } from '@inertiajs/react';

export default function AppLayout({ children, header, subtitle, flush = false, bodyClass = '', actions }) {
    const { auth } = usePage().props;
    const { url } = usePage();
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [profileOpen, setProfileOpen] = useState(false);

    useEffect(() => {
        document.body.classList.add('bp-dark-dashboard-page');
        if (bodyClass) document.body.classList.add(bodyClass);
        return () => {
            document.body.classList.remove('bp-dark-dashboard-page');
            if (bodyClass) document.body.classList.remove(bodyClass);
        };
    }, [bodyClass]);

    useEffect(() => {
        setSidebarOpen(false);
    }, [url]);

    useEffect(() => {
        if (!profileOpen) return;
        const close = (e) => {
            if (!e.target.closest('.bp-profile-dropdown')) setProfileOpen(false);
        };
        document.addEventListener('click', close);
        return () => document.removeEventListener('click', close);
    }, [profileOpen]);

    const isActive = (path) => url === path || url.startsWith(`${path}/`);

    const navItems = [
        { href: '/dashboard', label: 'Dashboard', icon: 'bi-grid-1x2', iconActive: 'bi-grid-1x2-fill' },
        { href: '/campaign', label: 'Campaigns', icon: 'bi-megaphone', iconActive: 'bi-megaphone-fill' },
        { href: '/reports', label: 'Reports', icon: 'bi-bar-chart', iconActive: 'bi-bar-chart-fill' },
        { href: '/audit-report', label: 'Audit Report', icon: 'bi-clipboard-check', iconActive: 'bi-clipboard-check-fill' },
        { href: '/white-label-report', label: 'White Label Report', icon: 'bi-file-earmark-richtext', iconActive: 'bi-file-earmark-richtext-fill' },
        { href: '/settings', label: 'Settings', icon: 'bi-gear', iconActive: 'bi-gear-fill' },
    ];

    return (
        <div className="bp-dark-dashboard bp-user-theme-dark">
            {sidebarOpen && (
                <div className="bp-sidebar-overlay" onClick={() => setSidebarOpen(false)} />
            )}

            {/* Sidebar */}
            <aside className={`bp-sidebar ${sidebarOpen ? 'bp-sidebar-open' : ''}`}>
                <div className="bp-sidebar-header">
                    <Link href="/dashboard" className="bp-sidebar-logo">
                        <div className="bp-logo-icon">
                            <i className="bi bi-link-45deg"></i>
                        </div>
                        <span className="bp-logo-text">Backlink Pro</span>
                    </Link>
                </div>

                <nav className="bp-sidebar-nav">
                    {navItems.map((item) => {
                        const active = isActive(item.href);
                        return (
                            <Link
                                key={item.href}
                                href={item.href}
                                className={`bp-nav-item ${active ? 'bp-nav-active' : ''}`}
                            >
                                <i className={`bi ${active ? item.iconActive : item.icon}`}></i>
                                <span>{item.label}</span>
                            </Link>
                        );
                    })}
                </nav>

                <div className="bp-sidebar-footer">
                    <div className="bp-profile-dropdown" style={{ position: 'relative' }}>
                        <button className="bp-sidebar-user" onClick={() => setProfileOpen(!profileOpen)}>
                            <div className="bp-user-avatar">
                                {auth?.user?.name?.charAt(0).toUpperCase() || 'U'}
                            </div>
                            <div className="bp-user-info">
                                <span className="bp-user-name">{auth?.user?.name || 'User'}</span>
                                <span className="bp-user-email">{auth?.user?.email || ''}</span>
                            </div>
                        </button>
                        {profileOpen && (
                            <div className="bp-profile-menu">
                                <Link href="/profile" className="bp-profile-menu-item" onClick={() => setProfileOpen(false)}>
                                    <i className="bi bi-person"></i> View Profile
                                </Link>
                                <Link href="/subscription/manage" className="bp-profile-menu-item" onClick={() => setProfileOpen(false)}>
                                    <i className="bi bi-credit-card"></i> Subscription
                                </Link>
                                <Link href="/help" className="bp-profile-menu-item" onClick={() => setProfileOpen(false)}>
                                    <i className="bi bi-question-circle"></i> Help & Support
                                </Link>
                                <div className="bp-profile-menu-divider"></div>
                                <Link href="/logout" method="post" className="bp-profile-menu-item bp-profile-menu-danger" onClick={() => setProfileOpen(false)}>
                                    <i className="bi bi-box-arrow-right"></i> Logout
                                </Link>
                            </div>
                        )}
                    </div>
                </div>
            </aside>

            {/* Main area */}
            <div className="bp-main">
                <header className="bp-topbar">
                    <div className="bp-topbar-left">
                        <button
                            className="bp-hamburger"
                            onClick={() => setSidebarOpen(!sidebarOpen)}
                            aria-label="Toggle sidebar"
                        >
                            <i className="bi bi-list"></i>
                        </button>
                        <div className="bp-topbar-titles">
                            {header && <h1 className="bp-page-title">{header}</h1>}
                            {subtitle && <p className="bp-page-subtitle">{subtitle}</p>}
                        </div>
                    </div>
                    <div className="bp-topbar-actions">
                        {actions || (
                            <Link href="/notifications" className="bp-topbar-btn-secondary">
                                <i className="bi bi-bell"></i>
                                <span>Notifications</span>
                            </Link>
                        )}
                    </div>
                </header>

                <main className={`bp-content ${flush ? 'bp-content-flush' : ''}`}>
                    {children}
                </main>
            </div>
        </div>
    );
}
