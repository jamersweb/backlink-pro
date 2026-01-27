import { useState } from 'react';
import AdminLayout from '@/Components/Layout/AdminLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';
import { Link, useForm, router } from '@inertiajs/react';

export default function RolesPermissionsUsers({ users, roles, permissions, permissionsList, filters }) {
    const [selectedUser, setSelectedUser] = useState(null);
    const [editMode, setEditMode] = useState(null); // 'roles' or 'permissions'
    const [expandedModules, setExpandedModules] = useState({});
    const [searchTerm, setSearchTerm] = useState(filters?.search || '');
    const [roleFilter, setRoleFilter] = useState(filters?.role || '');

    const { data, setData, put, processing, reset } = useForm({
        roles: [],
        permissions: [],
    });

    const toggleModule = (module) => {
        setExpandedModules(prev => ({
            ...prev,
            [module]: !prev[module]
        }));
    };

    const handleEditRoles = (user) => {
        setSelectedUser(user);
        setEditMode('roles');
        setData({
            roles: user.roles || [],
            permissions: user.permissions || [],
        });
    };

    const handleEditPermissions = (user) => {
        setSelectedUser(user);
        setEditMode('permissions');
        setData({
            roles: user.roles || [],
            permissions: user.permissions || [],
        });
    };

    const handleSave = (e) => {
        e.preventDefault();
        const endpoint = editMode === 'roles' 
            ? `/admin/roles-permissions/users/${selectedUser.id}/roles`
            : `/admin/roles-permissions/users/${selectedUser.id}/permissions`;
        
        put(endpoint, {
            onSuccess: () => {
                setSelectedUser(null);
                setEditMode(null);
                reset();
            },
        });
    };

    const handleSearch = (e) => {
        e.preventDefault();
        router.get('/admin/roles-permissions/users', {
            search: searchTerm,
            role: roleFilter,
        }, { preserveState: true });
    };

    const toggleRole = (role) => {
        setData('roles', 
            data.roles.includes(role)
                ? data.roles.filter(r => r !== role)
                : [...data.roles, role]
        );
    };

    const togglePermission = (permission) => {
        setData('permissions', 
            data.permissions.includes(permission)
                ? data.permissions.filter(p => p !== permission)
                : [...data.permissions, permission]
        );
    };

    const toggleAllInModule = (modulePermissions) => {
        const permNames = modulePermissions.map(p => p.name);
        const allSelected = permNames.every(p => data.permissions.includes(p));
        
        if (allSelected) {
            setData('permissions', data.permissions.filter(p => !permNames.includes(p)));
        } else {
            setData('permissions', [...new Set([...data.permissions, ...permNames])]);
        }
    };

    const getModuleLabel = (module) => {
        const labels = {
            dashboard: 'Dashboard',
            campaigns: 'Campaigns',
            domains: 'Domains',
            backlinks: 'Backlinks',
            marketing: 'Marketing Pages',
            admin_dashboard: 'Admin Dashboard',
            admin_users: 'Admin Users',
            admin_roles: 'Admin Roles & Permissions',
        };
        return labels[module] || module.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    };

    return (
        <AdminLayout header="User Permissions">
            <div className="space-y-6">
                {/* Header */}
                <div className="flex justify-between items-center">
                    <div>
                        <h2 className="text-lg font-semibold text-gray-900">Manage User Access</h2>
                        <p className="text-sm text-gray-600">Assign roles and direct permissions to individual users.</p>
                    </div>
                    <Link href="/admin/roles-permissions">
                        <Button variant="secondary">
                            ← Back to Roles
                        </Button>
                    </Link>
                </div>

                {/* Search & Filter */}
                <Card className="p-4 bg-white border border-gray-200">
                    <form onSubmit={handleSearch} className="flex gap-4">
                        <div className="flex-1">
                            <input
                                type="text"
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                placeholder="Search by name or email..."
                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                        <select
                            value={roleFilter}
                            onChange={(e) => setRoleFilter(e.target.value)}
                            className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="">All Roles</option>
                            {roles.map((role) => (
                                <option key={role} value={role}>{role}</option>
                            ))}
                        </select>
                        <Button type="submit">Search</Button>
                    </form>
                </Card>

                {/* Users Table */}
                <Card className="bg-white border border-gray-200 shadow-md">
                    {users?.data && users.data.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">User</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Roles</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Direct Permissions</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {users.data.map((user) => (
                                        <tr key={user.id} className="hover:bg-gray-50 transition-colors">
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="flex items-center">
                                                    <div className="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-700 font-bold text-sm">
                                                        {user.name?.charAt(0).toUpperCase() || 'U'}
                                                    </div>
                                                    <div className="ml-3">
                                                        <div className="text-sm font-medium text-gray-900">{user.name}</div>
                                                        <div className="text-sm text-gray-500">{user.email}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="flex flex-wrap gap-1">
                                                    {user.roles.map((role) => (
                                                        <span 
                                                            key={role} 
                                                            className={`px-2 py-1 text-xs font-medium rounded ${
                                                                role === 'admin' 
                                                                    ? 'bg-purple-100 text-purple-800' 
                                                                    : 'bg-blue-100 text-blue-800'
                                                            }`}
                                                        >
                                                            {role}
                                                        </span>
                                                    ))}
                                                    {user.roles.length === 0 && (
                                                        <span className="text-gray-400 text-sm">No roles</span>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="flex flex-wrap gap-1 max-w-xs">
                                                    {user.permissions.slice(0, 3).map((perm) => (
                                                        <span key={perm} className="px-2 py-0.5 text-xs bg-gray-100 text-gray-700 rounded">
                                                            {perm.split('.').pop()}
                                                        </span>
                                                    ))}
                                                    {user.permissions.length > 3 && (
                                                        <span className="px-2 py-0.5 text-xs bg-gray-200 text-gray-600 rounded">
                                                            +{user.permissions.length - 3} more
                                                        </span>
                                                    )}
                                                    {user.permissions.length === 0 && (
                                                        <span className="text-gray-400 text-sm">None</span>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="flex gap-2">
                                                    <Button 
                                                        variant="secondary" 
                                                        size="sm"
                                                        onClick={() => handleEditRoles(user)}
                                                    >
                                                        Edit Roles
                                                    </Button>
                                                    <Button 
                                                        variant="secondary" 
                                                        size="sm"
                                                        onClick={() => handleEditPermissions(user)}
                                                    >
                                                        Edit Permissions
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <div className="text-center py-16">
                            <div className="inline-block p-6 bg-gray-100 rounded-full mb-4">
                                <span className="text-5xl">👥</span>
                            </div>
                            <p className="text-gray-500 font-medium text-lg">No users found</p>
                        </div>
                    )}

                    {/* Pagination */}
                    {users?.links && users.links.length > 3 && (
                        <div className="px-6 py-4 border-t border-gray-200 bg-gray-50">
                            <div className="flex flex-col sm:flex-row items-center justify-between gap-4">
                                <div className="text-sm text-gray-700">
                                    Showing <span className="font-medium">{users.from || 0}</span> to <span className="font-medium">{users.to || 0}</span> of <span className="font-medium">{users.total || 0}</span> results
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    {users.links.map((link, index) => (
                                        <Link
                                            key={index}
                                            href={link.url || '#'}
                                            className={`px-3 py-2 text-sm font-medium rounded-md transition-colors ${
                                                link.active
                                                    ? 'bg-gray-900 text-white'
                                                    : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300'
                                            }`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </div>
                            </div>
                        </div>
                    )}
                </Card>

                {/* Edit Modal */}
                {selectedUser && editMode && (
                    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                        <div className="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-hidden">
                            <div className="p-6 border-b border-gray-200">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <h3 className="text-lg font-semibold text-gray-900">
                                            {editMode === 'roles' ? 'Edit Roles' : 'Edit Direct Permissions'}
                                        </h3>
                                        <p className="text-sm text-gray-500">{selectedUser.name} ({selectedUser.email})</p>
                                    </div>
                                    <button 
                                        onClick={() => {
                                            setSelectedUser(null);
                                            setEditMode(null);
                                            reset();
                                        }}
                                        className="text-gray-400 hover:text-gray-600"
                                    >
                                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <form onSubmit={handleSave}>
                                <div className="p-6 overflow-y-auto max-h-[60vh]">
                                    {editMode === 'roles' ? (
                                        <div className="space-y-4">
                                            <p className="text-sm text-gray-600 mb-4">
                                                Select roles for this user. Role permissions are automatically inherited.
                                            </p>
                                            {roles.map((role) => (
                                                <label key={role} className="flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                                                    <input
                                                        type="checkbox"
                                                        checked={data.roles.includes(role)}
                                                        onChange={() => toggleRole(role)}
                                                        className="h-5 w-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                                    />
                                                    <div>
                                                        <span className={`font-medium capitalize ${
                                                            role === 'admin' ? 'text-purple-700' : 'text-gray-900'
                                                        }`}>
                                                            {role}
                                                        </span>
                                                        {role === 'admin' && (
                                                            <span className="ml-2 text-xs text-purple-600">(Full access)</span>
                                                        )}
                                                    </div>
                                                </label>
                                            ))}
                                        </div>
                                    ) : (
                                        <div className="space-y-4">
                                            <p className="text-sm text-gray-600 mb-4">
                                                Assign direct permissions in addition to role-based permissions.
                                            </p>
                                            {Object.entries(permissions).map(([module, modulePerms]) => (
                                                <div key={module} className="border border-gray-200 rounded-lg overflow-hidden">
                                                    <button
                                                        type="button"
                                                        onClick={() => toggleModule(module)}
                                                        className="w-full px-4 py-3 bg-gray-50 flex items-center justify-between hover:bg-gray-100 transition-colors"
                                                    >
                                                        <div className="flex items-center gap-3">
                                                            <input
                                                                type="checkbox"
                                                                checked={modulePerms.every(p => data.permissions.includes(p.name))}
                                                                onChange={() => toggleAllInModule(modulePerms)}
                                                                onClick={(e) => e.stopPropagation()}
                                                                className="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                                            />
                                                            <span className="font-medium text-gray-900">
                                                                {getModuleLabel(module)}
                                                            </span>
                                                            <span className="text-xs text-gray-500">
                                                                ({modulePerms.filter(p => data.permissions.includes(p.name)).length}/{modulePerms.length})
                                                            </span>
                                                        </div>
                                                        <svg 
                                                            className={`w-5 h-5 text-gray-500 transition-transform ${expandedModules[module] ? 'rotate-180' : ''}`}
                                                            fill="none" 
                                                            stroke="currentColor" 
                                                            viewBox="0 0 24 24"
                                                        >
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                                        </svg>
                                                    </button>
                                                    
                                                    {expandedModules[module] && (
                                                        <div className="p-4 grid grid-cols-2 gap-3">
                                                            {modulePerms.map((perm) => (
                                                                <label key={perm.name} className="flex items-center gap-2 cursor-pointer">
                                                                    <input
                                                                        type="checkbox"
                                                                        checked={data.permissions.includes(perm.name)}
                                                                        onChange={() => togglePermission(perm.name)}
                                                                        className="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                                                    />
                                                                    <span className="text-sm text-gray-700">{perm.label}</span>
                                                                </label>
                                                            ))}
                                                        </div>
                                                    )}
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </div>

                                <div className="p-6 border-t border-gray-200 bg-gray-50 flex justify-end gap-3">
                                    <Button 
                                        type="button" 
                                        variant="secondary"
                                        onClick={() => {
                                            setSelectedUser(null);
                                            setEditMode(null);
                                            reset();
                                        }}
                                    >
                                        Cancel
                                    </Button>
                                    <Button type="submit" disabled={processing}>
                                        {processing ? 'Saving...' : 'Save Changes'}
                                    </Button>
                                </div>
                            </form>
                        </div>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
