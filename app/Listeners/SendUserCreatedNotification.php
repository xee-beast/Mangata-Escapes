<?php

namespace App\Listeners;

use App\Events\UserCreated;
use App\Notifications\UserCreated as UserCreatedNotification;
use App\User;

class SendUserCreatedNotification
{
    /**
     * Handle the event.
     *
     * @param  UserCreated  $event
     * @return void
     */
    public function handle(UserCreated $event)
    {
        foreach(User::role('Super Admin')->get() as $user) {
            $user->notify(new UserCreatedNotification($event->user, $event->password));
        }
    }
}