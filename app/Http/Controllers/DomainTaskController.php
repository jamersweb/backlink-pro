<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\DomainTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DomainTaskController extends Controller
{
    /**
     * Update task status
     */
    public function updateStatus(Request $request, Domain $domain, DomainTask $task)
    {
        // Authorize domain ownership
        if ($domain->user_id !== Auth::id() || $task->domain_id !== $domain->id) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'required|in:open,doing,done,dismissed',
        ]);

        $task->update([
            'status' => $validated['status'],
        ]);

        return back()->with('success', 'Task status updated');
    }

    /**
     * Create manual task
     */
    public function createManual(Request $request, Domain $domain)
    {
        // Authorize domain ownership
        if ($domain->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:p1,p2,p3',
            'due_at' => 'nullable|date',
        ]);

        DomainTask::create([
            'domain_id' => $domain->id,
            'user_id' => Auth::id(),
            'source' => DomainTask::SOURCE_INSIGHTS,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'priority' => $validated['priority'],
            'impact_score' => $validated['priority'] === 'p1' ? 75 : ($validated['priority'] === 'p2' ? 50 : 25),
            'effort' => DomainTask::EFFORT_MEDIUM,
            'status' => DomainTask::STATUS_OPEN,
            'due_at' => $validated['due_at'] ?? null,
            'created_by' => DomainTask::CREATED_BY_USER,
        ]);

        return back()->with('success', 'Task created');
    }
}
