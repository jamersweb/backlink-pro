<?php

namespace App\Mail;

use App\Models\SeoAlert;
use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SeoAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public SeoAlert $alert,
        public Organization $organization
    ) {}

    public function build()
    {
        $severityColors = [
            'info' => '#3b82f6',
            'warning' => '#f59e0b',
            'critical' => '#ef4444',
        ];

        return $this->subject("SEO Alert: {$this->alert->title}")
            ->view('emails.seo.alert', [
                'alert' => $this->alert,
                'organization' => $this->organization,
                'severityColor' => $severityColors[$this->alert->severity] ?? '#808080',
            ]);
    }
}
