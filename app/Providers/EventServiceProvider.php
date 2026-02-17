<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Notifications\Events\NotificationSent;
use App\Models\NotificationLog;
use Illuminate\Support\Facades\Log;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        \Illuminate\Auth\Events\Registered::class => [
            \Illuminate\Auth\Listeners\SendEmailVerificationNotification::class,
        ],

        \App\Events\BookingSubmitted::class => [
            \App\Listeners\SendBookingSubmittedEmail::class,
            \App\Listeners\SendBookingSubmittedNotification::class,
            \App\Listeners\SendFlightManifestRequestNotification::class,
        ],
        \App\Events\BookingConfirmed::class => [
            \App\Listeners\SendBookingInvoiceNotification::class
        ],

        \App\Events\PaymentSubmitted::class => [
            \App\Listeners\SendPaymentSubmittedEmail::class,
            \App\Listeners\SendPaymentSubmittedNotification::class
        ],
        \App\Events\PaymentConfirmed::class => [
            \App\Listeners\SendPaymentConfirmedNotification::class
        ],
        \App\Events\CardDeclined::class => [
            \App\Listeners\SendCardDeclinedNotification::class
        ],

        \App\Events\CardUpdated::class => [
            \App\Listeners\SendCardUpdatedEmail::class,
            \App\Listeners\SendCardUpdatedNotification::class
        ],

        \App\Events\FlightManifestSubmitted::class => [
            \App\Listeners\SendFlightManifestSubmittedEmail::class,
            \App\Listeners\SendFlightManifestSubmittedNotification::class,
        ],

        \App\Events\PasswordChanged::class => [
            \App\Listeners\SendPasswordChangedNotification::class,
        ],

        \App\Events\UserCreated::class => [
            \App\Listeners\SendUserCreatedNotification::class,
        ],
        \App\Events\GuestChangeSubmitted::class => [
            \App\Listeners\SendGuestChangeNotification::class,
        ],
        \App\Events\GuestChangeApproved::class => [
            \App\Listeners\SendGuestChangeApprovedNotification::class,
        ],
        \App\Events\GuestChangeCancelled::class => [
            \App\Listeners\SendGuestChangeCancelledNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        // Listen for notification sent events
        Event::listen(NotificationSent::class, function (NotificationSent $event) {
            try {
                // Log the notification
                $log = NotificationLog::logNotification($event->notification);

                // Debug log
                Log::debug('Notification logged', [
                    'notification' => get_class($event->notification),
                    'log_id' => $log->id,
                    'channel' => $event->channel,
                    'notifiable_type' => get_class($event->notifiable),
                    'notifiable_id' => $event->notifiable->id ?? null
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to log notification', [
                    'notification' => get_class($event->notification),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        });
    }
}
