import { useState } from 'react';
import AdminLayout from '@/Components/Layout/AdminLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';
import { Link, useForm, router } from '@inertiajs/react';

export default function RolesPermissionsIndex({ roles, permissions, permissionsList }) {
    const [selectedRole, setSelectedRole] = useState(null);
    const [showCreateModal, setShowCreateModal] = useState(false);
    const [expandedModules, setExpandedModules] = useState({});

    const { data, setData, post, put, processing, reset, errors } = useForm({
        name: '',
        permissions: [],
    });

    const toggleModule = (module) => {
        setExpandedModules(prev => ({
            ...prev,
            [module]: !prev[module]
        }));
    };

    const handleEditRole = (role) => {
        setSelectedRole(role);
        setData({
            name: role.name,
            permissions: role.permissions || [],
        });
    };

    const handleCreateRole = () => {
        setShowCreateModal(true);
        setSelectedRole(null);
        reset();
    };

    const handleSaveRole = (e) => {
        e.preventDefault();
        if (selectedRole) {
            put(`/admin/roles-permissions/roles/${selectedRole.id}`, {
                onSuccess: () => {
                    setSelectedRole(null);
                    reset();
                },
            });
        } else {
            post('/admin/roles-permissions/roles', {
                onSuccess: () => {
                    setShowCreateModal(false);
                    reset();
                },
            });
        }
    };

    const handleDeleteRole = (roleId) => {
        if (confirm('Are you sure you want to delete this role?')) {
            router.delete(`/admin/roles-permissions/roles/${roleId}`);
        }
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
            domain_audits: 'Domain Audits',
            domain_backlinks: 'Domain Backlinks',
            domain_meta: 'Domain Meta Editor',
            domain_content: 'Domain Content',
            rank_tracking: 'Rank Tracking',
            domain_insights: 'Domain Insights',
            domain_planner: 'Domain Planner',
            domain_automation: 'Domain Automation',
            domain_reports: 'Domain Reports',
            domain_integrations: 'Domain Integrations',
            site_accounts: 'Site Accounts',
            settings: 'Settings',
            notifications: 'Notifications',
            team: 'Team',
            profile: 'Profile',
            subscription: 'Subscription',
            reports: 'Reports',
            activity: 'Activity',
            gmail: 'Gmail/OAuth',
            marketing: 'Marketing Pages',
            admin_dashboard: 'Admin Dashboard',
            admin_users: 'Admin Users',
            admin_plans: 'Admin Plans',
            admin_subscriptions: 'Admin Subscriptions',
            admin_leads: 'Admin Leads',
            admin_campaigns: 'Admin Campaigns',
            admin_categories: 'Admin Categories',
            admin_opportunities: 'Admin Opportunities',
            admin_backlinks: 'Admin Backlinks',
            admin_automation_tasks: 'Admin Automation Tasks',
            admin_proxies: 'Admin Proxies',
            admin_captcha: 'Admin Captcha',
            admin_system: 'Admin System Health',
            admin_settings: 'Admin Settings',
            admin_ml: 'Admin ML Training',
            admin_blocked_sites: 'Admin Blocked Sites',
            admin_marketing_leads: 'Admin Marketing Leads',
            admin_runs: 'Admin Runs',
            admin_roles: 'Admin Roles & Permissions',
        };
        return labels[module] || module.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    };

    return (
        <AdminLayout header="Roles & Permissions">
            <div className="space-y-6">
                {/* Header Actions */}
                <div className="flex justify-between items-center">
                    <div>
                        <h2 className="text-lg font-semibold text-gray-900">Manage Access Control</h2>
                        <p className="text-sm text-gray-600">Create roles and assign permissions to control what users can access.</p>
                    </div>
                    <div className="flex gap-3">
                        <Link href="/admin/roles-permissions/users">
                            <Button variant="secondary">
                                👥 Manage User Permissions
                            </Button>
                        </Link>
                        <Button onClick={handleCreateRole}>
                            + Create Role
                        </Button>
                    </div>
                </div>

                {/* Roles Grid */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {roles.map((role) => (
                        <Card key={role.id} className="p-6 bg-white border border-gray-200 shadow-md">
                            <div className="flex items-start justify-between mb-4">
                                <div>
                                    <h3 className="text-lg font-semibold text-gray-900 capitalize">{role.name}</h3>
                                    <p className="text-sm text-gray-500">{role.users_count} users</p>
                                </div>
                                <span className={`px-2 py-1 text-xs font-medium rounded ${
                                    role.name === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'
                                }`}>
                                    {role.permissions.length} permissions
                                </span>
                            </div>
                            
                            <div className="mb-4">
                                <p className="text-xs text-gray-500 mb-2">Sample permissions:</p>
                                <div className="flex flex-wrap gap-1">
                                    {role.permissions.slice(0, 5).map((perm) => (
                                        <span key={perm} className="px-2 py-0.5 text-xs bg-gray-100 text-gray-700 rounded">
                                            {perm.split('.').pop()}
                                        </span>
                                    ))}
                                    {role.permissions.length > 5 && (
                                        <span className="px-2 py-0.5 text-xs bg-gray-200 text-gray-600 rounded">
                                            +{role.permissions.length - 5} more
                                        </span>
                                    )}
                                </div>
                            </div>

                            <div className="flex gap-2">
                                <Button 
                                    variant="secondary" 
                                    size="sm" 
                                    onClick={() => handleEditRole(role)}
                                    disabled={role.name === 'admin'}
                                >
                                    Edit
                                </Button>
                                {!['admin', 'user'].includes(role.name) && (
                                    <Button 
                                        variant="danger" 
                                        size="sm" 
                                        onClick={() => handleDeleteRole(role.id)}
                                    >
                                        Delete
                                    </Button>
                                )}
                            </div>
                        </Card>
                    ))}
                </div>

                {/* Edit/Create Modal */}
                {(selectedRole || showCreateModal) && (
                    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                        <div className="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden">
                            <div className="p-6 border-b border-gray-200">
                                <div className="flex items-center justify-between">
                                    <h3 className="text-lg font-semibold text-gray-900">
                                        {selectedRole ? `Edit Role: ${selectedRole.name}` : 'Create New Role'}
                                    </h3>
                                    <button 
                                        onClick={() => {
                                            setSelectedRole(null);
                                            setShowCreateModal(false);
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

                            <form onSubmit={handleSaveRole}>
                                <div className="p-6 overflow-y-auto max-h-[60vh]">
                                    {/* Role Name */}
                                    {!selectedRole && (
                                        <div className="mb-6">
                                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                                Role Name
                                            </label>
                                            <input
                                                type="text"
                                                value={data.name}
                                                onChange={(e) => setData('name', e.target.value)}
                                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                placeholder="e.g., editor, manager, viewer"
                                            />
                                            {errors.name && (
                                                <p className="mt-1 text-sm text-red-600">{errors.name}</p>
                                            )}
                                        </div>
                                    )}

                                    {/* Permissions by Module */}
                                    <div className="space-y-4">
                                        <h4 className="text-sm font-semibold text-gray-900">Permissions</h4>
                                        
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
                                                    <div className="p-4 grid grid-cols-2 md:grid-cols-3 gap-3">
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
                                </div>

                                <div className="p-6 border-t border-gray-200 bg-gray-50 flex justify-end gap-3">
                                    <Button 
                                        type="button" 
                                        variant="secondary"
                                        onClick={() => {
                                            setSelectedRole(null);
                                            setShowCreateModal(false);
                                            reset();
                                        }}
                                    >
                                        Cancel
                                    </Button>
                                    <Button type="submit" disabled={processing}>
                                        {processing ? 'Saving...' : (selectedRole ? 'Update Role' : 'Create Role')}
                                    </Button>
                                </div>
                            </form>
                        </div>
                    </div>
                )}

                {/* Info Card */}
                <Card className="p-6 bg-blue-50 border border-blue-200">
                    <div className="flex items-start gap-4">
                        <div className="p-2 bg-blue-100 rounded-lg">
                            <svg className="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h4 className="font-semibold text-blue-900">About Roles & Permissions</h4>
                            <p className="text-sm text-blue-700 mt-1">
                                Roles are collections of permissions that can be assigned to users. 
                                The <strong>admin</strong> role has all permissions and cannot be modified.
                                Users can also have direct permissions assigned in addition to their role permissions.
                            </p>
                        </div>
                    </div>
                </Card>
            </div>
        </AdminLayout>
    );
}
