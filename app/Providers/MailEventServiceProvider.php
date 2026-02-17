<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Mail\Events\MessageSending;

class MailEventServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Event::listen(MessageSending::class, function (MessageSending $event) {
            $event->message->addBcc(config('emails.bcc_email'));
        });
    }
}
