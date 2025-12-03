<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AutomationTask;
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
        $apiToken = $request->header('X-API-Token');
        if ($apiToken !== config('app.api_token')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check API rate limit (100 requests per hour per worker)
        $workerId = $request->header('X-Worker-ID', 'unknown');
        $maxRequests = config('app.api_rate_limit', 100);
        
        if (!RateLimitingService::checkApiRateLimit($workerId, $maxRequests, 60)) {
            return response()->json([
                'error' => 'Rate limit exceeded',
                'message' => 'API rate limit exceeded. Please wait before making more requests.',
                'retry_after' => 3600, // seconds
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

        $updateData = [
            'status' => $request->status,
        ];

        if ($request->has('result')) {
            $updateData['result'] = $request->result;
        }

        if ($request->has('error_message')) {
            $updateData['error_message'] = $request->error_message;
        }

        if ($request->status === 'success') {
            $updateData['completed_at'] = now();
        }

        $task->update($updateData);

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

