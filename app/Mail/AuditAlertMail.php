<?php

namespace App\Mail;

use App\Models\AuditAlert;
use App\Models\AuditMonitor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AuditAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public AuditAlert $alert,
        public AuditMonitor $monitor
    ) {}

    public function build()
    {
        $severityColors = [
            'info' => '#36a64f',
            'warning' => '#ffa500',
            'critical' => '#ff0000',
        ];

        return $this->subject("SEO Alert: {$this->alert->title}")
            ->view('emails.audit-alert', [
                'alert' => $this->alert,
                'monitor' => $this->monitor,
                'severityColor' => $severityColors[$this->alert->severity] ?? '#808080',
            ]);
    }
}
