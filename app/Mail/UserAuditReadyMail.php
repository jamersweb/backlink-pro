<?php

namespace App\Mail;

use App\Models\Audit;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserAuditReadyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Audit $audit
    ) {}

    public function build()
    {
        $reportUrl = $this->audit->share_token
            ? url('/audit-report/share/' . $this->audit->share_token)
            : url('/audit-report/' . $this->audit->id);

        return $this->subject('Your SEO Audit Report is Ready')
            ->view('emails.audit.user-ready', [
                'audit' => $this->audit,
                'reportUrl' => $reportUrl,
            ]);
    }
}
