import { Link, router } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import AppLayout from '@/Components/Layout/AppLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';
import PlanHeader from './Partials/PlanHeader';
import PlannerBuckets from './Partials/PlannerBuckets';

export default function PlannerIndex({ domain, plan, tasks }) {
    const [isGenerating, setIsGenerating] = useState(false);

    const handleGenerate = () => {
        setIsGenerating(true);
        router.post(`/domains/${domain.id}/planner/generate`, {}, {
            onFinish: () => {
                // Poll for plan completion
                setTimeout(() => {
                    router.reload({ only: ['plan'] });
                    setIsGenerating(false);
                }, 3000);
            },
        });
    };

    const handleApply = () => {
        if (confirm('Apply this plan? This will create or update tasks in your task board.')) {
            router.post(`/domains/${domain.id}/planner/apply`, {}, {
                onSuccess: () => {
                    router.reload();
                },
            });
        }
    };

    return (
        <AppLayout header="Action Planner">
            <div className="space-y-6">
                {/* Breadcrumb */}
                <div className="flex items-center gap-2 text-sm text-gray-600">
                    <Link href="/domains" className="hover:text-gray-900">Domains</Link>
                    <span>/</span>
                    <Link href={`/domains/${domain.id}`} className="hover:text-gray-900">{domain.name}</Link>
                    <span>/</span>
                    <Link href={`/domains/${domain.id}/insights`} className="hover:text-gray-900">Insights</Link>
                    <span>/</span>
                    <span className="text-gray-900">Action Planner</span>
                </div>

                {/* Header */}
                <PlanHeader
                    domain={domain}
                    plan={plan}
                    onGenerate={handleGenerate}
                    onApply={handleApply}
                    isGenerating={isGenerating}
                />

                {/* Planner Buckets */}
                {plan ? (
                    <PlannerBuckets plan={plan} tasks={tasks} domain={domain} />
                ) : (
                    <Card>
                        <div className="p-6 text-center">
                            <p className="text-gray-500 mb-4">
                                No plan generated yet. Click "Generate Plan" to create an action plan based on your latest data.
                            </p>
                        </div>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}


