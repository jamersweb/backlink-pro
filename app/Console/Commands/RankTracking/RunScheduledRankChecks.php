<?php

namespace App\Console\Commands\RankTracking;

use App\Models\Domain;
use App\Models\RankKeyword;
use App\Services\RankTracking\RankTracker;
use App\Jobs\RankTracking\RunRankCheckJob;
use Illuminate\Console\Command;

class RunScheduledRankChecks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rank:run-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run scheduled rank checks for domains';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Running scheduled rank checks...');

        $today = now();
        $dayOfWeek = $today->dayOfWeek; // 0 = Sunday, 6 = Saturday

        // Get domains with active keywords that need checking
        $domains = Domain::whereHas('rankKeywords', function($query) use ($today, $dayOfWeek) {
            $query->where('is_active', true)
                ->where(function($q) use ($today, $dayOfWeek) {
                    // Daily schedule
                    $q->where('schedule', RankKeyword::SCHEDULE_DAILY)
                        // Weekly schedule - run on Monday (1)
                        ->orWhere(function($qq) use ($dayOfWeek) {
                            $qq->where('schedule', RankKeyword::SCHEDULE_WEEKLY)
                                ->whereRaw('? = 1', [$dayOfWeek]); // Monday
                        });
                });
        })
        ->with(['rankKeywords' => function($query) use ($today, $dayOfWeek) {
            $query->where('is_active', true)
                ->where(function($q) use ($today, $dayOfWeek) {
                    $q->where('schedule', RankKeyword::SCHEDULE_DAILY)
                        ->orWhere(function($qq) use ($dayOfWeek) {
                            $qq->where('schedule', RankKeyword::SCHEDULE_WEEKLY)
                                ->whereRaw('? = 1', [$dayOfWeek]);
                        });
                });
        }])
        ->get();

        $totalChecks = 0;

        foreach ($domains as $domain) {
            $rankTracker = new RankTracker($domain);

            // Group keywords by user (domain owner)
            $user = $domain->user;
            if (!$user) {
                continue;
            }

            // Create check for all scheduled keywords
            $keywordIds = $domain->rankKeywords->pluck('id')->toArray();
            
            if (empty($keywordIds)) {
                continue;
            }

            try {
                $check = $rankTracker->createCheck($user, $keywordIds, 'gsc_fallback');
                RunRankCheckJob::dispatch($check->id);
                $totalChecks++;

                $this->info("Queued rank check for domain: {$domain->name} ({$check->keywords_count} keywords)");
            } catch (\Exception $e) {
                $this->error("Failed to queue check for domain {$domain->name}: " . $e->getMessage());
            }
        }

        $this->info("Queued {$totalChecks} rank checks");

        return Command::SUCCESS;
    }
}
