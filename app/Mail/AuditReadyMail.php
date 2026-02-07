<?php

namespace App\Mail;

use App\Models\Audit;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AuditReadyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Audit $audit
    ) {}

    /**
     * Build the message.
     */
    public function build()
    {
        $organization = $this->audit->organization;
        $branding = $organization?->brandingProfile;

        $fromName = $branding?->email_from_name ?? config('app.name');
        $fromAddress = $branding?->email_from_address ?? config('mail.from.address');

        $reportUrl = $this->audit->share_token 
            ? route('public.report.show', $this->audit->share_token)
            : route('audit.show', $this->audit);

        return $this->from($fromAddress, $fromName)
            ->subject('Your SEO Audit Report is Ready')
            ->view('emails.audit.ready', [
                'audit' => $this->audit,
                'reportUrl' => $reportUrl,
                'branding' => $branding,
            ]);
    }
}
