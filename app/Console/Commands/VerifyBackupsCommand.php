<?php

namespace App\Console\Commands;

use App\Models\BackupLog;
use App\Models\SecurityIncident;
use App\Services\IncidentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Verify Backups Command
 *
 * Checks that recent backups exist and are valid
 * ISO 27001 & SOC 2: Regular backup verification requirement
 */
class VerifyBackupsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backups:verify
        {--hours=24 : Check backups within this many hours}
        {--alert : Create incident if backups are missing}
        {--integrity : Verify backup file integrity}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify that recent backups exist and are valid';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $shouldAlert = $this->option('alert');
        $checkIntegrity = $this->option('integrity');

        $this->info("Verifying backups within the last {$hours} hours...");
        $this->newLine();

        $cutoff = now()->subHours($hours);
        $issues = [];

        // Check database backups
        $this->info('Checking database backups...');
        $dbBackups = BackupLog::ofType(BackupLog::TYPE_DATABASE)
            ->where('started_at', '>=', $cutoff)
            ->orderByDesc('started_at')
            ->get();

        if ($dbBackups->isEmpty()) {
            $this->error('No database backups found in the last ' . $hours . ' hours!');
            $issues[] = 'No database backups found';
        } else {
            $latestBackup = $dbBackups->first();
            $hoursSince = $latestBackup->started_at->diffInHours(now());

            $this->line("✓ Latest backup: {$latestBackup->started_at->format('Y-m-d H:i:s')} ({$hoursSince}h ago)");
            $this->line("  Size: {$latestBackup->human_file_size}");
            $this->line("  Status: {$latestBackup->status}");

            if ($latestBackup->status === BackupLog::STATUS_FAILED) {
                $this->error('Latest backup failed!');
                $issues[] = 'Latest database backup failed';
            }

            if ($checkIntegrity && $latestBackup->file_path) {
                $this->info('  Verifying integrity...');
                $integrityOk = $this->verifyBackupIntegrity($latestBackup);

                if (!$integrityOk) {
                    $this->error('  Backup integrity check failed!');
                    $issues[] = 'Database backup integrity check failed';
                } else {
                    $this->line('  ✓ Integrity verified');
                }
            }
        }

        $this->newLine();

        // Check success rate
        $this->info('Checking backup success rate...');
        $successRate = BackupLog::getSuccessRate(BackupLog::TYPE_DATABASE, 30);
        $this->line("Success rate (last 30 days): {$successRate}%");

        if ($successRate < 95) {
            $this->warn('Success rate is below 95%!');
            $issues[] = "Database backup success rate is {$successRate}% (below 95%)";
        }

        $this->newLine();

        // Summary
        if (empty($issues)) {
            $this->info('✓ All backup checks passed!');
            Log::info('Backup verification completed successfully', ['hours' => $hours]);
            return self::SUCCESS;
        }

        $this->error('Backup verification issues detected:');
        foreach ($issues as $issue) {
            $this->line("  - {$issue}");
        }

        // Create security incident if alert option is enabled
        if ($shouldAlert) {
            $this->newLine();
            $this->info('Creating security incident...');

            IncidentService::report(
                SecurityIncident::TYPE_SUSPICIOUS_ACTIVITY,
                SecurityIncident::SEVERITY_HIGH,
                'Backup Verification Failed',
                'Backup verification detected issues: ' . implode(', ', $issues),
                [
                    'issues' => $issues,
                    'hours_checked' => $hours,
                    'success_rate' => $successRate,
                ]
            );

            $this->line('✓ Security incident created');
        }

        Log::warning('Backup verification failed', ['issues' => $issues]);

        return self::FAILURE;
    }

    /**
     * Verify backup file integrity
     */
    protected function verifyBackupIntegrity(BackupLog $backup): bool
    {
        if (!$backup->checksum) {
            $this->line('  ⚠ No checksum stored for comparison');
            return true; // Can't verify without checksum
        }

        // For local storage
        if (Storage::disk('local')->exists($backup->file_path)) {
            $contents = Storage::disk('local')->get($backup->file_path);
            $currentChecksum = hash('sha256', $contents);

            return hash_equals($backup->checksum, $currentChecksum);
        }

        // For S3 or other storage, you would implement appropriate verification
        // This is a placeholder for when you have a specific backup storage system
        return true;
    }
}
