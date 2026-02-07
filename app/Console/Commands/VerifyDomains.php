<?php

namespace App\Console\Commands;

use App\Models\CustomDomain;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class VerifyDomains extends Command
{
    protected $signature = 'domains:verify {--domain= : Verify specific domain}';
    protected $description = 'Verify custom domain DNS configuration';

    public function handle()
    {
        $query = CustomDomain::whereIn('status', [
            CustomDomain::STATUS_PENDING,
            CustomDomain::STATUS_VERIFIED,
        ]);

        if ($this->option('domain')) {
            $query->where('domain', $this->option('domain'));
        }

        $domains = $query->get();

        if ($domains->isEmpty()) {
            $this->info('No domains to verify.');
            return 0;
        }

        foreach ($domains as $domain) {
            $this->info("Verifying {$domain->domain}...");

            $verified = $this->verifyDomain($domain);

            if ($verified) {
                $domain->update([
                    'status' => CustomDomain::STATUS_VERIFIED,
                    'last_checked_at' => now(),
                ]);
                $this->info("✓ {$domain->domain} verified successfully.");
            } else {
                $domain->update([
                    'last_checked_at' => now(),
                ]);
                $this->warn("✗ {$domain->domain} verification failed.");
            }
        }

        return 0;
    }

    /**
     * Verify domain DNS
     */
    protected function verifyDomain(CustomDomain $domain): bool
    {
        try {
            // Check for TXT record: _backlinkpro-audit.{domain} = {token}
            $txtRecord = '_backlinkpro-audit.' . $domain->domain;
            $records = dns_get_record($txtRecord, DNS_TXT);

            foreach ($records as $record) {
                if (isset($record['txt']) && $record['txt'] === $domain->verification_token) {
                    return true;
                }
            }

            // Also check root domain TXT records
            $rootRecords = dns_get_record($domain->domain, DNS_TXT);
            foreach ($rootRecords as $record) {
                if (isset($record['txt']) && strpos($record['txt'], $domain->verification_token) !== false) {
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            Log::error("Domain verification failed for {$domain->domain}: {$e->getMessage()}");
            return false;
        }
    }
}
