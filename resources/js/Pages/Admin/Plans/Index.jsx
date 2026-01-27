import AdminLayout from '../../../Components/Layout/AdminLayout';
import Card from '../../../Components/Shared/Card';
import { Link, router } from '@inertiajs/react';

export default function PlansIndex({ plans = [], total = 0 }) {
    const handleToggleActive = (planId) => {
        router.post(`/admin/plans/${planId}/toggle-active`, {}, {
            preserveScroll: true,
        });
    };

    const handleTogglePublic = (planId) => {
        router.post(`/admin/plans/${planId}/toggle-public`, {}, {
            preserveScroll: true,
        });
    };

    return (
        <AdminLayout header="Pricing Plans">
            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <p className="text-[#9CA3AF] mt-1">Manage your subscription plans and pricing</p>
                    </div>
                    <Link
                        href="/admin/plans/create"
                        className="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-[#2F6BFF] to-[#2457D6] hover:from-[#2457D6] hover:to-[#1E4BB8] text-white rounded-lg font-medium transition-all duration-200 shadow-lg shadow-[#2F6BFF]/20"
                    >
                        <i className="bi bi-plus-lg"></i>
                        Create Plan
                    </Link>
                </div>

                {/* Stats */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div className="bg-[#111827] border border-white/10 rounded-xl p-5">
                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 rounded-lg bg-[#2F6BFF]/15 flex items-center justify-center">
                                <i className="bi bi-box-seam text-[#5B8AFF]"></i>
                            </div>
                            <div>
                                <p className="text-2xl font-bold text-white">{total}</p>
                                <p className="text-sm text-[#6B7280]">Total Plans</p>
                            </div>
                        </div>
                    </div>
                    <div className="bg-[#111827] border border-white/10 rounded-xl p-5">
                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 rounded-lg bg-[#12B76A]/15 flex items-center justify-center">
                                <i className="bi bi-check-circle text-[#12B76A]"></i>
                            </div>
                            <div>
                                <p className="text-2xl font-bold text-white">{plans.filter(p => p.is_active).length}</p>
                                <p className="text-sm text-[#6B7280]">Active Plans</p>
                            </div>
                        </div>
                    </div>
                    <div className="bg-[#111827] border border-white/10 rounded-xl p-5">
                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 rounded-lg bg-[#B6F400]/15 flex items-center justify-center">
                                <i className="bi bi-eye text-[#B6F400]"></i>
                            </div>
                            <div>
                                <p className="text-2xl font-bold text-white">{plans.filter(p => p.is_public).length}</p>
                                <p className="text-sm text-[#6B7280]">Public Plans</p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Plans Grid */}
                <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    {plans.map((plan) => (
                        <div
                            key={plan.id}
                            className={`relative overflow-hidden rounded-2xl border transition-all duration-300 ${
                                plan.is_highlighted 
                                    ? 'bg-gradient-to-br from-[#111827] via-[#1F2937] to-[#111827] border-[#2F6BFF]/50 shadow-xl shadow-[#2F6BFF]/10' 
                                    : 'bg-[#111827] border-white/10 hover:border-white/20'
                            }`}
                        >
                            {/* Highlight Badge */}
                            {plan.is_highlighted && (
                                <div className="absolute top-0 right-0">
                                    <div className="bg-gradient-to-r from-[#2F6BFF] to-[#B6F400] text-[#0B0F14] text-xs font-bold px-3 py-1 rounded-bl-lg">
                                        {plan.badge || 'Popular'}
                                    </div>
                                </div>
                            )}

                            <div className="p-6">
                                {/* Plan Header */}
                                <div className="flex items-start justify-between mb-4">
                                    <div>
                                        <h3 className="text-xl font-bold text-white">{plan.name}</h3>
                                        {plan.tagline && (
                                            <p className="text-sm text-[#6B7280] mt-1">{plan.tagline}</p>
                                        )}
                                    </div>
                                    <div className="flex gap-2">
                                        <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                                            plan.is_active 
                                                ? 'bg-[#12B76A]/15 text-[#12B76A] border border-[#12B76A]/30' 
                                                : 'bg-[#F04438]/15 text-[#F04438] border border-[#F04438]/30'
                                        }`}>
                                            {plan.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                    </div>
                                </div>

                                {/* Pricing */}
                                <div className="mb-6">
                                    {plan.price_monthly !== null ? (
                                        <div className="flex items-baseline gap-1">
                                            <span className="text-4xl font-bold text-white">${plan.price_monthly}</span>
                                            <span className="text-[#6B7280]">/month</span>
                                        </div>
                                    ) : (
                                        <div className="text-2xl font-bold text-[#B6F400]">Custom Pricing</div>
                                    )}
                                    {plan.price_annual && (
                                        <p className="text-sm text-[#6B7280] mt-1">
                                            or ${plan.price_annual}/mo billed annually
                                        </p>
                                    )}
                                </div>

                                {/* Limits */}
                                {plan.display_limits && plan.display_limits.length > 0 && (
                                    <div className="space-y-2 mb-6">
                                        {plan.display_limits.map((limit, idx) => (
                                            <div key={idx} className="flex items-center justify-between text-sm">
                                                <span className="text-[#9CA3AF]">{limit.label}</span>
                                                <span className="text-white font-medium">{limit.value}</span>
                                            </div>
                                        ))}
                                    </div>
                                )}

                                {/* Features */}
                                {plan.includes && plan.includes.length > 0 && (
                                    <div className="space-y-2 mb-6 pt-4 border-t border-white/10">
                                        {plan.includes.slice(0, 4).map((feature, idx) => (
                                            <div key={idx} className="flex items-center gap-2 text-sm">
                                                <i className="bi bi-check2 text-[#12B76A]"></i>
                                                <span className="text-[#9CA3AF]">{feature}</span>
                                            </div>
                                        ))}
                                        {plan.includes.length > 4 && (
                                            <p className="text-xs text-[#6B7280]">+{plan.includes.length - 4} more features</p>
                                        )}
                                    </div>
                                )}

                                {/* Subscribers */}
                                <div className="flex items-center gap-2 text-sm text-[#6B7280] mb-6">
                                    <i className="bi bi-people"></i>
                                    <span>{plan.subscribers_count || 0} subscribers</span>
                                </div>

                                {/* Actions */}
                                <div className="flex items-center gap-3 pt-4 border-t border-white/10">
                                    <Link
                                        href={`/admin/plans/${plan.id}/edit`}
                                        className="flex-1 px-4 py-2 bg-white/5 hover:bg-white/10 text-[#E5E7EB] rounded-lg text-sm font-medium text-center transition-colors"
                                    >
                                        <i className="bi bi-pencil mr-2"></i>Edit
                                    </Link>
                                    <button
                                        onClick={() => handleToggleActive(plan.id)}
                                        className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
                                            plan.is_active
                                                ? 'bg-[#F04438]/15 hover:bg-[#F04438]/25 text-[#F04438]'
                                                : 'bg-[#12B76A]/15 hover:bg-[#12B76A]/25 text-[#12B76A]'
                                        }`}
                                        title={plan.is_active ? 'Deactivate' : 'Activate'}
                                    >
                                        <i className={`bi ${plan.is_active ? 'bi-pause-circle' : 'bi-play-circle'}`}></i>
                                    </button>
                                    <button
                                        onClick={() => handleTogglePublic(plan.id)}
                                        className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
                                            plan.is_public
                                                ? 'bg-[#2F6BFF]/15 hover:bg-[#2F6BFF]/25 text-[#5B8AFF]'
                                                : 'bg-white/5 hover:bg-white/10 text-[#6B7280]'
                                        }`}
                                        title={plan.is_public ? 'Hide from pricing' : 'Show on pricing'}
                                    >
                                        <i className={`bi ${plan.is_public ? 'bi-eye' : 'bi-eye-slash'}`}></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>

                {/* Empty State */}
                {plans.length === 0 && (
                    <Card variant="elevated">
                        <div className="text-center py-12">
                            <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white/5 mb-4">
                                <i className="bi bi-box-seam text-3xl text-[#6B7280]"></i>
                            </div>
                            <h3 className="text-lg font-medium text-[#E5E7EB] mb-2">No plans yet</h3>
                            <p className="text-[#6B7280] mb-6">Create your first pricing plan to get started</p>
                            <Link
                                href="/admin/plans/create"
                                className="inline-flex items-center gap-2 px-4 py-2.5 bg-[#2F6BFF] hover:bg-[#2457D6] text-white rounded-lg font-medium transition-colors"
                            >
                                <i className="bi bi-plus-lg"></i>
                                Create Plan
                            </Link>
                        </div>
                    </Card>
                )}
            </div>
        </AdminLayout>
    );
}
