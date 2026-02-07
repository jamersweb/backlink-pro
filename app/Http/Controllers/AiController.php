<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\AiGeneration;
use App\Models\Organization;
use App\Jobs\AuditChatAnswerJob;
use App\Jobs\GenerateAiSnippetPackJob;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Hash;

class AiController extends Controller
{
    /**
     * Chat with AI about audit
     */
    public function chat(Request $request, Organization $organization, Audit $audit)
    {
        $this->authorize('view', $organization);

        if ($audit->organization_id !== $organization->id) {
            abort(403);
        }

        $validated = $request->validate([
            'question' => ['required', 'string', 'max:500'],
        ]);

        // Rate limiting: max 10 questions/day per org on free plan
        $key = "ai_chat:{$organization->id}";
        $maxAttempts = $organization->plan_key === 'free' ? 10 : 100;
        
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return response()->json([
                'error' => 'Rate limit exceeded. Please try again later.',
            ], 429);
        }

        RateLimiter::hit($key, 86400); // 24 hours

        // Compute question hash for polling
        $questionHash = hash('sha256', strtolower(trim($validated['question'])));

        // Dispatch job
        AuditChatAnswerJob::dispatch($audit->id, $validated['question'], $organization->id);

        return response()->json([
            'status' => 'queued',
            'message' => 'Your question is being processed.',
            'question_hash' => $questionHash,
        ]);
    }

    /**
     * Get chat answer (polling endpoint)
     */
    public function getChatAnswer(Organization $organization, Audit $audit, string $questionHash)
    {
        $this->authorize('view', $organization);

        $generation = AiGeneration::where('audit_id', $audit->id)
            ->where('type', AiGeneration::TYPE_CHAT_ANSWER)
            ->where('input_fingerprint', $questionHash)
            ->first();

        if (!$generation) {
            return response()->json(['status' => 'not_found'], 404);
        }

        return response()->json([
            'status' => $generation->status,
            'answer' => $generation->output['answer'] ?? null,
            'citations' => $generation->output['citations'] ?? [],
            'error' => $generation->error,
        ]);
    }

    /**
     * Generate snippet pack
     */
    public function generateSnippets(Request $request, Organization $organization, Audit $audit)
    {
        $this->authorize('view', $organization);

        if ($audit->organization_id !== $organization->id) {
            abort(403);
        }

        $validated = $request->validate([
            'issue_codes' => ['required', 'array'],
            'issue_codes.*' => ['string'],
            'platform' => ['required', 'string', 'in:laravel_blade,nextjs,wordpress,shopify'],
        ]);

        // Rate limiting: max 20/month on pro, unlimited on agency
        $key = "ai_snippets:{$organization->id}";
        $maxAttempts = match($organization->plan_key) {
            'free' => 5,
            'pro' => 20,
            'agency' => 1000,
            default => 5,
        };

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return response()->json([
                'error' => 'Rate limit exceeded. Please upgrade your plan for more generations.',
            ], 429);
        }

        RateLimiter::hit($key, 2592000); // 30 days

        // Dispatch job
        GenerateAiSnippetPackJob::dispatch(
            $audit->id,
            $validated['issue_codes'],
            $validated['platform']
        );

        return response()->json([
            'status' => 'queued',
            'message' => 'Snippet pack is being generated.',
        ]);
    }

    /**
     * Get snippet pack (polling endpoint)
     */
    public function getSnippets(Organization $organization, Audit $audit, string $fingerprint)
    {
        $this->authorize('view', $organization);

        $generation = AiGeneration::where('audit_id', $audit->id)
            ->where('type', AiGeneration::TYPE_SNIPPET_PACK)
            ->where('input_fingerprint', $fingerprint)
            ->first();

        if (!$generation) {
            return response()->json(['status' => 'not_found'], 404);
        }

        return response()->json([
            'status' => $generation->status,
            'snippets' => $generation->output['snippets'] ?? [],
            'error' => $generation->error,
        ]);
    }

    /**
     * Get AI summary for audit
     */
    public function getSummary(Organization $organization, Audit $audit)
    {
        $this->authorize('view', $organization);

        $summary = AiGeneration::where('audit_id', $audit->id)
            ->where('type', AiGeneration::TYPE_REPORT_SUMMARY)
            ->where('status', AiGeneration::STATUS_COMPLETED)
            ->latest()
            ->first();

        $fixPlan = AiGeneration::where('audit_id', $audit->id)
            ->where('type', AiGeneration::TYPE_FIX_PLAN)
            ->where('status', AiGeneration::STATUS_COMPLETED)
            ->latest()
            ->first();

        return response()->json([
            'summary' => $summary?->output,
            'fix_plan' => $fixPlan?->output,
        ]);
    }
}
