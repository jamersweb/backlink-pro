<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class AuthDebugCheck extends Command
{
    protected $signature = 'auth:debug-check {email : User email} {password : Plain password (not stored)}';

    protected $description = 'Local/staging: check if user exists and Hash::check result (no auth bypass)';

    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));
        $password = $this->argument('password');

        $user = User::where('email', $email)->first();

        $this->info('Auth debug check (do not run in production with real passwords).');
        $this->newLine();
        $this->table(
            ['Check', 'Result'],
            [
                ['User found', $user ? 'Yes (id: ' . $user->id . ')' : 'No'],
                ['Stored hash prefix', $user ? substr($user->getAuthPassword(), 0, 7) . '...' : 'N/A'],
                ['Hash::check(password, stored)', $user ? (Hash::check($password, $user->getAuthPassword()) ? 'Yes' : 'No') : 'N/A'],
                ['Default hash driver', config('hashing.driver', 'bcrypt')],
                ['Guard (config)', config('auth.defaults.guard', 'web')],
            ]
        );

        return Command::SUCCESS;
    }
}
