<?php

namespace App\Notifications;

use App\Helpers\TrainingStatus;
use App\Http\Controllers\PilotTrainingController;
use App\Mail\PilotTrainingMail;
use App\Models\PilotTraining;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PilotTrainingClosedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $training;

    private $trainingStatus;

    private $closedBy;

    private $reason;

    /**
     * Create a new notification instance.
     */
    public function __construct(PilotTraining $training, int $trainingStatus, ?string $reason = null)
    {
        $this->training = $training;
        $this->trainingStatus = $trainingStatus;
        $this->closedBy = strtolower(PilotTrainingController::$statuses[$trainingStatus]['text']);
        $this->reason = $reason;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        $textLines[] = 'We would like to inform you that your training request for ' . $this->training->getInlineRatings() . ' has been *' . $this->closedBy . '*.';
        if (isset($this->reason)) {
            $textLines[] = '**Reason for closure:** ' . $this->reason;
        }

        if ($this->trainingStatus == TrainingStatus::COMPLETED->value) {
            // add feedback
        }

        return (new PilotTrainingMail('Training Request Closed', $this->training, $textLines))
            ->to($this->training->user->notificationEmail, $this->training->user->name);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable)
    {
        return [
            'training_id' => $this->training->id,
            'new_status' => $this->training->status,
            'reason' => $this->reason,
        ];
    }
}
