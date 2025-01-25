<?php

namespace App\Notifications;

use anlutro\LaravelSettings\Facade as Setting;
use App\Mail\PilotTrainingMail;
use App\Models\PilotTraining;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PilotTrainingCreatedNotification extends Notification implements ShouldQueue
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
            'We hereby confirm that we have received your training request for ' . $this->training->getInlineRatings() . '.',
            'Your callsign is: **' . $this->training->callsign->callsign . '**, which will be used for DUAL and solo flights.',
            'The theory is completed at your own pace and ther is no specific deadline for the exam.',
            'You\'ll need to log in to Moodle once before we can grant you access.',
            'After successfully completing the theoretical exam, you will start practical flight training together with your assigned instructor.',
            'The theoretical material and other documents are available on the VATSIM Scandinavia wiki.',
            'If you have any questions, feel free to contact your instructor or ask in the pilot training channel on [Discord](' . Setting::get('linkDiscord') . ').',
        ];

        $bcc = User::allWithGroup(4)->where('setting_notify_newreq', true);
        foreach ($bcc as $key => $value) {
            if (! $user->isInstructorOrAbove()) {
                $bcc->pull($key);
            }
        }

        $url1 = 'https://wiki.vatsim-scandinavia.org/shelves/pilot-training';
        $url2 = 'https://moodle.vatsim-scandinavia.org';
        $contactMail = Setting::get('ptmEmail');

        return (new PilotTrainingMail('New Training Request Confirmation', $this->training, $textLines, $contactMail, $url1, $url2))
            ->to($this->training->user->notificationEmail, $this->training->user->name)
            ->bcc($bcc->pluck('notificationEmail'));
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
        ];
    }
}
