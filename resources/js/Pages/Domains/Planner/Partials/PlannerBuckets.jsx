import Card from '@/Components/Shared/Card';
import TaskCard from './TaskCard';

export default function PlannerBuckets({ plan, tasks, domain }) {
    const planItems = plan?.plan_json || [];
    
    // Group plan items by planner_group
    const planByGroup = {
        today: planItems.filter(item => item.planner_group === 'today'),
        week: planItems.filter(item => item.planner_group === 'week'),
        month: planItems.filter(item => item.planner_group === 'month'),
    };

    return (
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {/* Today */}
            <div>
                <Card>
                    <div className="p-4 border-b border-gray-200">
                        <h2 className="text-lg font-semibold text-gray-900">Today</h2>
                        <p className="text-xs text-gray-500 mt-1">
                            {planByGroup.today.length} items
                        </p>
                    </div>
                    <div className="p-4 space-y-4 max-h-[600px] overflow-y-auto">
                        {planByGroup.today.length > 0 ? (
                            planByGroup.today.map((item, idx) => (
                                <TaskCard
                                    key={idx}
                                    item={item}
                                    domain={domain}
                                    isFromPlan={true}
                                />
                            ))
                        ) : (
                            <p className="text-sm text-gray-500 text-center py-4">No items for today</p>
                        )}
                    </div>
                </Card>
            </div>

            {/* This Week */}
            <div>
                <Card>
                    <div className="p-4 border-b border-gray-200">
                        <h2 className="text-lg font-semibold text-gray-900">This Week</h2>
                        <p className="text-xs text-gray-500 mt-1">
                            {planByGroup.week.length} items
                        </p>
                    </div>
                    <div className="p-4 space-y-4 max-h-[600px] overflow-y-auto">
                        {planByGroup.week.length > 0 ? (
                            planByGroup.week.map((item, idx) => (
                                <TaskCard
                                    key={idx}
                                    item={item}
                                    domain={domain}
                                    isFromPlan={true}
                                />
                            ))
                        ) : (
                            <p className="text-sm text-gray-500 text-center py-4">No items for this week</p>
                        )}
                    </div>
                </Card>
            </div>

            {/* This Month */}
            <div>
                <Card>
                    <div className="p-4 border-b border-gray-200">
                        <h2 className="text-lg font-semibold text-gray-900">This Month</h2>
                        <p className="text-xs text-gray-500 mt-1">
                            {planByGroup.month.length} items
                        </p>
                    </div>
                    <div className="p-4 space-y-4 max-h-[600px] overflow-y-auto">
                        {planByGroup.month.length > 0 ? (
                            planByGroup.month.map((item, idx) => (
                                <TaskCard
                                    key={idx}
                                    item={item}
                                    domain={domain}
                                    isFromPlan={true}
                                />
                            ))
                        ) : (
                            <p className="text-sm text-gray-500 text-center py-4">No items for this month</p>
                        )}
                    </div>
                </Card>
            </div>
        </div>
    );
}


