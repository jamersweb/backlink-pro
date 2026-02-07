<?php

namespace App\Services\ManagedServices;

use App\Models\Approval;
use App\Models\AuditPatch;
use App\Models\ServiceRequest;
use App\Models\BacklinkOutreachMessage;

class ApprovalWorkflow
{
    /**
     * Require approval for patch PR
     */
    public function requirePatchApproval(AuditPatch $patch): Approval
    {
        $project = $patch->auditFixCandidate->audit->clientProject;
        if (!$project) {
            throw new \Exception('Patch must be linked to a client project');
        }

        return Approval::create([
            'organization_id' => $project->client->organization_id,
            'client_id' => $project->client_id,
            'client_project_id' => $project->id,
            'subject_type' => 'audit_patch',
            'subject_id' => $patch->id,
            'title' => "Approve PR: {$patch->auditFixCandidate->title}",
            'summary' => "Review and approve this SEO fix patch before creating PR",
            'requested_by_user_id' => auth()->id(),
            'status' => 'pending',
            'requested_at' => now(),
        ]);
    }

    /**
     * Require approval for outreach message
     */
    public function requireOutreachApproval(BacklinkOutreachMessage $message): Approval
    {
        $campaign = $message->prospect->campaign;
        $project = $campaign->clientProject;
        
        if (!$project) {
            throw new \Exception('Campaign must be linked to a client project');
        }

        return Approval::create([
            'organization_id' => $project->client->organization_id,
            'client_id' => $project->client_id,
            'client_project_id' => $project->id,
            'subject_type' => 'outreach_message',
            'subject_id' => $message->id,
            'title' => "Approve Outreach: {$message->prospect->prospect_url}",
            'summary' => "Review and approve this backlink outreach message",
            'requested_by_user_id' => auth()->id(),
            'status' => 'pending',
            'requested_at' => now(),
        ]);
    }

    /**
     * Check if approval is required and granted
     */
    public function canProceed(string $subjectType, int $subjectId): bool
    {
        $approval = Approval::where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->first();

        if (!$approval) {
            return true; // No approval required
        }

        return $approval->status === 'approved';
    }

    /**
     * Approve an approval request
     */
    public function approve(Approval $approval, ?string $note = null): void
    {
        $approval->update([
            'status' => 'approved',
            'decision_by_user_id' => auth()->id(),
            'decision_note' => $note,
            'decided_at' => now(),
        ]);
    }

    /**
     * Reject an approval request
     */
    public function reject(Approval $approval, string $note): void
    {
        $approval->update([
            'status' => 'rejected',
            'decision_by_user_id' => auth()->id(),
            'decision_note' => $note,
            'decided_at' => now(),
        ]);
    }

    /**
     * Request changes
     */
    public function requestChanges(Approval $approval, string $note): void
    {
        $approval->update([
            'status' => 'changes_requested',
            'decision_by_user_id' => auth()->id(),
            'decision_note' => $note,
            'decided_at' => now(),
        ]);
    }
}
