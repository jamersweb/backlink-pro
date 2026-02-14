import { useState, useEffect, useRef } from 'react';
import { useTheme } from '@/Contexts/ThemeContext';

export default function ThemeMenu() {
    const { theme, setDarkMode, setLightMode } = useTheme();
    const [isOpen, setIsOpen] = useState(false);
    const dropdownRef = useRef(null);

    // Close on click outside
    useEffect(() => {
        const handleClickOutside = (event) => {
            if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
                setIsOpen(false);
            }
        };

        if (isOpen) {
            document.addEventListener('mousedown', handleClickOutside);
        }

        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
        };
    }, [isOpen]);

    // Close on ESC key
    useEffect(() => {
        const handleEscape = (event) => {
            if (event.key === 'Escape') {
                setIsOpen(false);
            }
        };

        if (isOpen) {
            document.addEventListener('keydown', handleEscape);
        }

        return () => {
            document.removeEventListener('keydown', handleEscape);
        };
    }, [isOpen]);

    const handleThemeChange = (newTheme) => {
        if (newTheme === 'light') {
            setLightMode();
        } else {
            setDarkMode();
        }
        setIsOpen(false);
    };

    return (
        <div className="relative" ref={dropdownRef}>
            {/* Gear Icon Button */}
            <button
                onClick={() => setIsOpen(!isOpen)}
                className="p-2 rounded-lg hover:bg-[var(--admin-hover-bg)] transition-colors text-[var(--admin-text-muted)] hover:text-[var(--admin-text)]"
                aria-label="Theme settings"
            >
                <i className="bi bi-gear text-lg"></i>
            </button>

            {/* Dropdown Menu */}
            {isOpen && (
                <div 
                    className="absolute right-0 mt-2 w-56 bg-[var(--admin-surface)] rounded-xl border border-[var(--admin-border)] shadow-xl z-50 overflow-hidden"
                    style={{ 
                        animation: 'fadeIn 0.15s ease-out',
                        boxShadow: 'var(--admin-shadow-lg)'
                    }}
                >
                    {/* Header */}
                    <div className="px-4 py-3 border-b border-[var(--admin-border)] bg-[var(--admin-hover-bg)]">
                        <h3 className="text-sm font-semibold text-[var(--admin-text)]">Appearance</h3>
                    </div>

                    {/* Theme Options */}
                    <div className="p-2">
                        {/* Light Mode */}
                        <button
                            onClick={() => handleThemeChange('light')}
                            className={`w-full flex items-center justify-between px-3 py-2.5 rounded-lg transition-all duration-200 ${
                                theme === 'light'
                                    ? 'bg-[var(--admin-primary)] bg-opacity-10 text-[var(--admin-text)]'
                                    : 'text-[var(--admin-text-muted)] hover:bg-[var(--admin-hover-bg)] hover:text-[var(--admin-text)]'
                            }`}
                        >
                            <div className="flex items-center gap-3">
                                <i className="bi bi-sun text-lg"></i>
                                <span className="font-medium text-sm">Light</span>
                            </div>
                            {theme === 'light' && (
                                <i className="bi bi-check-lg text-[var(--admin-primary)]"></i>
                            )}
                        </button>

                        {/* Dark Mode */}
                        <button
                            onClick={() => handleThemeChange('dark')}
                            className={`w-full flex items-center justify-between px-3 py-2.5 rounded-lg transition-all duration-200 mt-1 ${
                                theme === 'dark'
                                    ? 'bg-[var(--admin-primary)] bg-opacity-10 text-[var(--admin-text)]'
                                    : 'text-[var(--admin-text-muted)] hover:bg-[var(--admin-hover-bg)] hover:text-[var(--admin-text)]'
                            }`}
                        >
                            <div className="flex items-center gap-3">
                                <i className="bi bi-moon-stars text-lg"></i>
                                <span className="font-medium text-sm">Dark</span>
                            </div>
                            {theme === 'dark' && (
                                <i className="bi bi-check-lg text-[var(--admin-primary)]"></i>
                            )}
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
}
