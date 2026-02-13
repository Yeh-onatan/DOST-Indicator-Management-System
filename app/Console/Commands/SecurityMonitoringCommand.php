<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\IncidentService;
use App\Services\SecurityMonitorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Security Monitoring Command
 *
 * Runs scheduled security checks and anomaly detection
 * SOC 2 & ISO 27001: Continuous monitoring requirement
 */
class SecurityMonitoringCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:monitor
        {--detailed : Show detailed output}
        {--alert : Create incidents for issues found}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run scheduled security monitoring and anomaly detection';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting security monitoring...');
        $this->newLine();

        $detailed = $this->option('detailed');
        $shouldAlert = $this->option('alert');
        $issues = [];

        // 1. Check for users with expired passwords
        $this->info('Checking for expired passwords...');
        $expiredPasswordUsers = User::whereHas('passwordHistories')
            ->get()
            ->filter(fn ($user) => $user->isPasswordExpired());

        if ($expiredPasswordUsers->isNotEmpty()) {
            $this->warning("Found {$expiredPasswordUsers->count()} users with expired passwords");
            $issues[] = "{$expiredPasswordUsers->count()} users have expired passwords";

            if ($detailed) {
                foreach ($expiredPasswordUsers as $user) {
                    $this->line("  - {$user->username} (expired: {$user->password_changed_at?->format('Y-m-d')})");
                }
            }
        } else {
            $this->line('✓ No expired passwords found');
        }

        $this->newLine();

        // 2. Check for unusual activity patterns
        $this->info('Analyzing activity patterns...');
        $metrics = SecurityMonitorService::getSecurityMetrics(1);

        if ($detailed) {
            $this->line("  Failed logins (24h): {$metrics['failed_logins']}");
            $this->line("  Suspicious activities (24h): {$metrics['suspicious_activity']}");
            $this->line("  Data exports (24h): {$metrics['data_exports']}");
        }

        // Thresholds for alerting
        if ($metrics['failed_logins'] > 50) {
            $this->warning("High number of failed logins: {$metrics['failed_logins']}");
            $issues[] = "High failed login count: {$metrics['failed_logins']}";
        } else {
            $this->line('✓ Failed login count within normal range');
        }

        if ($metrics['suspicious_activity'] > 10) {
            $this->warning("High number of suspicious activities: {$metrics['suspicious_activity']}");
            $issues[] = "High suspicious activity count: {$metrics['suspicious_activity']}";
        } else {
            $this->line('✓ Suspicious activity count within normal range');
        }

        $this->newLine();

        // 3. Check for orphaned/locked accounts
        $this->info('Checking for inactive accounts...');
        $inactiveDays = 90;
        $inactiveUsers = User::where('last_login_at', '<', now()->subDays($inactiveDays))
            ->orWhere('last_login_at', null)
            ->count();

        if ($inactiveUsers > 0) {
            $this->warn("Found {$inactiveUsers} users inactive for more than {$inactiveDays} days");
            if ($inactiveUsers > 100) {
                $issues[] = "High number of inactive accounts: {$inactiveUsers}";
            }
        } else {
            $this->line('✓ No inactive accounts found');
        }

        $this->newLine();

        // 4. Check for users with excessive roles
        $this->info('Checking role assignments...');
        $superAdminCount = User::where('role', User::ROLE_SUPER_ADMIN)->count();
        $adminCount = User::whereIn('role', [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN])->count();

        $this->line("  Super admins: {$superAdminCount}");
        $this->line("  Total admins: {$adminCount}");

        if ($superAdminCount > 5) {
            $this->warning("High number of super admin accounts: {$superAdminCount}");
            $issues[] = "Excessive super admin accounts: {$superAdminCount}";
        } else {
            $this->line('✓ Admin count within acceptable range');
        }

        $this->newLine();

        // 5. System health checks
        $this->info('Checking system health...');

        // Check disk space (if available)
        $diskFree = disk_free_space(base_path());
        $diskTotal = disk_total_space(base_path());
        $diskUsagePercent = (($diskTotal - $diskFree) / $diskTotal) * 100;

        $this->line("  Disk usage: " . number_format($diskUsagePercent, 2) . '%');

        if ($diskUsagePercent > 90) {
            $this->error('Disk space critically low!');
            $issues[] = "Disk usage at {$diskUsagePercent}%";
        } elseif ($diskUsagePercent > 80) {
            $this->warning('Disk space running low');
            $issues[] = "Disk usage at {$diskUsagePercent}%";
        } else {
            $this->line('✓ Disk space OK');
        }

        $this->newLine();

        // Summary
        $this->info('─── Security Monitoring Summary ───');

        if (empty($issues)) {
            $this->info('✓ All security checks passed!');
            Log::info('Security monitoring completed successfully');
            return self::SUCCESS;
        }

        $this->error('Security issues detected:');
        foreach ($issues as $issue) {
            $this->line("  • {$issue}");
        }

        // Create security incident if alert option is enabled
        if ($shouldAlert && count($issues) > 0) {
            $this->newLine();
            $this->info('Creating security incident...');

            IncidentService::report(
                SecurityIncident::TYPE_SUSPICIOUS_ACTIVITY,
                SecurityIncident::SEVERITY_MEDIUM,
                'Scheduled Security Monitoring Detected Issues',
                'Automated security monitoring detected the following issues: ' . implode(', ', $issues),
                [
                    'issues' => $issues,
                    'metrics' => $metrics,
                    'timestamp' => now()->toIso8601String(),
                ]
            );

            $this->line('✓ Security incident created');
        }

        Log::warning('Security monitoring detected issues', ['issues' => $issues]);

        return self::FAILURE;
    }
}
