<?php

namespace App\Mail;

use App\Models\Organization;
use App\Models\MonthlyReport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MonthlyReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Organization $organization,
        public MonthlyReport $report
    ) {}

    public function build()
    {
        $downloadUrl = route('seo.reports.download', [
            'organization' => $this->organization->id,
            'report' => $this->report->id,
        ]);

        return $this->subject("Your Monthly SEO Report - {$this->report->month}")
            ->view('emails.seo.monthly-report', [
                'organization' => $this->organization,
                'report' => $this->report,
                'downloadUrl' => $downloadUrl,
            ]);
    }
}
