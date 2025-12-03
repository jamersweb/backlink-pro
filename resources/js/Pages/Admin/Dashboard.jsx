import AdminLayout from '../../Components/Layout/AdminLayout';
import Card from '../../Components/Shared/Card';
import { Link } from '@inertiajs/react';

export default function AdminDashboard({ stats = {}, recentCampaigns = [], recentBacklinks = [] }) {
    return (
        <AdminLayout header="Admin Dashboard">
            <div className="space-y-8">
                {/* Welcome Section */}
                <div className="bg-white rounded-lg shadow-md border border-gray-200 p-6 mb-6">
                    <div className="flex items-center justify-between">
                        <div>
                            <h2 className="text-2xl font-bold text-gray-900 mb-1">Welcome Back, Admin! 👋</h2>
                            <p className="text-gray-600">Here's what's happening with your platform today.</p>
                        </div>
                        <div className="hidden md:block">
                            <div className="text-5xl">📊</div>
                        </div>
                    </div>
                </div>

                {/* Stats Grid */}
                <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <Card className="bg-white border border-gray-200 shadow-md hover:shadow-lg transition-all duration-300">
                        <div className="flex items-center justify-between">
                            <div className="flex-1">
                                <p className="text-gray-600 text-sm font-medium mb-1 flex items-center gap-2">
                                    <span className="text-lg">👥</span>
                                    Total Users
                                </p>
                                <p className="text-3xl font-bold text-gray-900 mt-2">{stats?.total_users || 0}</p>
                                <p className="text-gray-500 text-xs mt-2">All registered users</p>
                            </div>
                            <div className="flex items-center justify-center h-16 w-16 rounded-lg bg-gray-100">
                                <svg className="h-8 w-8 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                        </div>
                    </Card>

                    <Card className="bg-white border border-gray-200 shadow-md hover:shadow-lg transition-all duration-300">
                        <div className="flex items-center justify-between">
                            <div className="flex-1">
                                <p className="text-gray-600 text-sm font-medium mb-1 flex items-center gap-2">
                                    <span className="text-lg">🚀</span>
                                    Active Campaigns
                                </p>
                                <p className="text-3xl font-bold text-gray-900 mt-2">{stats?.active_campaigns || 0}</p>
                                <p className="text-gray-500 text-xs mt-2">Currently running</p>
                            </div>
                            <div className="flex items-center justify-center h-16 w-16 rounded-lg bg-gray-100">
                                <svg className="h-8 w-8 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </Card>

                    <Card className="bg-white border border-gray-200 shadow-md hover:shadow-lg transition-all duration-300">
                        <div className="flex items-center justify-between">
                            <div className="flex-1">
                                <p className="text-gray-600 text-sm font-medium mb-1 flex items-center gap-2">
                                    <span className="text-lg">🔗</span>
                                    Total Backlinks
                                </p>
                                <p className="text-3xl font-bold text-gray-900 mt-2">{stats?.total_backlinks || 0}</p>
                                <p className="text-gray-500 text-xs mt-2">All time created</p>
                            </div>
                            <div className="flex items-center justify-center h-16 w-16 rounded-lg bg-gray-100">
                                <svg className="h-8 w-8 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                            </div>
                        </div>
                    </Card>

                    <Card className="bg-white border border-gray-200 shadow-md hover:shadow-lg transition-all duration-300">
                        <div className="flex items-center justify-between">
                            <div className="flex-1">
                                <p className="text-gray-600 text-sm font-medium mb-1 flex items-center gap-2">
                                    <span className="text-lg">⏳</span>
                                    Pending Tasks
                                </p>
                                <p className="text-3xl font-bold text-gray-900 mt-2">{stats?.pending_tasks || 0}</p>
                                <p className="text-gray-500 text-xs mt-2">Awaiting action</p>
                            </div>
                            <div className="flex items-center justify-center h-16 w-16 rounded-lg bg-gray-100">
                                <svg className="h-8 w-8 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </Card>
                </div>

                {/* Recent Activity */}
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <Card title={
                        <div className="flex items-center gap-2">
                            <span className="text-xl">📋</span>
                            <span>Recent Campaigns</span>
                        </div>
                    } className="bg-white border border-gray-200 shadow-md">
                        {recentCampaigns && recentCampaigns.length > 0 ? (
                            <div className="space-y-3">
                                {recentCampaigns.map((campaign) => (
                                    <Link 
                                        key={campaign.id} 
                                        href={`/admin/campaigns/${campaign.id}`}
                                        className="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200 hover:border-gray-300 hover:shadow-md transition-all duration-200 group"
                                    >
                                        <div className="flex items-center gap-3 flex-1 min-w-0">
                                            <div className="h-10 w-10 rounded-lg bg-gray-200 flex items-center justify-center text-gray-700 font-bold text-sm group-hover:bg-gray-300 transition-colors">
                                                {campaign.name?.charAt(0).toUpperCase() || 'C'}
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <p className="text-sm font-semibold text-gray-900 truncate group-hover:text-gray-700 transition-colors">{campaign.name}</p>
                                                <p className="text-xs text-gray-600 mt-1 flex items-center gap-1">
                                                    <span>👤</span>
                                                    <span>{campaign.user?.name || 'Unknown'}</span>
                                                </p>
                                            </div>
                                        </div>
                                        <span className={`ml-3 px-3 py-1 text-xs font-semibold rounded-full ${
                                            campaign.status === 'active' ? 'bg-green-100 text-green-800 border border-green-200' :
                                            campaign.status === 'paused' ? 'bg-yellow-100 text-yellow-800 border border-yellow-200' :
                                            'bg-gray-100 text-gray-800 border border-gray-200'
                                        }`}>
                                            {campaign.status}
                                        </span>
                                    </Link>
                                ))}
                            </div>
                        ) : (
                            <div className="text-center py-12">
                                <div className="inline-block p-6 bg-gray-100 rounded-full mb-4">
                                    <span className="text-5xl">📋</span>
                                </div>
                                <p className="text-gray-500 font-medium">No campaigns yet</p>
                                <p className="text-gray-400 text-sm mt-2">Campaigns will appear here once created</p>
                            </div>
                        )}
                    </Card>

                    <Card title={
                        <div className="flex items-center gap-2">
                            <span className="text-xl">🔗</span>
                            <span>Recent Backlinks</span>
                        </div>
                    } className="bg-white border border-gray-200 shadow-md">
                        {recentBacklinks && recentBacklinks.length > 0 ? (
                            <div className="space-y-3">
                                {recentBacklinks.map((backlink) => (
                                    <div 
                                        key={backlink.id} 
                                        className="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200 hover:border-gray-300 hover:shadow-md transition-all duration-200 group"
                                    >
                                        <div className="flex items-center gap-3 flex-1 min-w-0">
                                            <div className={`h-10 w-10 rounded-lg flex items-center justify-center text-white font-bold text-sm ${
                                                backlink.status === 'verified' ? 'bg-green-500' :
                                                backlink.status === 'pending' ? 'bg-yellow-500' :
                                                'bg-red-500'
                                            }`}>
                                                {backlink.status === 'verified' ? '✓' : backlink.status === 'pending' ? '⏳' : '✗'}
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <p className="text-sm font-semibold text-gray-900 truncate">{backlink.url}</p>
                                                <p className="text-xs text-gray-600 mt-1 flex items-center gap-1">
                                                    <span>📊</span>
                                                    <span>{backlink.campaign?.name || 'Unknown Campaign'}</span>
                                                </p>
                                            </div>
                                        </div>
                                        <span className={`ml-3 px-3 py-1 text-xs font-semibold rounded-full ${
                                            backlink.status === 'verified' ? 'bg-green-100 text-green-800 border border-green-200' :
                                            backlink.status === 'pending' ? 'bg-yellow-100 text-yellow-800 border border-yellow-200' :
                                            'bg-red-100 text-red-800 border border-red-200'
                                        }`}>
                                            {backlink.status}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="text-center py-12">
                                <div className="inline-block p-6 bg-gray-100 rounded-full mb-4">
                                    <span className="text-5xl">🔗</span>
                                </div>
                                <p className="text-gray-500 font-medium">No backlinks yet</p>
                                <p className="text-gray-400 text-sm mt-2">Backlinks will appear here once created</p>
                            </div>
                        )}
                    </Card>
                </div>
            </div>
        </AdminLayout>
    );
}

