import { useState, useRef, useEffect } from 'react';
import { Link } from '@inertiajs/react';

/**
 * Dropdown Component
 * A flexible dropdown menu component
 */
export default function Dropdown({
    trigger,
    children,
    align = 'left',
    width = 'w-48',
    className = '',
}) {
    const [open, setOpen] = useState(false);
    const dropdownRef = useRef(null);

    useEffect(() => {
        const handleClickOutside = (event) => {
            if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
                setOpen(false);
            }
        };

        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    const alignmentClasses = {
        left: 'left-0',
        right: 'right-0',
        center: 'left-1/2 -translate-x-1/2',
    };

    return (
        <div className={`relative inline-block ${className}`} ref={dropdownRef}>
            <div onClick={() => setOpen(!open)}>
                {trigger}
            </div>

            {open && (
                <div
                    className={`absolute z-50 mt-2 ${width} ${alignmentClasses[align]} rounded-lg bg-white shadow-lg border border-gray-200 py-1`}
                    onClick={() => setOpen(false)}
                >
                    {children}
                </div>
            )}
        </div>
    );
}

/**
 * Dropdown Item
 */
export function DropdownItem({
    href,
    method = 'get',
    onClick,
    icon,
    children,
    danger = false,
    disabled = false,
}) {
    const baseClasses = `
        w-full flex items-center px-4 py-2 text-sm transition-colors
        ${danger ? 'text-red-600 hover:bg-red-50' : 'text-gray-700 hover:bg-gray-100'}
        ${disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'}
    `;

    if (href && !disabled) {
        return (
            <Link href={href} method={method} className={baseClasses}>
                {icon && <span className="mr-3 text-gray-400">{icon}</span>}
                {children}
            </Link>
        );
    }

    return (
        <button
            type="button"
            onClick={disabled ? undefined : onClick}
            className={baseClasses}
            disabled={disabled}
        >
            {icon && <span className="mr-3 text-gray-400">{icon}</span>}
            {children}
        </button>
    );
}

/**
 * Dropdown Divider
 */
export function DropdownDivider() {
    return <hr className="my-1 border-gray-200" />;
}

/**
 * Dropdown Header
 */
export function DropdownHeader({ children }) {
    return (
        <div className="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
            {children}
        </div>
    );
}

/**
 * Action Dropdown - Pre-configured dropdown for common actions
 */
export function ActionDropdown({
    onView,
    onEdit,
    onDuplicate,
    onDelete,
    viewHref,
    editHref,
    className = '',
}) {
    return (
        <Dropdown
            trigger={
                <button className="p-2 rounded-lg hover:bg-gray-100 transition-colors">
                    <svg className="w-5 h-5 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                    </svg>
                </button>
            }
            align="right"
            width="w-40"
            className={className}
        >
            {(viewHref || onView) && (
                <DropdownItem
                    href={viewHref}
                    onClick={onView}
                    icon={<svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>}
                >
                    View
                </DropdownItem>
            )}
            {(editHref || onEdit) && (
                <DropdownItem
                    href={editHref}
                    onClick={onEdit}
                    icon={<svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>}
                >
                    Edit
                </DropdownItem>
            )}
            {onDuplicate && (
                <DropdownItem
                    onClick={onDuplicate}
                    icon={<svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>}
                >
                    Duplicate
                </DropdownItem>
            )}
            {onDelete && (
                <>
                    <DropdownDivider />
                    <DropdownItem
                        onClick={onDelete}
                        danger
                        icon={<svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>}
                    >
                        Delete
                    </DropdownItem>
                </>
            )}
        </Dropdown>
    );
}
