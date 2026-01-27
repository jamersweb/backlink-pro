import { Link } from '@inertiajs/react';

/**
 * Table Component
 * A reusable table component with sorting, selection, and action support
 */
export default function Table({
    columns = [],
    data = [],
    keyField = 'id',
    emptyMessage = 'No data available',
    emptyIcon = '📋',
    className = '',
    striped = true,
    hoverable = true,
    compact = false,
    selectable = false,
    selectedIds = [],
    onSelectChange,
    onSelectAll,
    actions,
}) {
    const allSelected = data.length > 0 && selectedIds.length === data.length;
    const someSelected = selectedIds.length > 0 && selectedIds.length < data.length;

    const handleSelectAll = () => {
        if (onSelectAll) {
            onSelectAll(allSelected ? [] : data.map(row => row[keyField]));
        }
    };

    const handleSelectRow = (id) => {
        if (onSelectChange) {
            if (selectedIds.includes(id)) {
                onSelectChange(selectedIds.filter(selectedId => selectedId !== id));
            } else {
                onSelectChange([...selectedIds, id]);
            }
        }
    };

    if (!data || data.length === 0) {
        return (
            <div className={`bg-white border border-gray-200 rounded-lg ${className}`}>
                <div className="text-center py-16">
                    <div className="inline-block p-6 bg-gray-100 rounded-full mb-4">
                        <span className="text-5xl">{emptyIcon}</span>
                    </div>
                    <p className="text-gray-500 font-medium text-lg">{emptyMessage}</p>
                </div>
            </div>
        );
    }

    return (
        <div className={`bg-white border border-gray-200 rounded-lg overflow-hidden ${className}`}>
            <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            {selectable && (
                                <th className="w-12 px-4 py-3">
                                    <input
                                        type="checkbox"
                                        checked={allSelected}
                                        ref={(el) => el && (el.indeterminate = someSelected)}
                                        onChange={handleSelectAll}
                                        className="h-4 w-4 text-gray-900 border-gray-300 rounded focus:ring-gray-900"
                                    />
                                </th>
                            )}
                            {columns.map((column, index) => (
                                <th
                                    key={column.key || index}
                                    className={`px-6 ${compact ? 'py-2' : 'py-3'} text-left text-xs font-semibold text-gray-700 uppercase tracking-wider ${column.className || ''}`}
                                    style={column.width ? { width: column.width } : {}}
                                >
                                    {column.label}
                                </th>
                            ))}
                            {actions && (
                                <th className={`px-6 ${compact ? 'py-2' : 'py-3'} text-left text-xs font-semibold text-gray-700 uppercase tracking-wider`}>
                                    Actions
                                </th>
                            )}
                        </tr>
                    </thead>
                    <tbody className={`bg-white divide-y divide-gray-200`}>
                        {data.map((row, rowIndex) => (
                            <tr
                                key={row[keyField] || rowIndex}
                                className={`
                                    ${striped && rowIndex % 2 === 1 ? 'bg-gray-50' : ''}
                                    ${hoverable ? 'hover:bg-gray-100 transition-colors' : ''}
                                    ${selectedIds.includes(row[keyField]) ? 'bg-blue-50' : ''}
                                `}
                            >
                                {selectable && (
                                    <td className="w-12 px-4 py-3">
                                        <input
                                            type="checkbox"
                                            checked={selectedIds.includes(row[keyField])}
                                            onChange={() => handleSelectRow(row[keyField])}
                                            className="h-4 w-4 text-gray-900 border-gray-300 rounded focus:ring-gray-900"
                                        />
                                    </td>
                                )}
                                {columns.map((column, colIndex) => (
                                    <td
                                        key={column.key || colIndex}
                                        className={`px-6 ${compact ? 'py-2' : 'py-4'} whitespace-nowrap ${column.cellClassName || ''}`}
                                    >
                                        {column.render
                                            ? column.render(row[column.key], row, rowIndex)
                                            : row[column.key]
                                        }
                                    </td>
                                ))}
                                {actions && (
                                    <td className={`px-6 ${compact ? 'py-2' : 'py-4'} whitespace-nowrap`}>
                                        <div className="flex gap-2">
                                            {actions(row, rowIndex)}
                                        </div>
                                    </td>
                                )}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

/**
 * TableCell component for custom cell rendering
 */
export function TableCell({ children, className = '' }) {
    return (
        <div className={className}>
            {children}
        </div>
    );
}

/**
 * TableLink component for link cells
 */
export function TableLink({ href, children, className = '' }) {
    return (
        <Link href={href} className={`text-blue-600 hover:text-blue-800 hover:underline ${className}`}>
            {children}
        </Link>
    );
}
