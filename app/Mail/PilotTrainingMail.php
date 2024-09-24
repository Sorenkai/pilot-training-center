<?php

namespace App\Mail;

use App\Models\PilotTraining;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PilotTrainingMail extends Mailable
{
    use Queueable, SerializesModels;

    private $training;

    private $mailSubject;

    private $textLines;

    private $contactMail;

    private $actionURL;

    private $actionText;

    private $actionColor;

    private $url1;

    private $url2;

    /**
     * Create a new message instance.
     */
    public function __construct(string $mailSubject, PilotTraining $training, array $textLines, ?string $contactMail = null, ?string $url1 = null, ?string $url2 = null, ?string $actionUrl = null, ?string $actionText = null, string $actionColor = 'primary')
    {
        $this->mailSubject = $mailSubject;
        $this->training = $training;

        $this->textLines = $textLines;
        $this->contactMail = $contactMail;

        $this->actionUrl = $actionUrl;
        $this->actionText = $actionText;
        $this->actionColor = $actionColor;

        $this->url1 = $url1;
        $this->url2 = $url2;
    }

    public function build()
    {
        return $this->subject($this->mailSubject)->markdown('mail.pilot.training', [
            'firstName' => $this->training->user->first_name,
            'textLines' => $this->textLines,
            'contactMail' => $this->contactMail,

            'actionUrl' => $this->actionUrl,
            'actionText' => $this->actionText,
            'actionColor' => $this->actionColor,
            'url1' => $this->url1,
            'url2' => $this->url2,
        ]);
    }
}
