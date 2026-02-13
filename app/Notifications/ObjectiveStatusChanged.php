<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;
use App\Models\Indicator as Objective;

class ObjectiveStatusChanged extends Notification
{
    use Queueable;

    public string $status;
    public ?string $notes;
    public ?array $corrections;
    public Objective $objective;
    public ?int $actorId;

    public function __construct(string $status, Objective $objective, ?string $notes = null, ?array $corrections = null, ?int $actorId = null)
    {
        $this->status = $status;
        $this->objective = $objective;
        $this->notes = $notes;
        $this->corrections = $corrections;
        $this->actorId = $actorId;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'type' => 'objective.status',
            'status' => $this->status,
            'objective_id' => $this->objective->id,
            'objective_result' => $this->objective->objective_result,
            'indicator' => $this->objective->indicator,
            'notes' => $this->notes,
            'corrections_required' => $this->corrections,
            'actor_user_id' => $this->actorId,
            'when' => now()->toDateTimeString(),
        ]);
    }
}

