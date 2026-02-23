<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EnsureAdminCommand extends Command
{
    protected $signature = 'backlinkpro:ensure-admin 
                            {--email= : Admin email}
                            {--password= : New password (will be hashed)}';

    protected $description = 'Create or update an admin user with the given email/password (secure hashed, no bypass)';

    public function handle(): int
    {
        $email = $this->option('email');
        $password = $this->option('password');

        if (!$email || !$password) {
            $this->error('Usage: php artisan backlinkpro:ensure-admin --email=admin@example.com --password=YourSecurePassword');
            return Command::FAILURE;
        }

        $email = strtolower(trim($email));
        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters.');
            return Command::FAILURE;
        }

        DB::transaction(function () use ($email, $password) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->update(['password' => $password]);
                $this->info("Updated existing user id {$user->id} as admin.");
            } else {
                $user = User::create([
                    'name'  => 'Admin',
                    'email' => $email,
                    'password' => $password,
                    'email_verified_at' => now(),
                ]);
                $this->info("Created new user id {$user->id}.");
            }

            $adminRole = \Spatie\Permission\Models\Role::firstOrCreate(
                ['name' => 'admin', 'guard_name' => 'web']
            );
            if (!$user->hasRole('admin')) {
                $user->assignRole('admin');
                $this->info('Assigned admin role.');
            }
        });

        $this->info('Done. You can log in with the provided email and password.');
        return Command::SUCCESS;
    }
}
