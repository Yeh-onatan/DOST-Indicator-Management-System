<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\SecurityIncident;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Generate Compliance Report
 *
 * Generates reports for security compliance audits
 * PCI DSS, SOC 2, ISO 27001
 */
class ComplianceReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'compliance:report
        {--format=console : Output format (console, json, markdown)}
        {--period=30 : Reporting period in days}
        {--output= : Output file path (for json/markdown)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate security compliance report';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $period = (int) $this->option('period');
        $format = $this->option('format');
        $output = $this->option('output');

        $report = $this->generateReport($period);

        if ($format === 'json') {
            $json = json_encode($report, JSON_PRETTY_PRINT);
            if ($output) {
                file_put_contents($output, $json);
                $this->info("Report saved to: {$output}");
            } else {
                $this->line($json);
            }
        } elseif ($format === 'markdown') {
            $markdown = $this->formatMarkdown($report);
            if ($output) {
                file_put_contents($output, $markdown);
                $this->info("Report saved to: {$output}");
            } else {
                $this->line($markdown);
            }
        } else {
            $this->displayReport($report);
        }

        return self::SUCCESS;
    }

    /**
     * Generate the compliance report
     */
    protected function generateReport(int $period): array
    {
        $cutoff = now()->subDays($period);

        return [
            'metadata' => [
                'generated_at' => now()->toIso8601String(),
                'period_days' => $period,
                'period_start' => $cutoff->toIso8601String(),
                'period_end' => now()->toIso8601String(),
            ],
            'standards' => [
                'pci_dss' => $this->getPciDssStatus($cutoff),
                'soc_2' => $this->getSoc2Status($cutoff),
                'iso_27001' => $this->getIso27001Status($cutoff),
                'owasp' => $this->getOwaspStatus($cutoff),
            ],
            'metrics' => [
                'users' => $this->getUserMetrics($cutoff),
                'authentication' => $this->getAuthenticationMetrics($cutoff),
                'data_access' => $this->getDataAccessMetrics($cutoff),
                'incidents' => $this->getIncidentMetrics($cutoff),
                'backups' => $this->getBackupMetrics($cutoff),
            ],
        ];
    }

    /**
     * PCI DSS compliance status
     */
    protected function getPciDssStatus($cutoff): array
    {
        return [
            'standard' => 'PCI DSS',
            'version' => '4.0',
            'requirements' => [
                '4.1 - Strong cryptography' => [
                    'status' => 'implemented',
                    'description' => 'HTTPS enforced, HSTS active, session encryption enabled',
                ],
                '8.2.1 - Unique credentials' => [
                    'status' => 'implemented',
                    'description' => 'Username-based authentication with unique usernames',
                ],
                '8.2.2 - Password complexity' => [
                    'status' => 'implemented',
                    'description' => 'Minimum 12 characters, mixed case, numbers, symbols required',
                ],
                '8.2.4 - Password change' => [
                    'status' => 'implemented',
                    'description' => 'Password expires every 90 days, cannot reuse last 4',
                ],
                '8.2.5 - Session timeout' => [
                    'status' => 'implemented',
                    'description' => '15-minute inactivity timeout',
                ],
                '8.3 - Multi-factor authentication' => [
                    'status' => 'not_implemented',
                    'description' => 'MFA/2FA not currently enabled',
                ],
                '10.2 - Audit logs' => [
                    'status' => 'implemented',
                    'description' => 'Comprehensive audit trail with IP, user agent, timestamps',
                ],
            ],
            'overall_score' => '85%',
            'gap' => 'Multi-factor authentication required for full compliance',
        ];
    }

    /**
     * SOC 2 compliance status
     */
    protected function getSoc2Status($cutoff): array
    {
        return [
            'standard' => 'SOC 2 Type II',
            'trust_principles' => [
                'Security' => [
                    'status' => 'implemented',
                    'controls' => [
                        'Access controls' => 'Role-based permissions implemented',
                        'Encryption' => 'Data at rest and in transit encrypted',
                        'Monitoring' => 'Security monitoring and incident response',
                    ],
                ],
                'Availability' => [
                    'status' => 'partial',
                    'controls' => [
                        'Backups' => 'Automated backup verification in place',
                        'Disaster recovery' => 'Documentation pending',
                    ],
                ],
                'Processing Integrity' => [
                    'status' => 'implemented',
                    'controls' => [
                        'Data validation' => 'Input validation on all forms',
                        'Audit logging' => 'Complete data change tracking',
                    ],
                ],
                'Confidentiality' => [
                    'status' => 'implemented',
                    'controls' => [
                        'Data encryption' => 'Enabled',
                        'Access logging' => 'All access logged',
                    ],
                ],
                'Privacy' => [
                    'status' => 'partial',
                    'controls' => [
                        'Data retention' => 'Policy to be defined',
                        'User consent' => 'Implementation pending',
                    ],
                ],
            ],
            'overall_score' => '80%',
            'gap' => 'Disaster recovery testing and privacy controls required',
        ];
    }

    /**
     * ISO 27001 compliance status
     */
    protected function getIso27001Status($cutoff): array
    {
        return [
            'standard' => 'ISO/IEC 27001:2022',
            'controls' => [
                'A.5.1 - Policies for information security' => [
                    'status' => 'partial',
                    'description' => 'Security policies defined, documentation pending',
                ],
                'A.8.2 - Privileged access rights' => [
                    'status' => 'implemented',
                    'description' => 'Role-based access control with audit trail',
                ],
                'A.8.3 - Information access authentication' => [
                    'status' => 'partial',
                    'description' => 'Strong password policy, MFA pending',
                ],
                'A.12.4 - Logging and monitoring' => [
                    'status' => 'implemented',
                    'description' => 'Comprehensive audit logging and monitoring',
                ],
                'A.16.1 - Management of information security incidents' => [
                    'status' => 'implemented',
                    'description' => 'Incident detection, reporting, and response',
                ],
                'A.12.3 - Backup' => [
                    'status' => 'partial',
                    'description' => 'Backup system in place, testing procedures pending',
                ],
                'A.18.2 - Information security incident management' => [
                    'status' => 'implemented',
                    'description' => 'Incident response procedures defined',
                ],
            ],
            'overall_score' => '75%',
            'gap' => 'Policy documentation and formal risk assessment required',
        ];
    }

    /**
     * OWASP Top 10 status
     */
    protected function getOwaspStatus($cutoff): array
    {
        return [
            'standard' => 'OWASP Top 10 (2021)',
            'risks' => [
                'A01: Broken Access Control' => [
                    'status' => 'mitigated',
                    'description' => 'Role-based middleware, authorization checks',
                ],
                'A02: Cryptographic Failures' => [
                    'status' => 'mitigated',
                    'description' => 'HTTPS, encrypted sessions, hashed passwords',
                ],
                'A03: Injection' => [
                    'status' => 'mitigated',
                    'description' => 'ORM with parameterized queries, input validation',
                ],
                'A04: Insecure Design' => [
                    'status' => 'mitigated',
                    'description' => 'Security requirements, threat modeling',
                ],
                'A05: Security Misconfiguration' => [
                    'status' => 'mitigated',
                    'description' => 'Security headers, environment-based config',
                ],
                'A07: Authentication Failures' => [
                    'status' => 'partial',
                    'description' => 'Strong passwords, rate limiting, MFA pending',
                ],
            ],
            'overall_score' => '90%',
            'gap' => 'MFA implementation recommended',
        ];
    }

    /**
     * Get user metrics
     */
    protected function getUserMetrics($cutoff): array
    {
        return [
            'total_users' => User::count(),
            'active_users' => User::where('last_login_at', '>=', $cutoff)->count(),
            'users_with_expired_passwords' => User::get()->filter(fn ($u) => $u->isPasswordExpired())->count(),
            'admin_count' => User::whereIn('role', [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN])->count(),
        ];
    }

    /**
     * Get authentication metrics
     */
    protected function getAuthenticationMetrics($cutoff): array
    {
        return [
            'successful_logins' => AuditLog::withAction('login')->where('created_at', '>=', $cutoff)->count(),
            'failed_logins' => AuditLog::withAction('login_failed')->where('created_at', '>=', $cutoff)->count(),
            'password_changes' => AuditLog::withAction('password_changed')->where('created_at', '>=', $cutoff)->count(),
        ];
    }

    /**
     * Get data access metrics
     */
    protected function getDataAccessMetrics($cutoff): array
    {
        return [
            'records_created' => AuditLog::withAction('create')->where('created_at', '>=', $cutoff)->count(),
            'records_updated' => AuditLog::withAction('update')->where('created_at', '>=', $cutoff)->count(),
            'records_deleted' => AuditLog::withAction('delete')->where('created_at', '>=', $cutoff)->count(),
            'data_exports' => AuditLog::withAction('export')->where('created_at', '>=', $cutoff)->count(),
        ];
    }

    /**
     * Get incident metrics
     */
    protected function getIncidentMetrics($cutoff): array
    {
        $incidents = SecurityIncident::where('detected_at', '>=', $cutoff)->get();

        return [
            'total_incidents' => $incidents->count(),
            'open_incidents' => $incidents->whereIn('status', [SecurityIncident::STATUS_OPEN, SecurityIncident::STATUS_INVESTIGATING])->count(),
            'resolved_incidents' => $incidents->where('status', SecurityIncident::STATUS_RESOLVED)->count(),
            'critical_incidents' => $incidents->where('severity', SecurityIncident::SEVERITY_CRITICAL)->count(),
            'high_incidents' => $incidents->where('severity', SecurityIncident::SEVERITY_HIGH)->count(),
        ];
    }

    /**
     * Get backup metrics
     */
    protected function getBackupMetrics($cutoff): array
    {
        $backups = \App\Models\BackupLog::where('started_at', '>=', $cutoff)->get();

        return [
            'total_backups' => $backups->count(),
            'successful_backups' => $backups->where('status', 'success')->count(),
            'failed_backups' => $backups->where('status', 'failed')->count(),
            'success_rate' => $backups->count() > 0
                ? round(($backups->where('status', 'success')->count() / $backups->count()) * 100, 2)
                : 0,
        ];
    }

    /**
     * Display report in console
     */
    protected function displayReport(array $report): void
    {
        $this->info('╔════════════════════════════════════════════════════════════╗');
        $this->info('║           SECURITY COMPLIANCE REPORT                      ║');
        $this->info('╚════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $this->line("Generated: {$report['metadata']['generated_at']}");
        $this->line("Period: Last {$report['metadata']['period_days']} days");
        $this->newLine();

        foreach ($report['standards'] as $standard) {
            $this->info("─── {$standard['standard']} ({$standard['overall_score']}) ───");

            if (isset($standard['gap'])) {
                $this->line("Gap: {$standard['gap']}");
            }

            $this->newLine();
        }

        $this->info('─── Metrics ───');
        $this->table(['Metric', 'Value'], [
            ['Total Users', $report['metrics']['users']['total_users']],
            ['Active Users', $report['metrics']['users']['active_users']],
            ['Failed Logins', $report['metrics']['authentication']['failed_logins']],
            ['Open Incidents', $report['metrics']['incidents']['open_incidents']],
            ['Backup Success Rate', $report['metrics']['backups']['success_rate'] . '%'],
        ]);
    }

    /**
     * Format report as markdown
     */
    protected function formatMarkdown(array $report): string
    {
        $md = "# Security Compliance Report\n\n";
        $md .= "**Generated:** {$report['metadata']['generated_at']}\n";
        $md .= "**Period:** Last {$report['metadata']['period_days']} days\n\n";

        foreach ($report['standards'] as $key => $standard) {
            $md .= "## {$standard['standard']} - {$standard['overall_score']}\n\n";

            if (isset($standard['gap'])) {
                $md .= "> **Gap:** {$standard['gap']}\n\n";
            }

            if (isset($standard['requirements'])) {
                foreach ($standard['requirements'] as $req => $details) {
                    $statusIcon = $details['status'] === 'implemented' ? '✅' : '⚠️';
                    $md .= "### {$statusIcon} {$req}\n";
                    $md .= "{$details['description']}\n\n";
                }
            }
        }

        $md .= "## Metrics\n\n";
        $md .= "| Metric | Value |\n";
        $md .= "|--------|-------|\n";
        $md .= "| Total Users | {$report['metrics']['users']['total_users']} |\n";
        $md .= "| Active Users | {$report['metrics']['users']['active_users']} |\n";
        $md .= "| Failed Logins | {$report['metrics']['authentication']['failed_logins']} |\n";
        $md .= "| Open Incidents | {$report['metrics']['incidents']['open_incidents']} |\n";
        $md .= "| Backup Success Rate | {$report['metrics']['backups']['success_rate']}% |\n";

        return $md;
    }
}
