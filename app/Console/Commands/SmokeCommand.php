<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class SmokeCommand extends Command
{
    protected $signature = 'backlinkpro:smoke';

    protected $description = 'Smoke check for staging/prod: DB, login route, queues, key env (no data modification)';

    public function handle(): int
    {
        $fail = false;

        $this->info('BacklinkPro smoke check (read-only).');
        $this->newLine();

        if (!$this->checkDb()) {
            $fail = true;
        }
        if (!$this->checkLoginRoute()) {
            $fail = true;
        }
        if (!$this->checkQueuesConfig()) {
            $fail = true;
        }
        if (!$this->checkEnv()) {
            $fail = true;
        }

        $this->newLine();
        if ($fail) {
            $this->error('One or more checks failed.');
            return Command::FAILURE;
        }
        $this->info('All smoke checks passed.');
        return Command::SUCCESS;
    }

    private function checkDb(): bool
    {
        try {
            DB::connection()->getPdo();
            DB::connection()->getDatabaseName();
            $this->info('  [PASS] DB connection OK');
            return true;
        } catch (\Throwable $e) {
            $this->error('  [FAIL] DB connection: ' . $e->getMessage());
            return false;
        }
    }

    private function checkLoginRoute(): bool
    {
        try {
            $route = Route::getRoutes()->getByName('login');
            if ($route && $route->uri() === 'login') {
                $this->info('  [PASS] /login route exists');
                return true;
            }
        } catch (\Throwable $e) {
            // name might not exist
        }
        $found = false;
        foreach (Route::getRoutes() as $r) {
            if ($r->uri() === 'login' && in_array('GET', $r->methods())) {
                $found = true;
                break;
            }
        }
        if ($found) {
            $this->info('  [PASS] /login route exists');
            return true;
        }
        $this->error('  [FAIL] /login route not found');
        return false;
    }

    private function checkQueuesConfig(): bool
    {
        $conn = config('queue.default');
        if ($conn && config("queue.connections.{$conn}")) {
            $this->info('  [PASS] Queues config present (default: ' . $conn . ')');
            return true;
        }
        $this->error('  [FAIL] Queues config missing or invalid');
        return false;
    }

    private function checkEnv(): bool
    {
        $ok = true;
        if (empty(config('app.key'))) {
            $this->error('  [FAIL] APP_KEY is empty');
            $ok = false;
        } else {
            $this->info('  [PASS] APP_KEY set');
        }
        if (empty(config('app.url'))) {
            $this->error('  [FAIL] APP_URL is empty');
            $ok = false;
        } else {
            $this->info('  [PASS] APP_URL set');
        }
        return $ok;
    }
}
