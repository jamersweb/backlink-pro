<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Audit;
use App\Models\User;
use App\Jobs\RunSeoAuditJob;
use Illuminate\Support\Str;

class TestAuditCommand extends Command
{
    protected $signature = 'audit:test {url=https://www.xpertbid.com}';
    protected $description = 'Test audit execution with a URL';

    public function handle()
    {
        $url = $this->argument('url');
        $this->info("Testing audit for URL: {$url}");
        
        // Find first admin user
        $user = User::whereHas('roles', function ($query) {
            $query->where('name', 'admin');
        })->first();
        
        if (!$user) {
            $user = User::first();
        }
        
        if (!$user) {
            $this->error('No users found in database!');
            return 1;
        }
        
        $this->info("Using user: {$user->email} (ID: {$user->id})");
        
        // Create audit
        $audit = Audit::create([
            'user_id' => $user->id,
            'url' => $url,
            'normalized_url' => $url,
            'status' => Audit::STATUS_QUEUED,
            'mode' => Audit::MODE_AUTH,
            'share_token' => Str::random(32),
            'pages_limit' => 1,
            'crawl_depth' => 0,
            'started_at' => now(),
        ]);
        
        $this->info("Audit created: ID {$audit->id}");
        $this->info("Starting audit execution...");
        
        try {
            // Run synchronously
            RunSeoAuditJob::dispatchSync($audit->id);
            
            // Reload
            $audit->refresh();
            
            $this->info("Audit completed!");
            $this->info("Status: {$audit->status}");
            $this->info("Score: {$audit->overall_score}");
            $this->info("Grade: {$audit->overall_grade}");
            $this->info("Issues: " . $audit->issues()->count());
            
            if ($audit->status === Audit::STATUS_FAILED) {
                $this->error("Error: {$audit->error}");
                return 1;
            }
            
            $this->info("\nAudit URL: /audit-report/{$audit->id}");
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Audit failed with exception:");
            $this->error($e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}
