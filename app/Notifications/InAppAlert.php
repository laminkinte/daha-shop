<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * A simple, reusable in-app (database) notification - the free channel for
 * anything that doesn't need to interrupt someone via SMS: the recipient
 * already checks their dashboard as part of their normal workflow (vendors,
 * agents), or the underlying event isn't urgent enough to justify SMS cost.
 * Email is sent separately via the event's own Mailable, not through this.
 */
class InAppAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public ?string $url = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
        ];
    }
}
