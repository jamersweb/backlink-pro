<?php

namespace App\Console\Commands;

use App\Models\UserSubscription;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BillingRollPeriods extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:roll-periods';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Roll subscription periods forward for active subscriptions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();
        $subscriptions = UserSubscription::where('status', UserSubscription::STATUS_ACTIVE)
            ->where('current_period_end', '<', $today)
            ->get();

        $this->info("Found {$subscriptions->count()} subscriptions to roll forward");

        foreach ($subscriptions as $subscription) {
            $newPeriodStart = $today->copy()->startOfMonth();
            $newPeriodEnd = $today->copy()->addMonth()->startOfMonth();

            $subscription->update([
                'current_period_start' => $newPeriodStart,
                'current_period_end' => $newPeriodEnd,
            ]);

            $this->info("Rolled subscription for user {$subscription->user_id} to {$newPeriodStart->format('Y-m-d')} - {$newPeriodEnd->format('Y-m-d')}");
        }

        $this->info('Period roll completed');
        return 0;
    }
}
