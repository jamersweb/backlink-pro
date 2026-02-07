<?php

namespace App\Jobs;

use App\Models\EmailSequence;
use App\Models\EmailSequenceStep;
use App\Models\EmailEvent;
use App\Models\Organization;
use App\Models\User;
use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendSequenceEmailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job
     */
    public function handle(): void
    {
        $sequences = EmailSequence::where('is_active', true)->get();

        foreach ($sequences as $sequence) {
            $this->processSequence($sequence);
        }
    }

    /**
     * Process a sequence
     */
    protected function processSequence(EmailSequence $sequence): void
    {
        $steps = $sequence->steps;
        
        foreach ($steps as $step) {
            $this->processStep($sequence, $step);
        }
    }

    /**
     * Process a step
     */
    protected function processStep(EmailSequence $sequence, EmailSequenceStep $step): void
    {
        // Determine target entities based on sequence key
        $targets = $this->getTargetsForSequence($sequence->key);
        
        foreach ($targets as $target) {
            if ($this->shouldSendStep($target, $step, $sequence)) {
                $this->sendStepEmail($target, $step, $sequence);
            }
        }
    }

    /**
     * Get targets for sequence
     */
    protected function getTargetsForSequence(string $sequenceKey): array
    {
        return match($sequenceKey) {
            'onboarding_free', 'onboarding_pro', 'onboarding_agency' => $this->getOnboardingTargets($sequenceKey),
            'retention_past_due' => $this->getPastDueTargets(),
            'winback_canceled' => $this->getCanceledTargets(),
            default => [],
        };
    }

    /**
     * Get onboarding targets
     */
    protected function getOnboardingTargets(string $sequenceKey): array
    {
        $planKey = match($sequenceKey) {
            'onboarding_free' => 'free',
            'onboarding_pro' => 'pro',
            'onboarding_agency' => 'agency',
            default => 'free',
        };

        // Get orgs created in last 7 days with matching plan
        $orgs = Organization::where('plan_key', $planKey)
            ->where('created_at', '>', now()->subDays(7))
            ->get();

        $targets = [];
        foreach ($orgs as $org) {
            $targets[] = ['type' => 'organization', 'entity' => $org];
        }

        return $targets;
    }

    /**
     * Get past due targets
     */
    protected function getPastDueTargets(): array
    {
        $orgs = Organization::where('plan_status', 'past_due')->get();
        
        $targets = [];
        foreach ($orgs as $org) {
            $targets[] = ['type' => 'organization', 'entity' => $org];
        }

        return $targets;
    }

    /**
     * Get canceled targets
     */
    protected function getCanceledTargets(): array
    {
        $orgs = Organization::where('plan_status', 'canceled')
            ->where('updated_at', '>', now()->subDays(30))
            ->get();
        
        $targets = [];
        foreach ($orgs as $org) {
            $targets[] = ['type' => 'organization', 'entity' => $org];
        }

        return $targets;
    }

    /**
     * Check if step should be sent
     */
    protected function shouldSendStep(array $target, EmailSequenceStep $step, EmailSequence $sequence): bool
    {
        $entity = $target['entity'];
        
        // Check if step was already sent
        $emailEvent = EmailEvent::where('template_key', $step->template_key)
            ->where(function ($query) use ($entity, $target) {
                if ($target['type'] === 'organization') {
                    $query->where('organization_id', $entity->id);
                } elseif ($target['type'] === 'user') {
                    $query->where('user_id', $entity->id);
                } elseif ($target['type'] === 'lead') {
                    $query->where('lead_id', $entity->id);
                }
            })
            ->where('email_type', EmailEvent::TYPE_SEQUENCE_STEP)
            ->exists();

        if ($emailEvent) {
            return false; // Already sent
        }

        // Check conditions
        if ($step->conditions) {
            return $this->checkConditions($entity, $step->conditions);
        }

        // Check delay
        $createdAt = $entity->created_at ?? now();
        $delayMinutes = $step->delay_minutes ?? 0;
        $shouldSendAt = $createdAt->addMinutes($delayMinutes);

        return now()->gte($shouldSendAt);
    }

    /**
     * Check conditions
     */
    protected function checkConditions($entity, array $conditions): bool
    {
        foreach ($conditions as $key => $value) {
            if ($key === 'has_completed_audit') {
                if ($value && !$entity->audits()->where('status', 'completed')->exists()) {
                    return false;
                }
            }
            // Add more conditions as needed
        }

        return true;
    }

    /**
     * Send step email
     */
    protected function sendStepEmail(array $target, EmailSequenceStep $step, EmailSequence $sequence): void
    {
        $entity = $target['entity'];
        
        try {
            // Determine recipient
            $email = null;
            $name = null;
            
            if ($target['type'] === 'organization') {
                $email = $entity->billing_email ?? $entity->owner->email;
                $name = $entity->billing_name ?? $entity->name;
            } elseif ($target['type'] === 'user') {
                $email = $entity->email;
                $name = $entity->name;
            } elseif ($target['type'] === 'lead') {
                $email = $entity->email;
                $name = $entity->name;
            }

            if (!$email) {
                return;
            }

            // TODO: Create actual Mailable class for each template
            // For now, log the event
            EmailEvent::create([
                'organization_id' => $target['type'] === 'organization' ? $entity->id : null,
                'user_id' => $target['type'] === 'user' ? $entity->id : null,
                'lead_id' => $target['type'] === 'lead' ? $entity->id : null,
                'email_type' => EmailEvent::TYPE_SEQUENCE_STEP,
                'template_key' => $step->template_key,
                'status' => EmailEvent::STATUS_QUEUED,
                'meta' => [
                    'sequence_key' => $sequence->key,
                    'step_order' => $step->step_order,
                ],
                'occurred_at' => now(),
            ]);

            // TODO: Actually send email via Mailable
            // Mail::to($email)->send(new SequenceEmailMailable($step, $entity));

        } catch (\Exception $e) {
            \Log::error('Failed to send sequence email', [
                'step_id' => $step->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
