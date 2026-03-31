import { useState, useRef, useEffect, useLayoutEffect } from 'react';
import { createPortal } from 'react-dom';
import { DayPicker } from 'react-day-picker';
import 'react-day-picker/style.css';

/**
 * Parse YYYY-MM-DD string to Date (local timezone)
 */
function parseDate(str) {
    if (!str || typeof str !== 'string') return undefined;
    const [y, m, d] = str.split('-').map(Number);
    if (!y || !m || !d) return undefined;
    const date = new Date(y, m - 1, d);
    if (date.getFullYear() !== y || date.getMonth() !== m - 1 || date.getDate() !== d) return undefined;
    return date;
}

/**
 * Format Date to YYYY-MM-DD
 */
function formatDate(date) {
    if (!date || !(date instanceof Date) || isNaN(date.getTime())) return '';
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

/**
 * Format for display (e.g. "Feb 17, 2026")
 */
function formatDisplay(date) {
    if (!date || !(date instanceof Date) || isNaN(date.getTime())) return '';
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

export default function BpDatePicker({
    value = '',
    onChange,
    label,
    placeholder = 'Select date',
    min,
    max,
    disabled = false,
    className = '',
    name,
    id,
    error,
}) {
    const [isOpen, setIsOpen] = useState(false);
    const [month, setMonth] = useState(() => parseDate(value) || new Date());
    const containerRef = useRef(null);
    const triggerRef = useRef(null);
    const popoverRef = useRef(null);
    const [popoverStyle, setPopoverStyle] = useState({});

    const selectedDate = parseDate(value);

    useLayoutEffect(() => {
        if (!isOpen || !triggerRef.current) return;

        const updatePosition = () => {
            if (!triggerRef.current) return;

            const rect = triggerRef.current.getBoundingClientRect();
            const popoverWidth = popoverRef.current?.offsetWidth || 320;
            const popoverHeight = popoverRef.current?.offsetHeight || 360;
            const viewportWidth = window.innerWidth;
            const viewportHeight = window.innerHeight;
            const gap = 8;
            const edgePadding = 12;

            let left = rect.left;
            let top = rect.bottom + gap;

            if (left + popoverWidth > viewportWidth - edgePadding) {
                left = Math.max(edgePadding, viewportWidth - popoverWidth - edgePadding);
            }

            if (left < edgePadding) {
                left = edgePadding;
            }

            const spaceBelow = viewportHeight - rect.bottom;
            const spaceAbove = rect.top;

            if (spaceBelow < popoverHeight + gap && spaceAbove > popoverHeight + gap) {
                top = Math.max(edgePadding, rect.top - popoverHeight - gap);
            } else if (top + popoverHeight > viewportHeight - edgePadding) {
                top = Math.max(edgePadding, viewportHeight - popoverHeight - edgePadding);
            }

            setPopoverStyle({
                position: 'fixed',
                top,
                left,
                zIndex: 99999,
                width: Math.min(popoverWidth, viewportWidth - edgePadding * 2),
                maxWidth: `calc(100vw - ${edgePadding * 2}px)`,
            });
        };

        updatePosition();
        const rafId = window.requestAnimationFrame(updatePosition);
        window.addEventListener('scroll', updatePosition, true);
        window.addEventListener('resize', updatePosition);

        return () => {
            window.cancelAnimationFrame(rafId);
            window.removeEventListener('scroll', updatePosition, true);
            window.removeEventListener('resize', updatePosition);
        };
    }, [isOpen, month]);

    useEffect(() => {
        const parsed = parseDate(value);
        if (parsed) setMonth(parsed);
    }, [value]);

    useEffect(() => {
        if (!isOpen) return;
        const handleClickOutside = (e) => {
            const isInsideTrigger = containerRef.current?.contains(e.target);
            const isInsidePopover = e.target.closest?.('.bp-date-picker-popover');
            if (!isInsideTrigger && !isInsidePopover) {
                setIsOpen(false);
            }
        };
        const handleEscape = (e) => {
            if (e.key === 'Escape') setIsOpen(false);
        };
        document.addEventListener('mousedown', handleClickOutside);
        document.addEventListener('keydown', handleEscape);
        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
            document.removeEventListener('keydown', handleEscape);
        };
    }, [isOpen]);

    const handleSelect = (date) => {
        if (date) {
            onChange?.({ target: { value: formatDate(date) } });
            setIsOpen(false);
        }
    };

    const handleClear = (e) => {
        e.stopPropagation();
        onChange?.({ target: { value: '' } });
        setIsOpen(false);
    };

    const inputId = id || name || `bp-date-${Math.random().toString(36).slice(2)}`;

    const isDisabled = (date) => {
        if (min) {
            const minDate = parseDate(min);
            if (minDate && date < minDate) return true;
        }
        if (max) {
            const maxDate = parseDate(max);
            if (maxDate && date > maxDate) return true;
        }
        return false;
    };

    return (
        <div className={`bp-date-picker ${className}`} ref={containerRef}>
            {label && (
                <label htmlFor={inputId} className="block text-sm font-medium text-gray-700 mb-1">
                    {label}
                </label>
            )}
            <div className="relative" ref={triggerRef}>
                <button
                    type="button"
                    id={inputId}
                    name={name}
                    onClick={() => !disabled && setIsOpen((o) => !o)}
                    disabled={disabled}
                    aria-haspopup="dialog"
                    aria-expanded={isOpen}
                    aria-label={label || 'Choose date'}
                    className={`bp-date-picker-trigger w-full flex items-center justify-between gap-2 px-4 py-3 rounded-xl border transition-all duration-200 text-left ${
                        error
                            ? 'border-[#F04438] focus:border-[#F04438]'
                            : 'border-[var(--admin-border)] focus:border-[#2F6BFF] focus:ring-2 focus:ring-[#2F6BFF]/20'
                    } bg-[var(--admin-bg)] text-[var(--admin-text)] placeholder-[var(--admin-text-dim)] focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed`}
                >
                    <span className={selectedDate ? '' : 'text-[var(--admin-text-dim)]'}>
                        {selectedDate ? formatDisplay(selectedDate) : placeholder}
                    </span>
                    <i className="bi bi-calendar3 text-[var(--admin-text-dim)] flex-shrink-0" aria-hidden />
                </button>

                {isOpen && createPortal(
                    <div
                        ref={popoverRef}
                        className="bp-date-picker-popover rounded-[14px] border border-white/10 bg-[#0B1220] shadow-xl p-4"
                        style={popoverStyle}
                        role="dialog"
                        aria-modal="true"
                        aria-label="Choose date"
                    >
                        <div className="bp-date-picker-root">
                            <DayPicker
                                mode="single"
                                selected={selectedDate}
                                onSelect={handleSelect}
                                month={month}
                                onMonthChange={setMonth}
                                disabled={min || max ? isDisabled : undefined}
                                className="bp-day-picker"
                            />
                        </div>
                        <div className="bp-date-picker-footer flex justify-between mt-3 pt-3 border-t border-white/10">
                            <button
                                type="button"
                                onClick={handleClear}
                                className="bp-date-picker-footer-btn text-sm transition-colors"
                            >
                                Clear
                            </button>
                            <button
                                type="button"
                                onClick={() => handleSelect(new Date())}
                                className="bp-date-picker-footer-btn bp-date-picker-today-btn text-sm font-medium transition-colors"
                            >
                                Today
                            </button>
                        </div>
                    </div>,
                    document.body
                )}
            </div>
            {error && (
                <p className="mt-2 text-sm text-[#F04438] flex items-center gap-1.5">
                    <svg className="h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                    </svg>
                    {error}
                </p>
            )}
        </div>
    );
}
