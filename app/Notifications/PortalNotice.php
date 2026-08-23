<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A general-purpose portal + email notice carrying an action URL.
 * Domain-specific notifications compose this rather than ad-hoc data arrays.
 */
class PortalNotice extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $body,
        public string $url,
    ) {}

    /** @return array<int, string> */
    public function via(User $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->line($this->body)
            ->action('Open your portal', url($this->url));
    }

    /** @return array<string, string> */
    public function toArray(User $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
        ];
    }
}
