<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\SystemNotification;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestNotificationCommand extends Command
{
    protected $signature = 'notifications:test {--email= : User email to send to} {--type=info : Notification type} {--count=1 : Number of notifications} {--cc= : CC email for testing (e.g., markjerichonarciso@gmail.com)}';

    protected $description = 'Test notification system (creates test notifications)';

    public function handle(NotificationService $notificationService)
    {
        $this->info('🔔 Testing Notification System...');

        // Get user to notify
        $email = $this->option('email') ?? null;
        $ccEmail = $this->option('cc') ?? null;
        $type = $this->option('type') ?? 'info';
        $count = (int) $this->option('count') ?? 1;

        if ($email) {
            $user = User::where('email', $email)->first();
            if (!$user) {
                $this->error("❌ User with email '{$email}' not found!");
                $this->info("💡 Using first user in database instead...");
                $user = User::first();
            }
        } else {
            $user = User::first();
            if (!$user) {
                $this->error("❌ No users found in database!");
                return 1;
            }
        }

        $this->info("📧 In-app notification to: {$user->email}");
        if ($ccEmail) {
            $this->info("📧 Email CC to: {$ccEmail}");
        }
        $userName = $user->name ?? 'No name';
        $this->info("👤 User: {$userName} ({$user->role})");

        $titles = [
            'info' => 'Test Info Notification',
            'success' => 'Test Success Notification ✅',
            'warning' => 'Test Warning Notification ⚠️',
            'error' => 'Test Error Notification ❌',
        ];

        $messages = [
            'info' => 'This is a test info notification from the command line.',
            'success' => 'Great! This is a test success notification.',
            'warning' => 'Warning! This is a test warning notification.',
            'error' => 'Error! This is a test error notification.',
        ];

        for ($i = 1; $i <= $count; $i++) {
            $notification = $notificationService->createNotification(
                $user,
                $type,
                "{$titles[$type]} #{$i}",
                "{$messages[$type]} (Test #{$i})",
                url('/dashboard'),
                ['test' => true, 'iteration' => $i]
            );

            $this->info("✅ Notification #{$i} created (ID: {$notification->id})");

            // Send CC email if specified
            if ($ccEmail) {
                try {
                    Mail::raw('', function($message) use ($titles, $messages, $type, $i, $ccEmail, $user, $userName) {
                        $message->to($ccEmail)
                            ->subject("[DOST MIS TEST] {$titles[$type]} #{$i}")
                            ->html("
                                <h2>{$titles[$type]} #{$i}</h2>
                                <p>{$messages[$type]} (Test #{$i})</p>
                                <p><strong>Original Recipient:</strong> {$user->email} ({$userName} - {$user->role})</p>
                                <p><em>This is a test email from the DOST MIS notification system.</em></p>
                                <hr>
                                <p><a href='" . url('/dashboard') . "'>Go to Dashboard</a></p>
                            ");
                    })->send();
                    $this->info("📧 CC email sent to: {$ccEmail}");
                } catch (\Exception $e) {
                    $this->warn("⚠️ Failed to send CC email: {$e->getMessage()}");
                }
            }
        }

        $unreadCount = $notificationService->getUnreadCount($user);
        $this->info("📬 Total unread for user: {$unreadCount}");

        $this->newLine();
        $this->info('🎉 Test complete!');
        $this->info('💡 Check your dashboard or run: php artisan tinker');
        $this->info('   Then run: App\Models\SystemNotification::forUser(App\Models\User::first())->get()');

        return 0;
    }
}
