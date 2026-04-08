<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

class PurgeUserCommand extends Command
{
    protected $signature = 'user:purge
                            {user : User ID ya email}
                            {--by=auto : Lookup mode: auto, id, email}
                            {--force : Preview ke bajaye actual delete run kare}
                            {--force-admin : Admin user ko bhi delete allow kare}';

    protected $description = 'User aur us se related data ko purge karta hai. Default mode dry-run/preview hai.';

    public function handle(): int
    {
        $user = $this->resolveUser();

        if (! $user) {
            $this->error('User nahi mila. ID ya email dobara check karein.');

            return self::FAILURE;
        }

        if ($user->hasRole('admin') && ! $this->option('force-admin')) {
            $this->error('Ye admin user hai. Agar isi ko delete karna hai to --force-admin use karein.');

            return self::FAILURE;
        }

        $summary = $this->buildSummary($user);

        $this->info('User purge summary:');
        $this->table(
            ['Field', 'Value'],
            [
                ['User ID', (string) $user->id],
                ['Name', $user->name],
                ['Email', $user->email],
                ['Role', $user->role ?? '-'],
                ['Verified', $user->email_verified_at ? 'yes' : 'no'],
            ]
        );

        $this->table(
            ['Related Data', 'Rows'],
            collect($summary)
                ->map(fn ($count, $label) => [$label, (string) $count])
                ->values()
                ->all()
        );

        if (! $this->option('force')) {
            $this->warn('Preview mode hai. Koi data delete nahi hua.');
            $this->line('Actual delete ke liye ye command run karein:');
            $this->line(sprintf(
                'php artisan user:purge %s --by=%s --force%s',
                $this->escapeArgument($this->argument('user')),
                $this->option('by'),
                $user->hasRole('admin') ? ' --force-admin' : ''
            ));

            return self::SUCCESS;
        }

        try {
            DB::transaction(function () use ($user): void {
                $this->cleanupManualArtifacts($user);
                $user->delete();
            });

            app(PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (Throwable $e) {
            $this->error('Delete failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("User #{$user->id} purge ho gaya.");

        return self::SUCCESS;
    }

    protected function resolveUser(): ?User
    {
        $input = trim((string) $this->argument('user'));
        $by = strtolower((string) $this->option('by'));

        return match ($by) {
            'id' => User::find($input),
            'email' => User::where('email', $input)->first(),
            default => is_numeric($input)
                ? User::find($input) ?? User::where('email', $input)->first()
                : User::where('email', $input)->first(),
        };
    }

    protected function buildSummary(User $user): array
    {
        $summary = [
            'campaigns' => $this->countRows('campaigns', 'user_id', $user->id),
            'domains' => $this->countRows('domains', 'user_id', $user->id),
            'connected_accounts' => $this->countRows('connected_accounts', 'user_id', $user->id),
            'site_accounts' => $this->countRows('site_accounts', 'user_id', $user->id),
            'social_accounts' => $this->countRows('social_accounts', 'user_id', $user->id),
            'user_notifications' => $this->countRows('user_notifications', 'user_id', $user->id),
            'notifications' => $this->countRows('notifications', 'user_id', $user->id),
            'user_provider_settings' => $this->countRows('user_provider_settings', 'user_id', $user->id),
            'automation_campaigns' => $this->countRows('automation_campaigns', 'user_id', $user->id),
            'automation_jobs' => $this->countRows('automation_jobs', 'user_id', $user->id),
            'backlink_attempts' => $this->countRows('backlink_attempts', 'user_id', $user->id),
            'domain_audits' => $this->countRows('domain_audits', 'user_id', $user->id),
            'audits' => $this->countRows('audits', 'user_id', $user->id),
            'public_reports' => $this->countRows('public_reports', 'user_id', $user->id),
            'content_briefs' => $this->countRows('content_briefs', 'user_id', $user->id),
            'crawl_cost_logs' => $this->countRows('crawl_cost_logs', 'user_id', $user->id),
            'disavow_files' => $this->countRows('disavow_files', 'user_id', $user->id),
            'rank_checks' => $this->countRows('rank_checks', 'user_id', $user->id),
            'notification_rules' => $this->countRows('notification_rules', 'user_id', $user->id),
            'notification_endpoints' => $this->countRows('notification_endpoints', 'user_id', $user->id),
            'email_events' => $this->countRows('email_events', 'user_id', $user->id),
            'usage_counters' => $this->countRows('usage_counters', 'user_id', $user->id),
            'usage_events' => $this->countRows('usage_events', 'user_id', $user->id),
            'organizations_owned' => $this->countRows('organizations', 'owner_user_id', $user->id),
            'organization_memberships' => $this->countRows('organization_users', 'user_id', $user->id),
            'teams_owned' => $this->countRows('teams', 'owner_user_id', $user->id),
            'team_memberships' => $this->countRows('team_members', 'user_id', $user->id),
            'sessions' => $this->countRows('sessions', 'user_id', $user->id),
            'password_reset_tokens' => $this->countRows('password_reset_tokens', 'email', $user->email),
            'model_has_roles' => $this->countModelRows($this->permissionTable('model_has_roles'), $user->id),
            'model_has_permissions' => $this->countModelRows($this->permissionTable('model_has_permissions'), $user->id),
        ];

        return array_filter($summary, static fn ($count) => $count > 0);
    }

    protected function cleanupManualArtifacts(User $user): void
    {
        $this->deleteModelRows($this->permissionTable('model_has_roles'), $user->id);
        $this->deleteModelRows($this->permissionTable('model_has_permissions'), $user->id);
        $this->deleteRows('sessions', 'user_id', $user->id);
        $this->deleteRows('password_reset_tokens', 'email', $user->email);
    }

    protected function countRows(string $table, string $column, mixed $value): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return 0;
        }

        return DB::table($table)->where($column, $value)->count();
    }

    protected function deleteRows(string $table, string $column, mixed $value): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        DB::table($table)->where($column, $value)->delete();
    }

    protected function countModelRows(?string $table, int $userId): int
    {
        if (! $table || ! Schema::hasTable($table)) {
            return 0;
        }

        return DB::table($table)
            ->where('model_type', User::class)
            ->where('model_id', $userId)
            ->count();
    }

    protected function deleteModelRows(?string $table, int $userId): void
    {
        if (! $table || ! Schema::hasTable($table)) {
            return;
        }

        DB::table($table)
            ->where('model_type', User::class)
            ->where('model_id', $userId)
            ->delete();
    }

    protected function permissionTable(string $key): ?string
    {
        return config("permission.table_names.{$key}");
    }

    protected function escapeArgument(string $value): string
    {
        if (preg_match('/^[A-Za-z0-9@\.\-_]+$/', $value)) {
            return $value;
        }

        return '"'.str_replace('"', '\"', $value).'"';
    }
}
