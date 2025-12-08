<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AutomationTask;
use App\Models\Setting;
use App\Services\RateLimitingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    /**
     * Get pending tasks
     */
    public function getPendingTasks(Request $request)
    {
        // Validate API token
        $apiToken = trim($request->header('X-API-Token', ''));
        $expectedToken = trim(config('app.api_token', ''));

        // Handle token migration: accept both old and new default tokens
        // This allows transition period where scheduler might send new token but .env has old one
        $validTokens = [$expectedToken];
        if ($expectedToken === 'your-api-token-here') {
            $validTokens[] = 'your-secure-api-token-change-in-production';
        } elseif ($expectedToken === 'your-secure-api-token-change-in-production') {
            $validTokens[] = 'your-api-token-here';
        }

        if (empty($apiToken) || !in_array($apiToken, $validTokens)) {
            // Debug logging (remove in production)
            \Log::warning('API token mismatch', [
                'received' => $apiToken ? (substr($apiToken, 0, 10) . '...') : 'null/empty',
                'expected' => $expectedToken ? (substr($expectedToken, 0, 10) . '...') : 'null/empty',
                'received_length' => strlen($apiToken ?? ''),
                'expected_length' => strlen($expectedToken ?? ''),
                'match' => $apiToken === $expectedToken,
            ]);
            return response()->json([
                'error' => 'Unauthorized',
                'debug' => [
                    'received_preview' => $apiToken ? substr($apiToken, 0, 10) . '...' : 'null',
                    'expected_preview' => $expectedToken ? substr($expectedToken, 0, 10) . '...' : 'null',
                ]
            ], 401);
        }

        // Check API rate limit (300 requests per hour per worker)
        $workerId = $request->header('X-Worker-ID', 'unknown');
        $maxRequests = Setting::get('api_api_rate_limit', 300);

        if (!RateLimitingService::checkApiRateLimit($workerId, $maxRequests, 60)) {
            // Calculate retry_after: remaining time until rate limit window resets
            // Since we use a 60-minute window, calculate based on when the cache expires
            // For simplicity, use a reasonable default (e.g., 5 minutes)
            $retryAfter = 300; // 5 minutes default

            return response()->json([
                'error' => 'Rate limit exceeded',
                'message' => 'API rate limit exceeded. Please wait before making more requests.',
                'retry_after' => $retryAfter, // seconds
            ], 429);
        }

        $limit = $request->get('limit', 10);
        $type = $request->get('type');

        $query = AutomationTask::pending()
            ->with('campaign:id,name,settings')
            ->orderBy('created_at', 'asc');

        if ($type) {
            $query->ofType($type);
        }

        $tasks = $query->limit($limit)->get();

        return response()->json([
            'tasks' => $tasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'type' => $task->type,
                    'campaign_id' => $task->campaign_id,
                    'payload' => $task->payload,
                    'created_at' => $task->created_at->toISOString(),
                ];
            }),
        ]);
    }

    /**
     * Update task status
     */
    public function updateTaskStatus(Request $request, $id)
    {
        // Validate API token
        $apiToken = $request->header('X-API-Token');
        if ($apiToken !== config('app.api_token')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,running,success,failed',
            'result' => 'nullable|array',
            'error_message' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $task = AutomationTask::findOrFail($id);

        // Handle failed status with retry logic
        if ($request->status === 'failed') {
            $errorMessage = $request->error_message ?? 'Unknown error';

            // Truncate error message if too long (database column limit)
            if (strlen($errorMessage) > 1000) {
                $errorMessage = substr($errorMessage, 0, 997) . '...';
            }

            // Use the model's markFailed method which handles retry logic
            $task->markFailed($errorMessage);

            // Log the error for debugging
            \Log::warning("Task {$id} failed", [
                'task_id' => $id,
                'task_type' => $task->type,
                'error_message' => $errorMessage,
                'retry_count' => $task->retry_count,
                'max_retries' => $task->max_retries,
                'status' => $task->status,
            ]);

            return response()->json([
                'message' => 'Task marked as failed',
                'task' => [
                    'id' => $task->id,
                    'status' => $task->status,
                    'retry_count' => $task->retry_count,
                    'will_retry' => $task->status === AutomationTask::STATUS_PENDING,
                ]
            ]);
        }

        // Handle success status
        if ($request->status === 'success') {
            $updateData = [
                'status' => AutomationTask::STATUS_SUCCESS,
                'completed_at' => now(),
                'locked_at' => null,
                'locked_by' => null,
            ];

            if ($request->has('result')) {
                $updateData['result'] = $request->result;
            }

            // Clear error message on success
            $updateData['error_message'] = null;

            $task->update($updateData);

            \Log::info("Task {$id} completed successfully", [
                'task_id' => $id,
                'task_type' => $task->type,
            ]);

            return response()->json(['message' => 'Task completed successfully']);
        }

        // Handle running status
        if ($request->status === 'running') {
            $updateData = [
                'status' => AutomationTask::STATUS_RUNNING,
            ];

            $task->update($updateData);
            return response()->json(['message' => 'Task status updated']);
        }

        // Handle pending status
        if ($request->status === 'pending') {
            $updateData = [
                'status' => AutomationTask::STATUS_PENDING,
                'locked_at' => null,
                'locked_by' => null,
            ];

            // Clear error message when resetting to pending
            if ($request->has('error_message') && empty($request->error_message)) {
                $updateData['error_message'] = null;
            }

            $task->update($updateData);
            return response()->json(['message' => 'Task reset to pending']);
        }

        return response()->json(['message' => 'Task updated successfully']);
    }

    /**
     * Lock task
     */
    public function lockTask(Request $request, $id)
    {
        // Validate API token
        $apiToken = $request->header('X-API-Token');
        if ($apiToken !== config('app.api_token')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $task = AutomationTask::findOrFail($id);
        $workerId = $request->get('worker_id', 'worker-' . uniqid());

        if ($task->lock($workerId)) {
            return response()->json([
                'message' => 'Task locked successfully',
                'task' => $task,
            ]);
        }

        return response()->json(['error' => 'Task is already locked'], 409);
    }

    /**
     * Unlock task
     */
    public function unlockTask(Request $request, $id)
    {
        // Validate API token
        $apiToken = $request->header('X-API-Token');
        if ($apiToken !== config('app.api_token')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $task = AutomationTask::findOrFail($id);
        $task->unlock();

        return response()->json(['message' => 'Task unlocked successfully']);
    }
}

