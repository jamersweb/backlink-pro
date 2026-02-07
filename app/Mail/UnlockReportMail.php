<?php

namespace App\Mail;

use App\Models\Audit;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UnlockReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Audit $audit,
        public string $unlockUrl
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

        return $this->from($fromAddress, $fromName)
            ->subject('Your SEO Audit Report is Ready')
            ->view('emails.audit.unlock', [
                'audit' => $this->audit,
                'unlockUrl' => $this->unlockUrl,
                'branding' => $branding,
            ]);
    }
}
