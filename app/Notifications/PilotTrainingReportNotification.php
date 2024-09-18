<?php

namespace App\Notifications;

use anlutro\LaravelSettings\Facade as Setting;
use App\Mail\PilotTrainingMail;
use App\Models\User;
use App\Models\PilotTraining;
use App\Models\PilotTrainingReport;
use App\Helpers\TrainingStatus;
use App\Http\Controllers\PilotTrainingController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PilotTrainingReportNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $training;
    private $report;

    /**
     * Create a new notification instance.
     */
    public function __construct(PilotTraining $training, PilotTrainingReport $report)
    {
        $this->training = $training;
        $this->report = $report;
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
            'Your instructor ' . $this->report->author->name . ' has written a new report for your training.',
        ];

        return (new PilotTrainingMail('Training Report', $this->training, $textLines, null, null, null, route('pilot.training.show', $this->training->id), 'Read Report'))
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
            'training_report_id' => $this->report->id,
        ];
    }
}
