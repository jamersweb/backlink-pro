import { useState, useEffect } from 'react';
import AdminLayout from '@/Components/Layout/AdminLayout';
import Card from '@/Components/Shared/Card';
import Button from '@/Components/Shared/Button';

export default function MLTrainingIndex({ stats, modelInfo, trainingLogs }) {
    const [isTraining, setIsTraining] = useState(false);
    const [trainResult, setTrainResult] = useState(null);
    const [formData, setFormData] = useState({
        model_type: '',
        since_days: 7,
        auto_deploy: true,
    });
    const [currentStats, setCurrentStats] = useState(stats);
    const [currentModelInfo, setCurrentModelInfo] = useState(modelInfo);

    // Poll for status updates when training
    useEffect(() => {
        let interval;
        if (isTraining) {
            interval = setInterval(async () => {
                try {
                    const response = await fetch('/admin/ml-training/status', {
                        headers: { 'Accept': 'application/json' },
                    });
                    const data = await response.json();
                    setCurrentStats(data.stats);
                    setCurrentModelInfo(data.model_info);
                } catch (e) {
                    console.error('Status poll error:', e);
                }
            }, 5000);
        }
        return () => clearInterval(interval);
    }, [isTraining]);

    const handleTrain = async () => {
        setIsTraining(true);
        setTrainResult(null);

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const response = await fetch('/admin/ml-training/train', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(formData),
            });

            const data = await response.json();
            setTrainResult(data);
            
            // Refresh model info
            const statusResponse = await fetch('/admin/ml-training/status', {
                headers: { 'Accept': 'application/json' },
            });
            const statusData = await statusResponse.json();
            setCurrentStats(statusData.stats);
            setCurrentModelInfo(statusData.model_info);
        } catch (error) {
            setTrainResult({
                success: false,
                message: 'Failed to start training: ' + error.message,
            });
        } finally {
            setIsTraining(false);
        }
    };

    const handleExport = () => {
        window.location.href = `/admin/ml-training/export?since_days=${formData.since_days}`;
    };

    return (
        <AdminLayout header="ML Model Training">
            <div className="space-y-6">
                {/* Header Stats */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card className="bg-gradient-to-br from-violet-500 to-purple-600 text-white">
                        <div className="p-5">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-violet-100 text-sm font-medium">Training Samples</p>
                                    <p className="text-3xl font-bold mt-1">{currentStats?.total_training_samples?.toLocaleString() || 0}</p>
                                </div>
                                <div className="text-4xl opacity-80">
                                    <i className="bi bi-database"></i>
                                </div>
                            </div>
                        </div>
                    </Card>

                    <Card className="bg-gradient-to-br from-emerald-500 to-teal-600 text-white">
                        <div className="p-5">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-emerald-100 text-sm font-medium">Success Rate</p>
                                    <p className="text-3xl font-bold mt-1">{currentStats?.success_rate || 0}%</p>
                                </div>
                                <div className="text-4xl opacity-80">
                                    <i className="bi bi-graph-up-arrow"></i>
                                </div>
                            </div>
                        </div>
                    </Card>

                    <Card className="bg-gradient-to-br from-amber-500 to-orange-600 text-white">
                        <div className="p-5">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-amber-100 text-sm font-medium">Recent Data (7d)</p>
                                    <p className="text-3xl font-bold mt-1">{currentStats?.recent_data_count?.toLocaleString() || 0}</p>
                                </div>
                                <div className="text-4xl opacity-80">
                                    <i className="bi bi-clock-history"></i>
                                </div>
                            </div>
                        </div>
                    </Card>

                    <Card className="bg-gradient-to-br from-sky-500 to-blue-600 text-white">
                        <div className="p-5">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sky-100 text-sm font-medium">Verified Backlinks</p>
                                    <p className="text-3xl font-bold mt-1">{currentStats?.verified_backlinks?.toLocaleString() || 0}</p>
                                </div>
                                <div className="text-4xl opacity-80">
                                    <i className="bi bi-patch-check"></i>
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Training Controls */}
                    <div className="lg:col-span-2">
                        <Card variant="elevated">
                            <div className="p-6">
                                <h3 className="text-lg font-bold text-[var(--admin-text)] mb-6 flex items-center gap-2">
                                    <i className="bi bi-cpu text-violet-600"></i>
                                    Train Model
                                </h3>

                                <div className="space-y-5">
                                    {/* Model Type Selection */}
                                    <div>
                                        <label className="block text-sm font-medium text-[var(--admin-text)] mb-2">
                                            Model Type
                                        </label>
                                        <select
                                            value={formData.model_type}
                                            onChange={(e) => setFormData({ ...formData, model_type: e.target.value })}
                                            className="admin-select w-full px-4 py-2.5 rounded-lg"
                                            disabled={isTraining}
                                        >
                                            <option value="">Auto (Best Available)</option>
                                            <option value="xgboost">XGBoost (Recommended)</option>
                                            <option value="lightgbm">LightGBM (Fast)</option>
                                            <option value="randomforest">Random Forest (Baseline)</option>
                                        </select>
                                        <p className="mt-1.5 text-xs text-[var(--admin-text-muted)]">
                                            XGBoost typically gives best results. LightGBM is faster for large datasets.
                                        </p>
                                    </div>

                                    {/* Training Period */}
                                    <div>
                                        <label className="block text-sm font-medium text-[var(--admin-text)] mb-2">
                                            Training Data Period (days)
                                        </label>
                                        <input
                                            type="number"
                                            min="1"
                                            max="365"
                                            value={formData.since_days}
                                            onChange={(e) => setFormData({ ...formData, since_days: parseInt(e.target.value) || 7 })}
                                            className="admin-input w-full px-4 py-2.5 rounded-lg"
                                            disabled={isTraining}
                                        />
                                        <p className="mt-1.5 text-xs text-[var(--admin-text-muted)]">
                                            Number of days to look back for collecting training feedback data.
                                        </p>
                                    </div>

                                    {/* Auto Deploy Toggle */}
                                    <div className="flex items-center justify-between p-4 bg-[var(--admin-surface-2)] rounded-lg border border-[var(--admin-border)]">
                                        <div>
                                            <label className="text-sm font-medium text-[var(--admin-text)]">
                                                Auto-deploy if better
                                            </label>
                                            <p className="text-xs text-[var(--admin-text-muted)] mt-0.5">
                                                Automatically deploy new model if it outperforms the current one
                                            </p>
                                        </div>
                                        <label className="relative inline-flex items-center cursor-pointer">
                                            <input
                                                type="checkbox"
                                                checked={formData.auto_deploy}
                                                onChange={(e) => setFormData({ ...formData, auto_deploy: e.target.checked })}
                                                className="sr-only peer"
                                                disabled={isTraining}
                                            />
                                            <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-violet-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-violet-600"></div>
                                        </label>
                                    </div>

                                    {/* Action Buttons */}
                                    <div className="flex gap-3 pt-2">
                                        <Button
                                            variant="primary"
                                            onClick={handleTrain}
                                            disabled={isTraining}
                                            className="flex-1 bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-700 hover:to-purple-700"
                                        >
                                            {isTraining ? (
                                                <>
                                                    <svg className="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                    Training in Progress...
                                                </>
                                            ) : (
                                                <>
                                                    <i className="bi bi-lightning-charge mr-2"></i>
                                                    Start Training
                                                </>
                                            )}
                                        </Button>
                                        <Button
                                            variant="secondary"
                                            onClick={handleExport}
                                            disabled={isTraining}
                                        >
                                            <i className="bi bi-download mr-2"></i>
                                            Export Data
                                        </Button>
                                    </div>

                                    {/* Training Result */}
                                    {trainResult && (
                                        <div className={`mt-4 p-4 rounded-lg border ${
                                            trainResult.success 
                                                ? 'bg-[#12B76A]/10 border-[#12B76A]/30 text-[#12B76A]' 
                                                : 'bg-[#F04438]/10 border-[#F04438]/30 text-[#F04438]'
                                        }`}>
                                            <div className="flex items-start gap-3">
                                                <i className={`bi ${trainResult.success ? 'bi-check-circle-fill text-emerald-500' : 'bi-x-circle-fill text-red-500'} text-xl`}></i>
                                                <div className="flex-1">
                                                    <p className="font-medium">{trainResult.message}</p>
                                                    
                                                    {/* Show error location if available */}
                                                    {!trainResult.success && trainResult.result?.error_location && (
                                                        <div className="mt-2 p-2 bg-[#F04438]/10 rounded text-xs font-mono">
                                                            <p className="font-semibold mb-1">Error Location:</p>
                                                            <pre className="whitespace-pre-wrap">{trainResult.result.error_location}</pre>
                                                        </div>
                                                    )}
                                                    
                                                    {/* Show detailed error if available */}
                                                    {!trainResult.success && trainResult.result?.error_details && (
                                                        <details className="mt-2">
                                                            <summary className="cursor-pointer text-sm font-semibold hover:underline">
                                                                Show Detailed Error
                                                            </summary>
                                                            <pre className="mt-2 text-xs bg-[var(--admin-surface)]/50 p-3 rounded overflow-x-auto max-h-60 whitespace-pre-wrap">
                                                                {trainResult.result.error_details}
                                                            </pre>
                                                        </details>
                                                    )}
                                                    
                                                    {/* Show output if available */}
                                                    {trainResult.result?.output && (
                                                        <details className="mt-2">
                                                            <summary className="cursor-pointer text-sm font-semibold hover:underline">
                                                                Show Output
                                                            </summary>
                                                            <pre className="mt-2 text-xs bg-[var(--admin-surface)]/50 p-2 rounded overflow-x-auto max-h-40">
                                                                {trainResult.result.output.slice(-1000)}
                                                            </pre>
                                                        </details>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </Card>
                    </div>

                    {/* Model Info Sidebar */}
                    <div className="space-y-6">
                        {/* Current Model Card */}
                        <Card variant="elevated">
                            <div className="p-6">
                                <h3 className="text-lg font-bold text-[var(--admin-text)] mb-4 flex items-center gap-2">
                                    <i className="bi bi-box text-violet-600"></i>
                                    Current Model
                                </h3>

                                {currentModelInfo?.model_exists ? (
                                    <div className="space-y-3">
                                        <div className="flex justify-between items-center py-2 border-b border-[var(--admin-border-light)]">
                                            <span className="text-sm text-[var(--admin-text-muted)]">Status</span>
                                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                                                <span className="w-1.5 h-1.5 mr-1.5 rounded-full bg-emerald-500"></span>
                                                Active
                                            </span>
                                        </div>
                                        {currentModelInfo.current_version && (
                                            <div className="flex justify-between items-center py-2 border-b border-[var(--admin-border-light)]">
                                                <span className="text-sm text-[var(--admin-text-muted)]">Version</span>
                                                <span className="text-sm font-mono font-medium text-[var(--admin-text)]">{currentModelInfo.current_version}</span>
                                            </div>
                                        )}
                                        <div className="flex justify-between items-center py-2 border-b border-[var(--admin-border-light)]">
                                            <span className="text-sm text-[var(--admin-text-muted)]">Last Updated</span>
                                            <span className="text-sm text-[var(--admin-text)]">{currentModelInfo.last_modified || 'N/A'}</span>
                                        </div>
                                        <div className="flex justify-between items-center py-2">
                                            <span className="text-sm text-[var(--admin-text-muted)]">File Size</span>
                                            <span className="text-sm text-[var(--admin-text)]">{currentModelInfo.file_size || 'N/A'}</span>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="text-center py-6">
                                        <i className="bi bi-exclamation-triangle text-4xl text-amber-500"></i>
                                        <p className="mt-2 text-sm text-[var(--admin-text-muted)]">No model trained yet</p>
                                        <p className="text-xs text-[var(--admin-text-dim)] mt-1">Train a model to get started</p>
                                    </div>
                                )}
                            </div>
                        </Card>

                        {/* Version History */}
                        {currentModelInfo?.versions?.length > 0 && (
                            <Card variant="elevated">
                                <div className="p-6">
                                    <h3 className="text-lg font-bold text-[var(--admin-text)] mb-4 flex items-center gap-2">
                                        <i className="bi bi-clock-history text-violet-600"></i>
                                        Version History
                                    </h3>
                                    <div className="space-y-2">
                                        {currentModelInfo.versions.map((version, idx) => (
                                            <div key={idx} className={`p-3 rounded-lg border ${
                                                version.version === currentModelInfo.current_version 
                                                    ? 'bg-violet-500/10 border-violet-500/30' 
                                                    : 'bg-[var(--admin-surface-2)] border-[var(--admin-border)]'
                                            }`}>
                                                <div className="flex items-center justify-between">
                                                    <span className="font-mono text-sm font-medium">{version.version}</span>
                                                    {version.version === currentModelInfo.current_version && (
                                                        <span className="text-xs bg-violet-600 text-white px-2 py-0.5 rounded">Current</span>
                                                    )}
                                                </div>
                                                {version.accuracy && (
                                                    <p className="text-xs text-[var(--admin-text-muted)] mt-1">
                                                        Accuracy: {(version.accuracy * 100).toFixed(1)}%
                                                    </p>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </Card>
                        )}
                    </div>
                </div>

                {/* Training Logs */}
                {trainingLogs?.length > 0 && (
                    <Card variant="elevated">
                        <div className="p-6">
                            <h3 className="text-lg font-bold text-[var(--admin-text)] mb-4 flex items-center gap-2">
                                <i className="bi bi-journal-text text-violet-600"></i>
                                Recent Training Logs
                            </h3>
                            <div className="overflow-x-auto">
                                <table className="admin-table min-w-full">
                                    <thead>
                                        <tr>
                                            <th>Timestamp</th>
                                            <th>Status</th>
                                            <th>Triggered By</th>
                                            <th>Details</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {trainingLogs.map((log, idx) => (
                                            <tr key={idx}>
                                                <td className="text-sm text-[var(--admin-text)]">
                                                    {new Date(log.timestamp).toLocaleString()}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                                        log.success 
                                                            ? 'bg-emerald-100 text-emerald-800' 
                                                            : 'bg-red-100 text-red-800'
                                                    }`}>
                                                        {log.success ? 'Success' : 'Failed'}
                                                    </span>
                                                </td>
                                                <td className="text-sm text-[var(--admin-text-muted)]">
                                                    {log.triggered_by || 'manual'}
                                                </td>
                                                <td className="text-sm">
                                                    {log.error ? (
                                                        <span className="admin-text-danger truncate block max-w-xs" title={log.error}>
                                                            {log.error.substring(0, 50)}...
                                                        </span>
                                                    ) : (
                                                        <span className="text-[var(--admin-text-dim)]">—</span>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </Card>
                )}

                {/* Help Section */}
                <Card className="bg-gradient-to-br from-[var(--admin-surface-2)] to-[var(--admin-surface-3)] border border-[var(--admin-border)]">
                    <div className="p-6">
                        <h3 className="text-lg font-bold text-[var(--admin-text)] mb-4 flex items-center gap-2">
                            <i className="bi bi-info-circle text-[var(--admin-primary)]"></i>
                            How Model Training Works
                        </h3>
                        <div className="grid md:grid-cols-3 gap-6 text-sm">
                            <div>
                                <div className="flex items-center gap-2 mb-2">
                                    <span className="w-6 h-6 rounded-full bg-[var(--admin-primary)] flex items-center justify-center text-xs font-bold text-white">1</span>
                                    <span className="font-medium text-[var(--admin-text)]">Collect Data</span>
                                </div>
                                <p className="text-[var(--admin-text-muted)] ml-8">
                                    Gathers historical task outcomes (success/failure) from your automation campaigns.
                                </p>
                            </div>
                            <div>
                                <div className="flex items-center gap-2 mb-2">
                                    <span className="w-6 h-6 rounded-full bg-[var(--admin-primary)] flex items-center justify-center text-xs font-bold text-white">2</span>
                                    <span className="font-medium text-[var(--admin-text)]">Train Model</span>
                                </div>
                                <p className="text-[var(--admin-text-muted)] ml-8">
                                    Uses ML algorithms to learn patterns and predict the best action type for each backlink.
                                </p>
                            </div>
                            <div>
                                <div className="flex items-center gap-2 mb-2">
                                    <span className="w-6 h-6 rounded-full bg-[var(--admin-primary)] flex items-center justify-center text-xs font-bold text-white">3</span>
                                    <span className="font-medium text-[var(--admin-text)]">Evaluate & Deploy</span>
                                </div>
                                <p className="text-[var(--admin-text-muted)] ml-8">
                                    Compares new model accuracy with current one. Deploys automatically if improved.
                                </p>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>
        </AdminLayout>
    );
}

