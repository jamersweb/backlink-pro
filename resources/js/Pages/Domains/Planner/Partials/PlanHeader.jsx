import Button from '@/Components/Shared/Button';
import Card from '@/Components/Shared/Card';

export default function PlanHeader({ domain, plan, onGenerate, onApply, isGenerating }) {
    return (
        <Card>
            <div className="p-6">
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Action Planner</h1>
                        <p className="text-sm text-gray-500 mt-1">
                            AI-powered recommendations to improve your SEO performance
                        </p>
                    </div>
                    <div className="flex gap-3">
                        <Button
                            variant="outline"
                            onClick={onGenerate}
                            disabled={isGenerating}
                        >
                            {isGenerating ? 'Generating...' : 'Generate Plan'}
                        </Button>
                        {plan && plan.status === 'draft' && (
                            <Button
                                variant="primary"
                                onClick={onApply}
                            >
                                Apply Plan
                            </Button>
                        )}
                    </div>
                </div>

                {plan && (
                    <div className="mt-4 pt-4 border-t border-gray-200">
                        <div className="flex items-center gap-4 text-sm text-gray-600">
                            <span>
                                <strong>Generated:</strong> {new Date(plan.generated_at).toLocaleString()}
                            </span>
                            <span>
                                <strong>Period:</strong> {plan.period_days} days
                            </span>
                            <span>
                                <strong>Items:</strong> {plan.plan_json?.length || 0}
                            </span>
                            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${
                                plan.status === 'draft' ? 'bg-yellow-100 text-yellow-800' :
                                plan.status === 'applied' ? 'bg-green-100 text-green-800' :
                                'bg-gray-100 text-gray-800'
                            }`}>
                                {plan.status}
                            </span>
                        </div>
                    </div>
                )}
            </div>
        </Card>
    );
}


