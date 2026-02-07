<?php

namespace App\Jobs;

use App\Models\Organization;
use App\Services\SEO\ReportBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class GenerateMonthlyExecutiveReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;
    public $tries = 2;
    public $queue = 'reports';

    public function __construct(
        public int $organizationId,
        public ?string $month = null
    ) {
        $this->month = $month ?? Carbon::now()->subMonth()->format('Y-m');
    }

    public function handle(): void
    {
        $organization = Organization::find($this->organizationId);
        if (!$organization) {
            return;
        }

        // Check plan limits - only pro/agency get monthly reports
        if ($organization->plan_key === 'free') {
            return;
        }

        $month = Carbon::createFromFormat('Y-m', $this->month);

        try {
            $reportBuilder = new ReportBuilder();
            $reportData = $reportBuilder->buildMonthlyReport($organization, $month);

            // Generate PDF (using existing PDF engine)
            $pdfPath = $this->generatePDF($organization, $reportData, $month);

            // Store report metadata
            $report = \App\Models\MonthlyReport::create([
                'organization_id' => $organization->id,
                'month' => $month->format('Y-m'),
                'file_path' => $pdfPath,
                'download_token' => \Illuminate\Support\Str::random(64),
                'generated_at' => now(),
            ]);

            // Send email
            $this->sendReportEmail($organization, $report);

            Log::info("Monthly report generated", [
                'organization_id' => $organization->id,
                'month' => $this->month,
                'report_id' => $report->id,
            ]);

        } catch (\Exception $e) {
            Log::error("Monthly report generation failed", [
                'organization_id' => $organization->id,
                'month' => $this->month,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function generatePDF(Organization $organization, array $reportData, Carbon $month): string
    {
        // Use existing PDF generation service
        // For now, return placeholder path
        $filename = "reports/{$organization->id}/monthly-{$month->format('Y-m')}.pdf";
        
        // TODO: Generate actual PDF using existing PDF engine with white-label branding
        Storage::put($filename, 'PDF content placeholder');
        
        return $filename;
    }

    protected function sendReportEmail(Organization $organization, $report): void
    {
        $emails = $organization->billing_email ? [$organization->billing_email] : [];
        
        if (empty($emails)) {
            $owner = $organization->owner;
            if ($owner) {
                $emails[] = $owner->email;
            }
        }

        foreach ($emails as $email) {
            try {
                Mail::to($email)->send(new \App\Mail\MonthlyReportMail($organization, $report));
            } catch (\Exception $e) {
                Log::warning("Failed to send monthly report email", [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
