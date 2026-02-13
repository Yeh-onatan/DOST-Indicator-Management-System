<?php

namespace App\Services;

use App\Models\SystemNotification;
use App\Models\Objective as Indicator;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Get the singleton instance of NotificationService.
     */
    public static function make(): self
    {
        return App::make(self::class);
    }

    /**
     * Create a notification for a user.
     */
    public function createNotification(User $user, string $type, string $title, string $message, ?string $actionUrl = null, ?array $data = null): SystemNotification
    {
        $notification = SystemNotification::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'action_url' => $actionUrl,
            'data' => array_merge($data ?? [], [
                'notifiable_id' => $user->id,
                'notifiable_type' => User::class,
            ]),
        ]);

        // Also send email if enabled
        if ($user->email_notifications_enabled ?? true) {
            $this->sendEmailNotification($user, $type, $title, $message, $actionUrl);
        }

        return $notification;
    }

    /**
     * Notify when agency submission is sent to their assigned HO.
     */
    public function notifyAgencySubmissionToHO(Indicator $objective, User $ho): void
    {
        $submitter = $objective->submitter;
        if (!$submitter) return;

        $indicatorTitle = $objective->indicator ?? "Indicator #{$objective->id}";
        $submitterName = $submitter->name ?? $submitter->username;

        $this->createNotification(
            $ho,
            'info',
            'New Agency Submission',
            "{$submitterName} submitted \"{$indicatorTitle}\" for your review.",
            url('/dashboard?search=' . $objective->id),
            [
                'objective_id' => $objective->id,
                'submitter_name' => $submitterName,
                'agency_id' => $submitter->agency_id,
            ]
        );
    }

    /**
     * Notify when indicator is submitted.
     */
    public function notifyIndicatorSubmitted(Indicator $objective): void
    {
        $submitter = $objective->submitter;
        if (!$submitter) return;

        $indicatorTitle = $objective->indicator ?? "Indicator #{$objective->id}";
        $submitterName = $submitter->name ?? $submitter->username;

        // Use office hierarchy: find the parent RO office and notify its head
        $pstoOffice = $objective->office;
        if ($pstoOffice && $pstoOffice->parent_office_id) {
            // Get the parent RO office
            $roOffice = \App\Models\Office::find($pstoOffice->parent_office_id);

            if ($roOffice && $roOffice->head_user_id) {
                // Notify the head of the RO office
                $roHead = User::find($roOffice->head_user_id);
                if ($roHead) {
                    $this->createNotification(
                        $roHead,
                        'info',
                        'New Indicator Submitted',
                        "{$submitterName} submitted \"{$indicatorTitle}\" for review.",
                        url('/dashboard?search=' . $objective->id),
                        [
                            'objective_id' => $objective->id,
                            'submitter_name' => $submitterName,
                        ]
                    );
                }
            }
        }

        // Also notify Admins/SuperAdmin
        $admins = User::whereIn('role', ['administrator', 'super_admin'])->get();
        foreach ($admins as $admin) {
            $this->createNotification(
                $admin,
                'info',
                'New Indicator Submitted',
                "{$submitterName} submitted \"{$indicatorTitle}\" for review.",
                url('/dashboard?search=' . $objective->id),
                [
                    'objective_id' => $objective->id,
                    'submitter_name' => $submitterName,
                ]
            );
        }
    }

    /**
     * Notify when indicator is approved.
     */
    public function notifyIndicatorApproved(Indicator $objective): void
    {
        $submitter = $objective->submitter;
        if (!$submitter) return;

        $indicatorTitle = $objective->indicator ?? "Indicator #{$objective->id}";
        $approver = auth()->user();
        $approverName = $approver->name ?? $approver->username;

        $this->createNotification(
            $submitter,
            'success',
            'Indicator Approved!',
            "Your indicator \"{$indicatorTitle}\" has been approved.",
            url('/dashboard?search=' . $objective->id),
            [
                'objective_id' => $objective->id,
                'approver_name' => $approverName,
            ]
        );
    }

    /**
     * Notify when indicator is rejected/returned.
     */
    public function notifyIndicatorRejected(Indicator $objective, ?string $reason = null, ?User $recipient = null): void
    {
        // If no recipient specified, use the submitter
        $recipient = $recipient ?? $objective->submitter;
        if (!$recipient) return;

        $indicatorTitle = $objective->indicator ?? "Indicator #{$objective->id}";

        // Use rejected_by if available, otherwise try auth user
        $rejector = null;
        if ($objective->rejected_by) {
            $rejector = User::find($objective->rejected_by);
        }
        if (!$rejector) {
            $rejector = auth()->user();
        }

        $rejectorName = $rejector?->name ?? $rejector?->username ?? 'System';
        $returnLevel = ($rejector && $rejector->isRO()) ? 'Regional Office' : 'Head Office';

        $message = "\"{$indicatorTitle}\" was returned from {$returnLevel}";
        if ($reason) {
            $message .= ". Reason: {$reason}";
        }

        $this->createNotification(
            $recipient,
            'warning',
            'Indicator Returned',
            $message,
            url('/dashboard?search=' . $objective->id),
            [
                'objective_id' => $objective->id,
                'rejector_name' => $rejectorName,
                'rejection_reason' => $reason,
                'return_level' => $returnLevel,
            ]
        );
    }

    /**
     * Notify HO about RO rejections.
     */
    public function notifyHOAboutRejection(Indicator $objective, User $ro, ?string $reason, User $ho): void
    {
        $indicatorTitle = $objective->indicator ?? "Indicator #{$objective->id}";
        $roName = $ro->name ?? $ro->username;

        $message = "RO {$roName} rejected \"{$indicatorTitle}\"";
        if ($reason) {
            $message .= ". Reason: {$reason}";
        }

        $this->createNotification(
            $ho,
            'info',
            'RO Rejection Notification',
            $message,
            url('/dashboard?search=' . $objective->id),
            [
                'objective_id' => $objective->id,
                'ro_name' => $roName,
                'rejection_reason' => $reason,
            ]
        );
    }

    /**
     * Notify when indicator is forwarded to next level.
     */
    public function notifyIndicatorForwarded(Indicator $objective, string $toLevel): void
    {
        $submitter = $objective->submitter;
        if (!$submitter) return;

        $indicatorTitle = $objective->indicator ?? "Indicator #{$objective->id}";

        $this->createNotification(
            $submitter,
            'info',
            'Indicator Forwarded',
            "\"{$indicatorTitle}\" has been forwarded to {$toLevel}.",
            url('/dashboard?search=' . $objective->id),
            [
                'objective_id' => $objective->id,
                'forwarded_to' => $toLevel,
            ]
        );
    }

    /**
     * Notify admins when a new agency is created
     */
    public function notifyAgencyCreated(\App\Models\DOSTAgency $agency): void
    {
        $creator = auth()->user();
        $creatorName = $creator->name ?? $creator->username ?? 'System';

        // Notify all admins and super admins about the new agency (except creator)
        $admins = User::whereIn('role', ['administrator', 'super_admin'])
            ->where('id', '!=', $creator->id)
            ->get();

        foreach ($admins as $admin) {
            $this->createNotification(
                $admin,
                'info',
                'New Agency Created',
                "{$creatorName} created new agency: {$agency->name} ({$agency->code})",
                url('/admin/manage'),
                [
                    'agency_id' => $agency->id,
                    'agency_name' => $agency->name,
                    'agency_code' => $agency->code,
                    'cluster' => $agency->cluster,
                    'creator_name' => $creatorName,
                ]
            );
        }
    }

    /**
     * Notify admins when a new office is created
     */
    public function notifyOfficeCreated(\App\Models\Office $office): void
    {
        $creator = auth()->user();
        $creatorName = $creator->name ?? $creator->username ?? 'System';

        // Notify all admins and super admins about the new office
        $admins = User::whereIn('role', ['administrator', 'super_admin'])
            ->where('id', '!=', $creator->id) // Don't notify the creator
            ->get();

        foreach ($admins as $admin) {
            $this->createNotification(
                $admin,
                'info',
                'New Office Created',
                "{$creatorName} created new office: {$office->name} ({$office->code})",
                url('/admin/manage'),
                [
                    'office_id' => $office->id,
                    'office_name' => $office->name,
                    'office_code' => $office->code,
                    'office_type' => $office->type,
                    'creator_name' => $creatorName,
                ]
            );
        }
    }

    /**
     * Notify OUSEC users when indicator is submitted for their review
     */
    public function notifyIndicatorSubmittedToOUSEC(Indicator $objective): void
    {
        $submitter = $objective->submitter;
        if (!$submitter) return;

        $indicatorTitle = $objective->indicator ?? "Indicator #{$objective->id}";
        $submitterName = $submitter->name ?? $submitter->username;

        // Determine which OUSEC type to notify
        $ousecRole = null;
        $message = '';

        // For regional/PSTO submissions, notify OUSEC-RO
        if ($submitter->office_id || $submitter->region_id) {
            $ousecRole = User::ROLE_OUSEC_RO;
            $message = "{$submitterName} from Regional Office submitted \"{$indicatorTitle}\" for your review.";
        }
        // For agency submissions, determine by cluster
        elseif ($submitter->agency_id) {
            $agency = $submitter->agency;
            if ($agency) {
                if (in_array($agency->cluster, ['ssi', 'collegial'])) {
                    $ousecRole = User::ROLE_OUSEC_STS;
                    $message = "{$submitterName} from {$agency->name} submitted \"{$indicatorTitle}\" for your review.";
                } elseif (in_array($agency->cluster, ['council', 'rdi'])) {
                    $ousecRole = User::ROLE_OUSEC_RD;
                    $message = "{$submitterName} from {$agency->name} submitted \"{$indicatorTitle}\" for your review.";
                }
            }
        }

        if ($ousecRole) {
            $ousecUsers = User::where('role', $ousecRole)->get();
            foreach ($ousecUsers as $ousec) {
                $this->createNotification(
                    $ousec,
                    'info',
                    'New Indicator for OUSEC Review',
                    $message,
                    url('/dashboard?search=' . $objective->id),
                    [
                        'objective_id' => $objective->id,
                        'submitter_name' => $submitterName,
                    ]
                );
            }
        }
    }

    /**
     * Get unread notification count for a user.
     */
    public function getUnreadCount(User $user): int
    {
        return SystemNotification::forUser($user)
            ->unread()
            ->count();
    }

    /**
     * Get notifications for a user.
     */
    public function getNotifications(User $user, int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return SystemNotification::forUser($user)
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Send email notification.
     */
    protected function sendEmailNotification(User $user, string $type, string $title, string $message, ?string $actionUrl = null): void
    {
        try {
            // Skip emails with placeholder addresses for testing
            if (str_ends_with($user->email, '@local') || !$user->email) {
                Log::info('Skipped email notification for placeholder address', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'title' => $title,
                ]);
                return;
            }

            // Queue email to avoid slowing down the request
            Mail::to($user->email)->queue(new \App\Mail\SystemNotificationEmail($type, $title, $message, $actionUrl));

            Log::info('Email notification queued', [
                'user_id' => $user->id,
                'email' => $user->email,
                'title' => $title,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send email notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
