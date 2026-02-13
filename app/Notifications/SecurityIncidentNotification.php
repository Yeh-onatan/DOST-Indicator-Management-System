<?php

namespace App\Notifications;

use App\Models\SecurityIncident;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Security Incident Notification
 *
 * Notifies administrators about security incidents
 * SOC 2 & ISO 27001: Incident response notification requirement
 */
class SecurityIncidentNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public SecurityIncident $incident
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $severityEmoji = match($this->incident->severity) {
            SecurityIncident::SEVERITY_CRITICAL => '🔴',
            SecurityIncident::SEVERITY_HIGH => '🟠',
            SecurityIncident::SEVERITY_MEDIUM => '🟡',
            SecurityIncident::SEVERITY_LOW => '🟢',
            default => '⚪',
        };

        return (new MailMessage)
            ->subject("{$severityEmoji} Security Incident: {$this->incident->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line("A new security incident has been detected and requires your attention.")
            ->line("**Incident ID:** {$this->incident->incident_id}")
            ->line("**Severity:** {$this->incident->severity}")
            ->line("**Type:** {$this->incident->type}")
            ->line("**Title:** {$this->incident->title}")
            ->line("**Description:** {$this->incident->description}")
            ->line("**Detected At:** {$this->incident->detected_at->format('Y-m-d H:i:s')}")
            ->action('View Incident', url("/admin/incidents/{$this->incident->id}"))
            ->line('Please investigate and take appropriate action.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'incident_id' => $this->incident->id,
            'incident_ref' => $this->incident->incident_id,
            'title' => $this->incident->title,
            'severity' => $this->incident->severity,
            'type' => $this->incident->type,
            'status' => $this->incident->status,
            'description' => $this->incident->description,
            'detected_at' => $this->incident->detected_at->toIso8601String(),
        ];
    }
}
