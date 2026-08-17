<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * A single reusable in-app notification shape (Section 14) rather than a
 * near-identical class per trigger event - email already goes out
 * separately through the existing Mailable at each of these points, this
 * is purely what feeds the bell dropdown.
 */
class GenericNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $body,
        public ?string $url = null,
        public ?string $type = null,
    ) {}

    /**
     * Bell notifications are opt-out per category (Settings > Notifications),
     * keyed on $type - a category with no explicit preference, or a
     * notification with no $type at all, is always delivered.
     */
    public function via(mixed $notifiable): array
    {
        if ($this->type !== null) {
            $preferences = $notifiable->notification_preferences ?? [];

            if (($preferences[$this->type] ?? true) === false) {
                return [];
            }
        }

        return ['database'];
    }

    public function toArray(mixed $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
        ];
    }
}
