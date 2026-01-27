<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:database 
                            {--disk=local : Storage disk to use}
                            {--compress : Compress the backup using gzip}
                            {--keep=7 : Number of backups to keep}
                            {--tables=* : Specific tables to backup (empty for all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a database backup and optionally upload to cloud storage';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting database backup...');

        try {
            $filename = $this->createBackup();
            $this->cleanOldBackups();
            
            $this->info("Backup completed: {$filename}");
            Log::info('Database backup completed', ['filename' => $filename]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Backup failed: {$e->getMessage()}");
            Log::error('Database backup failed', ['error' => $e->getMessage()]);

            return Command::FAILURE;
        }
    }

    /**
     * Create the database backup
     */
    protected function createBackup(): string
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");
        $host = config("database.connections.{$connection}.host");
        $username = config("database.connections.{$connection}.username");
        $password = config("database.connections.{$connection}.password");
        $port = config("database.connections.{$connection}.port", 3306);

        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "backup_{$database}_{$timestamp}.sql";
        $compress = $this->option('compress');
        $tables = $this->option('tables');

        $backupPath = storage_path("app/backups");
        
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }

        $filePath = "{$backupPath}/{$filename}";

        // Build mysqldump command
        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --password=%s %s %s > %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            !empty($tables) ? implode(' ', array_map('escapeshellarg', $tables)) : '',
            escapeshellarg($filePath)
        );

        // For SQLite
        if ($connection === 'sqlite') {
            $sqlitePath = config("database.connections.{$connection}.database");
            $command = sprintf('cp %s %s', escapeshellarg($sqlitePath), escapeshellarg($filePath));
            $filename = str_replace('.sql', '.sqlite', $filename);
            $filePath = str_replace('.sql', '.sqlite', $filePath);
        }

        $this->info("Creating backup for database: {$database}");

        exec($command . ' 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('Database dump failed: ' . implode("\n", $output));
        }

        // Compress if requested
        if ($compress && file_exists($filePath)) {
            $this->info('Compressing backup...');
            $gzFilePath = $filePath . '.gz';
            
            $fp = fopen($filePath, 'rb');
            $gz = gzopen($gzFilePath, 'wb9');
            
            while (!feof($fp)) {
                gzwrite($gz, fread($fp, 8192));
            }
            
            fclose($fp);
            gzclose($gz);
            
            // Remove uncompressed file
            unlink($filePath);
            
            $filename .= '.gz';
            $filePath = $gzFilePath;
        }

        // Upload to configured disk if not local
        $disk = $this->option('disk');
        if ($disk !== 'local') {
            $this->info("Uploading to {$disk} storage...");
            
            Storage::disk($disk)->put(
                "backups/{$filename}",
                file_get_contents($filePath)
            );
            
            // Remove local file after upload
            unlink($filePath);
            
            $this->info("Backup uploaded to {$disk}");
        }

        return $filename;
    }

    /**
     * Clean old backups
     */
    protected function cleanOldBackups(): void
    {
        $keep = (int) $this->option('keep');
        $disk = $this->option('disk');
        
        if ($keep <= 0) {
            return;
        }

        $this->info("Cleaning old backups (keeping last {$keep})...");

        if ($disk === 'local') {
            $backupPath = storage_path("app/backups");
            $files = glob("{$backupPath}/backup_*");
        } else {
            $files = Storage::disk($disk)->files('backups');
            $files = array_filter($files, fn($f) => str_starts_with(basename($f), 'backup_'));
        }

        // Sort by modification time (newest first)
        usort($files, function ($a, $b) use ($disk) {
            if ($disk === 'local') {
                return filemtime($b) - filemtime($a);
            }
            return Storage::disk($disk)->lastModified($b) - Storage::disk($disk)->lastModified($a);
        });

        // Delete old backups
        $toDelete = array_slice($files, $keep);
        foreach ($toDelete as $file) {
            if ($disk === 'local') {
                unlink($file);
            } else {
                Storage::disk($disk)->delete($file);
            }
            $this->line("  Deleted: " . basename($file));
        }

        $deleted = count($toDelete);
        if ($deleted > 0) {
            $this->info("Deleted {$deleted} old backup(s)");
        }
    }
}
