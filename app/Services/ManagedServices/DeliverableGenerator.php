<?php

namespace App\Services\ManagedServices;

use App\Models\Client;
use App\Models\ClientSubscription;
use App\Models\Deliverable;
use Carbon\Carbon;

class DeliverableGenerator
{
    /**
     * Generate monthly deliverables for a client
     */
    public function generateMonthlyDeliverables(Client $client, string $month): void
    {
        $subscription = ClientSubscription::where('client_id', $client->id)
            ->where('status', 'active')
            ->where('start_date', '<=', Carbon::parse($month . '-01'))
            ->where(function ($query) use ($month) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', Carbon::parse($month . '-01')->endOfMonth());
            })
            ->first();

        if (!$subscription) {
            return; // No active subscription
        }

        $package = $subscription->servicePackage;
        $deliverables = $package->included_deliverables ?? [];
        $slaDays = $package->sla_days ?? [];

        $monthStart = Carbon::parse($month . '-01')->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        // Generate audits
        if (isset($deliverables['audits_per_month'])) {
            $this->generateAudits($client, $month, $deliverables['audits_per_month'], $slaDays['audit_delivery'] ?? 3);
        }

        // Generate reports
        if (isset($deliverables['reports_per_month'])) {
            $this->generateReports($client, $month, $deliverables['reports_per_month'], $slaDays['report_delivery'] ?? 5);
        }

        // Generate backlinks
        if (isset($deliverables['backlinks_target'])) {
            $this->generateBacklinks($client, $month, $deliverables['backlinks_target']);
        }

        // Generate fix hours (as tasks)
        if (isset($deliverables['fix_hours'])) {
            $this->generateFixTasks($client, $month, $deliverables['fix_hours']);
        }
    }

    protected function generateAudits(Client $client, string $month, int $count, int $slaDays): void
    {
        $monthStart = Carbon::parse($month . '-01')->startOfMonth();
        
        for ($i = 0; $i < $count; $i++) {
            $dueDate = $monthStart->copy()->addDays($slaDays + ($i * 5)); // Spread across month
            
            Deliverable::create([
                'client_id' => $client->id,
                'month' => $month,
                'type' => 'audit',
                'title' => "SEO Audit #" . ($i + 1),
                'description' => 'Complete SEO audit for client website',
                'due_date' => $dueDate,
                'status' => 'planned',
            ]);
        }
    }

    protected function generateReports(Client $client, string $month, int $count, int $slaDays): void
    {
        $monthStart = Carbon::parse($month . '-01')->startOfMonth();
        $dueDate = $monthStart->copy()->addDays($slaDays);
        
        Deliverable::create([
            'client_id' => $client->id,
            'month' => $month,
            'type' => 'report',
            'title' => "Monthly SEO Report - {$month}",
            'description' => 'Generate and deliver monthly SEO performance report',
            'due_date' => $dueDate,
            'status' => 'planned',
        ]);
    }

    protected function generateBacklinks(Client $client, string $month, int $target): void
    {
        $monthStart = Carbon::parse($month . '-01')->startOfMonth();
        $dueDate = $monthStart->copy()->endOfMonth();
        
        Deliverable::create([
            'client_id' => $client->id,
            'month' => $month,
            'type' => 'backlinks',
            'title' => "Backlink Campaign - {$target} links",
            'description' => "Acquire {$target} quality backlinks",
            'due_date' => $dueDate,
            'status' => 'planned',
        ]);
    }

    protected function generateFixTasks(Client $client, string $month, int $hours): void
    {
        $monthStart = Carbon::parse($month . '-01')->startOfMonth();
        $dueDate = $monthStart->copy()->endOfMonth();
        
        Deliverable::create([
            'client_id' => $client->id,
            'month' => $month,
            'type' => 'fix',
            'title' => "SEO Fixes - {$hours} hours",
            'description' => "Complete {$hours} hours of SEO fixes and optimizations",
            'due_date' => $dueDate,
            'status' => 'planned',
        ]);
    }
}
