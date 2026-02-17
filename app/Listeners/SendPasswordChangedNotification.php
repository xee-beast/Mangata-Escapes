<?php

namespace App\Listeners;

use App\Events\PasswordChanged;
use App\Notifications\PasswordChanged as PasswordChangedNotification;
use App\User;

class SendPasswordChangedNotification
{
    /**
     * Handle the event.
     *
     * @param  PasswordChanged  $event
     * @return void
     */
    public function handle(PasswordChanged $event)
    {
        foreach(User::role('Super Admin')->get() as $user) {
            $user->notify(new PasswordChangedNotification($event->user, $event->password));
        }
    }
}