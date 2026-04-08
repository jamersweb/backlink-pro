<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CheckSchemaCommand extends Command
{
    protected $signature = 'backlinkpro:check-schema';

    protected $description = 'Verify required tables/columns exist for GA4/GSC/PSI/backlinks/meta (no data modification)';

    protected array $checks = [
        'users' => ['id', 'email', 'password', 'email_verified_at'],
        'domains' => ['id', 'user_id', 'name'],
        'domain_meta_connectors' => ['id', 'domain_id', 'type', 'status'],
        'domain_meta_pages' => ['id', 'domain_id', 'resource_type'],
        'audits' => ['id', 'user_id', 'status'],
        'campaigns' => ['id', 'user_id', 'domain_id'],
        'plans' => ['id', 'name'],
    ];

    public function handle(): int
    {
        $this->info('Schema integrity check (read-only).');
        $this->newLine();

        $fail = false;
        foreach ($this->checks as $table => $columns) {
            if (!Schema::hasTable($table)) {
                $this->error("  [FAIL] Table missing: {$table}");
                $fail = true;
                continue;
            }
            $missing = [];
            foreach ($columns as $col) {
                if (!Schema::hasColumn($table, $col)) {
                    $missing[] = $col;
                }
            }
            if (!empty($missing)) {
                $this->error("  [FAIL] Table {$table} missing columns: " . implode(', ', $missing));
                $fail = true;
            } else {
                $this->info("  [PASS] {$table}");
            }
        }

        try {
            DB::connection()->getPdo();
            $this->info('  [PASS] DB connection');
        } catch (\Throwable $e) {
            $this->error('  [FAIL] DB connection: ' . $e->getMessage());
            $fail = true;
        }

        $this->newLine();
        if ($fail) {
            $this->error('One or more checks failed.');
            return Command::FAILURE;
        }
        $this->info('All schema checks passed.');
        return Command::SUCCESS;
    }
}
