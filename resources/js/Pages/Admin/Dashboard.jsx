import AdminLayout from '../../Components/Layout/AdminLayout';
import Card from '../../Components/Shared/Card';

export default function AdminDashboard({ stats = {}, recentCampaigns = [], recentBacklinks = [] }) {
    return (
        <AdminLayout header="Admin Dashboard">
            <div className="space-y-8">
                {/* Stats Grid */}
                <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <Card className="bg-gradient-to-br from-red-500 to-red-600 text-white border-0 shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
                        <div className="flex items-center justify-between">
                            <div className="flex-1">
                                <p className="text-red-100 text-sm font-medium mb-1">Total Users</p>
                                <p className="text-3xl font-bold">{stats?.total_users || 0}</p>
                            </div>
                            <div className="flex items-center justify-center h-16 w-16 rounded-xl bg-white bg-opacity-20 backdrop-blur-sm">
                                <svg className="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLineCap="round" strokeLineJoin="round" strokeWidth={2} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                        </div>
                    </Card>

                    <Card className="bg-gradient-to-br from-green-500 to-green-600 text-white border-0 shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
                        <div className="flex items-center justify-between">
                            <div className="flex-1">
                                <p className="text-green-100 text-sm font-medium mb-1">Active Campaigns</p>
                                <p className="text-3xl font-bold">{stats?.active_campaigns || 0}</p>
                            </div>
                            <div className="flex items-center justify-center h-16 w-16 rounded-xl bg-white bg-opacity-20 backdrop-blur-sm">
                                <svg className="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLineCap="round" strokeLineJoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </Card>

                    <Card className="bg-gradient-to-br from-red-600 to-red-700 text-white border-0 shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
                        <div className="flex items-center justify-between">
                            <div className="flex-1">
                                <p className="text-red-100 text-sm font-medium mb-1">Total Backlinks</p>
                                <p className="text-3xl font-bold">{stats?.total_backlinks || 0}</p>
                            </div>
                            <div className="flex items-center justify-center h-16 w-16 rounded-xl bg-white bg-opacity-20 backdrop-blur-sm">
                                <svg className="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLineCap="round" strokeLineJoin="round" strokeWidth={2} d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                            </div>
                        </div>
                    </Card>

                    <Card className="bg-gradient-to-br from-green-600 to-green-700 text-white border-0 shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
                        <div className="flex items-center justify-between">
                            <div className="flex-1">
                                <p className="text-green-100 text-sm font-medium mb-1">Pending Tasks</p>
                                <p className="text-3xl font-bold">{stats?.pending_tasks || 0}</p>
                            </div>
                            <div className="flex items-center justify-center h-16 w-16 rounded-xl bg-white bg-opacity-20 backdrop-blur-sm">
                                <svg className="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLineCap="round" strokeLineJoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </Card>
                </div>

                {/* Recent Activity */}
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <Card title="Recent Campaigns" className="border-l-4 border-red-500 shadow-lg">
                        {recentCampaigns && recentCampaigns.length > 0 ? (
                            <div className="space-y-3">
                                {recentCampaigns.map((campaign) => (
                                    <div key={campaign.id} className="flex items-center justify-between p-4 bg-gradient-to-r from-red-50 to-white rounded-lg border border-red-100 hover:shadow-md transition-all duration-200">
                                        <div>
                                            <p className="text-sm font-semibold text-gray-900">{campaign.name}</p>
                                            <p className="text-xs text-gray-600 mt-1">by {campaign.user?.name}</p>
                                        </div>
                                        <span className={`px-3 py-1.5 text-xs font-bold rounded-full shadow-sm ${
                                            campaign.status === 'active' ? 'bg-gradient-to-r from-green-500 to-green-600 text-white' :
                                            campaign.status === 'paused' ? 'bg-gradient-to-r from-yellow-400 to-yellow-500 text-white' :
                                            'bg-gradient-to-r from-gray-400 to-gray-500 text-white'
                                        }`}>
                                            {campaign.status}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-gray-500 text-center py-8">No campaigns yet</p>
                        )}
                    </Card>

                    <Card title="Recent Backlinks" className="border-l-4 border-green-500 shadow-lg">
                        {recentBacklinks && recentBacklinks.length > 0 ? (
                            <div className="space-y-3">
                                {recentBacklinks.map((backlink) => (
                                    <div key={backlink.id} className="flex items-center justify-between p-4 bg-gradient-to-r from-green-50 to-white rounded-lg border border-green-100 hover:shadow-md transition-all duration-200">
                                        <div className="flex-1 min-w-0">
                                            <p className="text-sm font-semibold text-gray-900 truncate">{backlink.url}</p>
                                            <p className="text-xs text-gray-600 mt-1">{backlink.campaign?.name}</p>
                                        </div>
                                        <span className={`ml-2 px-3 py-1.5 text-xs font-bold rounded-full shadow-sm ${
                                            backlink.status === 'verified' ? 'bg-gradient-to-r from-green-500 to-green-600 text-white' :
                                            backlink.status === 'pending' ? 'bg-gradient-to-r from-yellow-400 to-yellow-500 text-white' :
                                            'bg-gradient-to-r from-red-500 to-red-600 text-white'
                                        }`}>
                                            {backlink.status}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-gray-500 text-center py-8">No backlinks yet</p>
                        )}
                    </Card>
                </div>
            </div>
        </AdminLayout>
    );
}

