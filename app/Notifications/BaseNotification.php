<?php

namespace App\Notifications;

use App\Models\NotificationStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable;



    /**
     * Check if this notification is active
     *
     * @return bool
     */
    protected function isActive(): bool
    {
        $status = NotificationStatus::firstOrNew(
            ['notification_class' => static::class],
            ['is_active' => true]
        );

        return $status->is_active;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        // If notification is not active, don't send it through any channel
        if (!$this->isActive()) {
            return [];
        }

        return $this->channels();
    }

    /**
     * Get the notification's delivery channels.
     * This method should be implemented by child classes.
     *
     * @return array
     */
    abstract protected function channels();
}
