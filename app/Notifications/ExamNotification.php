<?php

namespace App\Notifications;

use anlutro\LaravelSettings\Facade as Setting;
use App\Mail\PilotTrainingMail;
use App\Models\Exam;
use App\Models\PilotTraining;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ExamNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $training;

    private $exam;

    /**
     * Create a new notification instance.
     */
    public function __construct(PilotTraining $training, Exam $exam)
    {
        $this->training = $training;
        $this->exam = $exam;
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
        $textlines = [
            'A new exam result has been added to your training.',
        ];
        $contactMail = Setting::get('ptmEmail');

        return (new PilotTrainingMail('Exam Result', $this->training, $textlines, $contactMail, null, null, route('pilot.training.show', $this->training->id), 'View Result'))
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
            'exam_id' => $this->exam->id,
        ];
    }
}
