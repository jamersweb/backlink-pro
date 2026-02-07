<?php

namespace App\Services\ManagedServices;

use App\Models\Deliverable;
use App\Models\SlaEvent;
use App\Models\Approval;
use Carbon\Carbon;

class SlaChecker
{
    /**
     * Check for SLA breaches
     */
    public function checkBreaches(): void
    {
        $this->checkOverdueDeliverables();
        $this->checkPendingApprovals();
    }

    /**
     * Check overdue deliverables
     */
    protected function checkOverdueDeliverables(): void
    {
        $overdue = Deliverable::where('status', '!=', 'delivered')
            ->where('status', '!=', 'canceled')
            ->where('due_date', '<', now())
            ->get();

        foreach ($overdue as $deliverable) {
            // Check if already marked overdue
            if ($deliverable->status === 'overdue') {
                continue;
            }

            // Mark as overdue
            $deliverable->update(['status' => 'overdue']);

            // Create SLA event
            $severity = $this->calculateSeverity($deliverable);
            
            SlaEvent::create([
                'client_id' => $deliverable->client_id,
                'deliverable_id' => $deliverable->id,
                'type' => $this->getSlaType($deliverable->type),
                'severity' => $severity,
                'message' => "Deliverable '{$deliverable->title}' is overdue by " . now()->diffInDays($deliverable->due_date) . " days",
                'occurred_at' => now(),
            ]);

            // Send notifications (would be implemented)
            $this->notifyBreach($deliverable, $severity);
        }
    }

    /**
     * Check pending approvals
     */
    protected function checkPendingApprovals(): void
    {
        $pending = Approval::where('status', 'pending')
            ->where('requested_at', '<', now()->subDays(2))
            ->get();

        foreach ($pending as $approval) {
            SlaEvent::create([
                'client_id' => $approval->client_id,
                'type' => 'response_late',
                'severity' => 'warning',
                'message' => "Approval '{$approval->title}' has been pending for " . now()->diffInDays($approval->requested_at) . " days",
                'occurred_at' => now(),
            ]);

            // Escalate notification
            $this->notifyApprovalDelay($approval);
        }
    }

    /**
     * Calculate severity based on delay
     */
    protected function calculateSeverity(Deliverable $deliverable): string
    {
        $daysOverdue = now()->diffInDays($deliverable->due_date);
        
        if ($daysOverdue >= 7) {
            return 'critical';
        }
        
        return 'warning';
    }

    /**
     * Get SLA type from deliverable type
     */
    protected function getSlaType(string $deliverableType): string
    {
        $mapping = [
            'audit' => 'audit_delivery_late',
            'report' => 'report_late',
        ];

        return $mapping[$deliverableType] ?? 'audit_delivery_late';
    }

    /**
     * Notify breach (placeholder)
     */
    protected function notifyBreach(Deliverable $deliverable, string $severity): void
    {
        // Would send email/Slack notifications
    }

    /**
     * Notify approval delay (placeholder)
     */
    protected function notifyApprovalDelay(Approval $approval): void
    {
        // Would send escalation notifications
    }
}
