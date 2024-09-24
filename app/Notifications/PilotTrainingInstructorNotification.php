<?php

namespace App\Notifications;

use anlutro\LaravelSettings\Facade as Setting;
use App\Mail\PilotTrainingMail;
use App\Models\PilotTraining;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PilotTrainingInstructorNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $training;

    private $contactMail;

    /**
     * Create a new notification instance.
     */
    public function __construct(PilotTraining $training)
    {
        $this->training = $training;
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
        $textLines = [
            "Get ready! You've been assigned an instructor for your training: " . $this->training->getInlineRatings() . '.',
            'Your instructor is: **' . $this->training->getInlineInstructors() . '**. You can contact them on [Discord](' . Setting::get('linkDiscord') . ').',
            'Your instructor will then give you more information on the next steps.',
        ];
        $contactMail = Setting::get('ptmEmail');

        return (new PilotTrainingMail('Training Instructor Assigned', $this->training, $textLines, $contactMail))
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
            'instructors' => $this->training->getInlineInstructors(),
        ];
    }
}
