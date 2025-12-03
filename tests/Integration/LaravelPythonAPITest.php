<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\AutomationTask;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

class LaravelPythonAPITest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiToken = config('app.api_token', 'test-token');
    }

    public function test_python_worker_can_get_pending_tasks()
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $user->id]);
        $task = AutomationTask::factory()->create([
            'campaign_id' => $campaign->id,
            'status' => AutomationTask::STATUS_PENDING,
        ]);

        $response = $this->withHeaders([
            'X-API-Token' => $this->apiToken,
        ])->get('/api/tasks/pending');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'tasks' => [
                '*' => ['id', 'type', 'status', 'payload'],
            ],
        ]);
    }

    public function test_python_worker_can_lock_task()
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $user->id]);
        $task = AutomationTask::factory()->create([
            'campaign_id' => $campaign->id,
            'status' => AutomationTask::STATUS_PENDING,
        ]);

        $response = $this->withHeaders([
            'X-API-Token' => $this->apiToken,
        ])->post("/api/tasks/{$task->id}/lock", [
            'worker_id' => 'test-worker-1',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('automation_tasks', [
            'id' => $task->id,
            'status' => AutomationTask::STATUS_RUNNING,
            'locked_by' => 'test-worker-1',
        ]);
    }

    public function test_python_worker_can_update_task_status()
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $user->id]);
        $task = AutomationTask::factory()->create([
            'campaign_id' => $campaign->id,
            'status' => AutomationTask::STATUS_RUNNING,
        ]);

        $response = $this->withHeaders([
            'X-API-Token' => $this->apiToken,
        ])->put("/api/tasks/{$task->id}/status", [
            'status' => AutomationTask::STATUS_SUCCESS,
            'result' => ['url' => 'https://example.com'],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('automation_tasks', [
            'id' => $task->id,
            'status' => AutomationTask::STATUS_SUCCESS,
        ]);
    }

    public function test_python_worker_can_create_backlink()
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $user->id]);

        $response = $this->withHeaders([
            'X-API-Token' => $this->apiToken,
        ])->post('/api/backlinks', [
            'campaign_id' => $campaign->id,
            'url' => 'https://example.com/backlink',
            'type' => 'comment',
            'status' => 'submitted',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('backlinks', [
            'campaign_id' => $campaign->id,
            'url' => 'https://example.com/backlink',
        ]);
    }
}

