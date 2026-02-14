import { createContext, useContext, useEffect, useState } from 'react';

const ThemeContext = createContext();

export function ThemeProvider({ children }) {
    const [theme, setTheme] = useState(() => {
        // Check localStorage first
        const savedTheme = localStorage.getItem('admin_theme');
        if (savedTheme) {
            return savedTheme;
        }
        
        // Check system preference
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return 'dark';
        }
        
        // Default to dark
        return 'dark';
    });

    useEffect(() => {
        // Apply theme class to document
        const root = document.documentElement;
        
        // Remove existing theme classes
        root.classList.remove('dark', 'light');
        
        // Add current theme class
        root.classList.add(theme);
        
        // Save to localStorage
        localStorage.setItem('admin_theme', theme);
        
        // Update meta theme-color for mobile browsers
        const metaThemeColor = document.querySelector('meta[name="theme-color"]');
        if (metaThemeColor) {
            metaThemeColor.setAttribute('content', theme === 'dark' ? '#0B0F14' : '#F6F8FB');
        } else {
            const meta = document.createElement('meta');
            meta.name = 'theme-color';
            meta.content = theme === 'dark' ? '#0B0F14' : '#F6F8FB';
            document.head.appendChild(meta);
        }
    }, [theme]);

    const toggleTheme = () => {
        setTheme(prevTheme => prevTheme === 'dark' ? 'light' : 'dark');
    };

    const setDarkMode = () => setTheme('dark');
    const setLightMode = () => setTheme('light');

    return (
        <ThemeContext.Provider value={{ theme, toggleTheme, setDarkMode, setLightMode }}>
            {children}
        </ThemeContext.Provider>
    );
}

export function useTheme() {
    const context = useContext(ThemeContext);
    if (!context) {
        throw new Error('useTheme must be used within a ThemeProvider');
    }
    return context;
}
