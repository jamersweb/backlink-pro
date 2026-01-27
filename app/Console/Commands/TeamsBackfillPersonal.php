<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\Domain;
use Illuminate\Console\Command;

class TeamsBackfillPersonal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teams:backfill-personal';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create personal teams for existing users and attach their domains';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting personal teams backfill...');

        $users = User::all();
        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        $created = 0;
        $attached = 0;

        foreach ($users as $user) {
            // Check if user already has a team
            $team = $user->primaryTeam();
            
            if (!$team) {
                // Create personal team
                $team = Team::create([
                    'owner_user_id' => $user->id,
                    'name' => "{$user->name} Workspace",
                ]);
                
                // Add user as owner
                TeamMember::create([
                    'team_id' => $team->id,
                    'user_id' => $user->id,
                    'role' => Team::ROLE_OWNER,
                ]);
                
                $created++;
            }

            // Attach domains to team
            $domains = Domain::where('user_id', $user->id)
                ->whereNull('team_id')
                ->get();
            
            foreach ($domains as $domain) {
                $domain->update(['team_id' => $team->id]);
                $attached++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Created {$created} teams");
        $this->info("Attached {$attached} domains to teams");

        return Command::SUCCESS;
    }
}
