<?php

namespace App\Notifications;

use anlutro\LaravelSettings\Facade as Setting;
use App\Mail\PilotTrainingMail;
use App\Models\User;
use App\Models\PilotTraining;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PilotTrainingPreStatusNotification extends Notification implements ShouldQueue
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
            'We would like to inform you that your training request for: ' . $this->training->getInlineRatings() . ' has now been assigned to pre-training.',
            'Access to the Moodle has been granted'
        ];

        $url2 = "https://moodle.vatsim-scandinavia.org";

        return (new PilotTrainingMail('Training Assigned', $this->training, $textLines, $url1 = null, $url2))
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
