<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DynamicNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $subject;
    public $body;
    public $channels;
    public $actionUrl;
    public $inAppSubject;
    public $inAppBody;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        string $subject,
        string $body,
        array $channels,
        ?string $actionUrl = null,
        ?string $inAppSubject = null,
        ?string $inAppBody = null
    ) {
        $this->subject = $subject;
        $this->body = $body;
        $this->channels = $channels;
        $this->actionUrl = $actionUrl;
        $this->inAppSubject = $inAppSubject ?: $subject;
        $this->inAppBody = $inAppBody ?: $body;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->subject)
            ->line(new \Illuminate\Support\HtmlString($this->body));
            
        if ($this->actionUrl) {
            $mail->action('Sisteme Git', $this->actionUrl);
        }

        return $mail;
    }

    /**
     * Get the array representation of the notification (Zil / Veritabanı).
     */
    public function toArray(object $notifiable): array
    {
        return [
            'subject' => $this->inAppSubject ?: $this->subject,
            'body' => $this->inAppBody ?: $this->body,
            'actionUrl' => $this->actionUrl,
        ];
    }
}
