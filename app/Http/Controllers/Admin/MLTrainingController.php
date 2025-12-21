<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AutomationTask;
use App\Models\BacklinkOpportunity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\Process\Process;

class MLTrainingController extends Controller
{
    /**
     * Display the ML training dashboard
     */
    public function index()
    {
        // Get training statistics
        $stats = $this->getTrainingStats();
        
        // Get model info
        $modelInfo = $this->getModelInfo();
        
        // Get recent training logs
        $trainingLogs = $this->getRecentTrainingLogs();
        
        return Inertia::render('Admin/MLTraining/Index', [
            'stats' => $stats,
            'modelInfo' => $modelInfo,
            'trainingLogs' => $trainingLogs,
        ]);
    }

    /**
     * Get training data statistics
     */
    private function getTrainingStats(): array
    {
        $successfulTasks = AutomationTask::where('status', AutomationTask::STATUS_SUCCESS)->count();
        $failedTasks = AutomationTask::where('status', AutomationTask::STATUS_FAILED)->count();
        $totalTasks = $successfulTasks + $failedTasks;
        
        $verifiedBacklinks = BacklinkOpportunity::where('status', BacklinkOpportunity::STATUS_VERIFIED)->count();
        
        // Calculate success rate
        $successRate = $totalTasks > 0 ? round(($successfulTasks / $totalTasks) * 100, 2) : 0;
        
        // Get last 7 days data count
        $recentDataCount = AutomationTask::whereIn('status', [AutomationTask::STATUS_SUCCESS, AutomationTask::STATUS_FAILED])
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
        
        return [
            'total_training_samples' => $totalTasks,
            'successful_samples' => $successfulTasks,
            'failed_samples' => $failedTasks,
            'verified_backlinks' => $verifiedBacklinks,
            'success_rate' => $successRate,
            'recent_data_count' => $recentDataCount,
        ];
    }

    /**
     * Get current model information
     */
    private function getModelInfo(): array
    {
        $modelPath = base_path('python/ml/export_model.pkl');
        $versionsPath = base_path('python/ml/models/versions.json');
        
        $info = [
            'model_exists' => file_exists($modelPath),
            'model_path' => 'python/ml/export_model.pkl',
            'last_modified' => null,
            'file_size' => null,
            'current_version' => null,
            'versions' => [],
        ];
        
        if (file_exists($modelPath)) {
            $info['last_modified'] = date('Y-m-d H:i:s', filemtime($modelPath));
            $info['file_size'] = $this->formatBytes(filesize($modelPath));
        }
        
        // Load versions info
        if (file_exists($versionsPath)) {
            $versionsData = json_decode(file_get_contents($versionsPath), true);
            $info['current_version'] = $versionsData['current_version'] ?? null;
            $info['versions'] = array_slice($versionsData['versions'] ?? [], -5); // Last 5 versions
        }
        
        return $info;
    }

    /**
     * Get recent training logs
     */
    private function getRecentTrainingLogs(): array
    {
        $logPath = base_path('python/ml/training_logs.json');
        
        if (!file_exists($logPath)) {
            return [];
        }
        
        $logs = json_decode(file_get_contents($logPath), true) ?? [];
        return array_slice(array_reverse($logs), 0, 10);
    }

    /**
     * Trigger model training
     */
    public function train(Request $request)
    {
        $request->validate([
            'model_type' => 'nullable|in:xgboost,lightgbm,randomforest',
            'since_days' => 'nullable|integer|min:1|max:365',
            'auto_deploy' => 'nullable|boolean',
        ]);

        $modelType = $request->input('model_type');
        $sinceDays = $request->input('since_days', 7);
        $autoDeploy = $request->input('auto_deploy', true);

        try {
            $result = $this->runTrainingScript($modelType, $sinceDays, $autoDeploy);
            
            // Log the training attempt
            $this->logTrainingAttempt($result);
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Model training completed successfully!',
                    'result' => $result,
                ]);
            } else {
                // Build detailed error message
                $errorMessage = 'Training failed';
                if (isset($result['error_summary']) && !empty($result['error_summary'])) {
                    $errorMessage .= ': ' . $result['error_summary'];
                } elseif (isset($result['error'])) {
                    // Extract first meaningful error line
                    $errorLines = explode("\n", $result['error']);
                    foreach ($errorLines as $line) {
                        if (stripos($line, 'Error') !== false || 
                            stripos($line, 'File:') !== false || 
                            stripos($line, 'Line:') !== false ||
                            stripos($line, 'unsupported operand') !== false) {
                            $errorMessage .= ': ' . trim($line);
                            break;
                        }
                    }
                    if ($errorMessage === 'Training failed') {
                        $errorMessage .= ': ' . substr($result['error'], 0, 200);
                    }
                }
                
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'error_details' => $result['error'] ?? null,
                    'error_location' => $result['error_location'] ?? null,
                    'result' => $result,
                ], 422);
            }
        } catch (\Exception $e) {
            Log::error('ML Training error', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Training error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Run the Python training script
     */
    private function runTrainingScript(?string $modelType, int $sinceDays, bool $autoDeploy): array
    {
        $pythonBinary = env('PYTHON_BINARY', 'python');
        $scriptPath = base_path('python/ml/retrain_model.py');
        
        if (!file_exists($scriptPath)) {
            return [
                'success' => false,
                'error' => 'Training script not found at: ' . $scriptPath,
            ];
        }
        
        // Build command arguments
        $args = [$pythonBinary, $scriptPath, '--since-days', (string) $sinceDays];
        
        if ($modelType) {
            $args[] = '--model-type';
            $args[] = $modelType;
        }
        
        if (!$autoDeploy) {
            $args[] = '--no-auto-deploy';
        }
        
        // Use API to collect data
        $args[] = '--use-api';
        
        // Get API token
        $apiToken = config('app.api_token') ?: env('APP_API_TOKEN', '');
        
        // Get Python user site-packages path
        $pythonUserSite = $this->getPythonUserSitePackages($pythonBinary);
        
        // Build environment variables
        $env = [
            'PYTHONUNBUFFERED' => '1',
            'LARAVEL_API_URL' => config('app.url', 'http://localhost'),
            'LARAVEL_API_TOKEN' => $apiToken,
        ];
        
        // Fix Windows asyncio issue
        if (PHP_OS_FAMILY === 'Windows') {
            $env['PYTHONASYNCIODEBUG'] = '0';
            // Use SelectorEventLoop instead of ProactorEventLoop on Windows
            $env['PYTHONIOENCODING'] = 'utf-8';
        }
        
        // Add PYTHONPATH to include user site-packages
        if ($pythonUserSite) {
            $env['PYTHONPATH'] = $pythonUserSite . (isset($_ENV['PYTHONPATH']) ? PATH_SEPARATOR . $_ENV['PYTHONPATH'] : '');
        }
        
        $process = new Process(
            $args,
            base_path('python'),
            $env,
            null,
            600 // 10 minutes timeout for training
        );
        
        $output = '';
        $errorOutput = '';
        
        try {
            $process->run(function ($type, $buffer) use (&$output, &$errorOutput) {
                if ($type === Process::ERR) {
                    $errorOutput .= $buffer;
                } else {
                    $output .= $buffer;
                }
            });
            
            if ($process->isSuccessful()) {
                return [
                    'success' => true,
                    'output' => $output,
                    'exit_code' => $process->getExitCode(),
                ];
            } else {
                // Extract detailed error information from output
                $errorDetails = $errorOutput ?: $output ?: 'Process failed without output';
                
                // Try to extract the most relevant error line
                $errorLines = explode("\n", $errorDetails);
                $mainError = '';
                $errorLocation = '';
                
                foreach ($errorLines as $line) {
                    // Look for error patterns
                    if (preg_match('/File:\s*(.+)/', $line, $matches)) {
                        $errorLocation .= "File: " . trim($matches[1]) . "\n";
                    }
                    if (preg_match('/Line:\s*(\d+)/', $line, $matches)) {
                        $errorLocation .= "Line: " . trim($matches[1]) . "\n";
                    }
                    if (preg_match('/Error (Type|Message):\s*(.+)/', $line, $matches)) {
                        $mainError .= trim($matches[2]) . "\n";
                    }
                    if (preg_match('/unsupported operand type/', $line)) {
                        $mainError = "Type Error: " . trim($line) . "\n" . $mainError;
                    }
                }
                
                return [
                    'success' => false,
                    'error' => $errorDetails,
                    'error_summary' => $mainError ?: 'Process failed',
                    'error_location' => $errorLocation,
                    'exit_code' => $process->getExitCode(),
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Log training attempt to JSON file
     */
    private function logTrainingAttempt(array $result): void
    {
        $logPath = base_path('python/ml/training_logs.json');
        
        $logs = [];
        if (file_exists($logPath)) {
            $logs = json_decode(file_get_contents($logPath), true) ?? [];
        }
        
        $logs[] = [
            'timestamp' => now()->toISOString(),
            'success' => $result['success'],
            'error' => $result['error'] ?? null,
            'exit_code' => $result['exit_code'] ?? null,
            'triggered_by' => 'admin_panel',
        ];
        
        // Keep only last 100 logs
        $logs = array_slice($logs, -100);
        
        file_put_contents($logPath, json_encode($logs, JSON_PRETTY_PRINT));
    }

    /**
     * Export training data as CSV
     */
    public function exportData(Request $request)
    {
        $request->validate([
            'since_days' => 'nullable|integer|min:1|max:365',
        ]);

        $sinceDays = $request->input('since_days', 30);
        
        $tasks = AutomationTask::whereIn('status', [AutomationTask::STATUS_SUCCESS, AutomationTask::STATUS_FAILED])
            ->where('created_at', '>=', now()->subDays($sinceDays))
            ->with(['campaign:id,name,category_id,subcategory_id'])
            ->orderBy('created_at', 'desc')
            ->limit(10000)
            ->get();
        
        $csv = "task_id,type,status,campaign_id,category_id,created_at,error_message\n";
        
        foreach ($tasks as $task) {
            $csv .= implode(',', [
                $task->id,
                $task->type,
                $task->status,
                $task->campaign_id,
                $task->campaign->category_id ?? '',
                $task->created_at->toDateTimeString(),
                '"' . str_replace('"', '""', $task->error_message ?? '') . '"',
            ]) . "\n";
        }
        
        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="training_data_' . date('Y-m-d') . '.csv"');
    }

    /**
     * Get training status (for polling)
     */
    public function status()
    {
        $modelInfo = $this->getModelInfo();
        $stats = $this->getTrainingStats();
        
        return response()->json([
            'model_info' => $modelInfo,
            'stats' => $stats,
        ]);
    }

    /**
     * Get Python user site-packages path
     */
    private function getPythonUserSitePackages(string $pythonBinary): ?string
    {
        try {
            $process = new Process(
                [$pythonBinary, '-c', 'import site; print(site.getusersitepackages())'],
                null,
                null,
                null,
                10
            );
            
            $process->run();
            
            if ($process->isSuccessful()) {
                $path = trim($process->getOutput());
                if ($path && is_dir($path)) {
                    return $path;
                }
            }
        } catch (\Exception $e) {
            // Fallback: try common user site-packages location
            $home = getenv('USERPROFILE') ?: getenv('HOME');
            if ($home) {
                $commonPath = $home . DIRECTORY_SEPARATOR . 'AppData' . DIRECTORY_SEPARATOR . 'Roaming' . DIRECTORY_SEPARATOR . 'Python' . DIRECTORY_SEPARATOR . 'Python312' . DIRECTORY_SEPARATOR . 'site-packages';
                if (is_dir($commonPath)) {
                    return $commonPath;
                }
            }
        }
        
        return null;
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        return round($bytes / pow(1024, $pow), 2) . ' ' . $units[$pow];
    }
}

