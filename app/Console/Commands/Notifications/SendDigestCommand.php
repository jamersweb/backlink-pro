<?php

namespace App\Console\Commands\Notifications;

use App\Models\User;
use App\Jobs\Notifications\SendDigestEmailJob;
use Illuminate\Console\Command;

class SendDigestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send-digest {--period=daily : daily or weekly}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notification digest emails';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $period = $this->option('period');

        if (!in_array($period, ['daily', 'weekly'])) {
            $this->error('Period must be "daily" or "weekly"');
            return Command::FAILURE;
        }

        // Get all users (for MVP - in production, filter by preference)
        $users = User::all();

        $this->info("Sending {$period} digests to {$users->count()} users...");

        foreach ($users as $user) {
            SendDigestEmailJob::dispatch($user->id, $period);
            $this->line("Queued digest for user {$user->id}");
        }

        $this->info("Queued {$users->count()} digest emails");

        return Command::SUCCESS;
    }
}
