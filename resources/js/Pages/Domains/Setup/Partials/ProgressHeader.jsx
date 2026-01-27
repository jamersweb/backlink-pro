import Button from '@/Components/Shared/Button';
import Card from '@/Components/Shared/Card';

export default function ProgressHeader({ domain, completedCount, totalSteps, onComplete }) {
    const progressPercent = (completedCount / totalSteps) * 100;

    return (
        <Card>
            <div className="p-6">
                <div className="flex justify-between items-center mb-4">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Setup {domain.name}</h1>
                        <p className="text-sm text-gray-500 mt-1">
                            Complete these steps to get the most out of BacklinkPro
                        </p>
                    </div>
                    <Button variant="primary" onClick={onComplete}>
                        Finish Setup
                    </Button>
                </div>

                {/* Progress Bar */}
                <div className="mt-4">
                    <div className="flex justify-between items-center mb-2">
                        <span className="text-sm font-medium text-gray-700">
                            Progress: {completedCount} of {totalSteps} steps completed
                        </span>
                        <span className="text-sm text-gray-500">
                            {Math.round(progressPercent)}%
                        </span>
                    </div>
                    <div className="w-full bg-gray-200 rounded-full h-3">
                        <div
                            className="bg-blue-600 h-3 rounded-full transition-all duration-300"
                            style={{ width: `${progressPercent}%` }}
                        />
                    </div>
                </div>
            </div>
        </Card>
    );
}


