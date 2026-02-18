<?php

namespace App\Providers;

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        \App\Models\Booking::observe(\App\Observers\BookingObserver::class);
        \App\Models\BookingClient::observe(\App\Observers\BookingClientObserver::class);
        \App\Models\Group::observe(\App\Observers\GroupObserver::class);
        \App\Models\Hotel::observe(\App\Observers\HotelObserver::class);
        \App\Models\Destination::observe(\App\Observers\DestinationObserver::class);
        \App\Models\Transfer::observe(\App\Observers\TransferObserver::class);
        \App\Models\Image::observe(\App\Observers\ImageObserver::class);
        \App\Models\File::observe(\App\Observers\FileObserver::class);
        \App\Models\LeadHotel::observe(\App\Observers\LeadHotelObserver::class);
        \App\Models\Room::observe(\App\Observers\RoomObserver::class);
        \App\User::observe(\App\Observers\UserObserver::class);

        \Illuminate\Auth\Notifications\ResetPassword::toMailUsing(function ($notifiable, $token) {
            return (new \Illuminate\Notifications\Messages\MailMessage)
                ->subject(Lang::get('Reset Password Notification'))
                ->line(Lang::get('You are receiving this email because we received a password reset request for your account.'))
                ->action(Lang::get('Reset Password'), route('password.reset', ['token' => $token, 'email' => $notifiable->getEmailForPasswordReset()]))
                ->line(Lang::get('This password reset link will expire in :count minutes.', ['count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire')]))
                ->line(Lang::get('If you did not request a password reset, no further action is required.'));
        });
    }
}
